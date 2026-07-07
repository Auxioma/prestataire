<?php

/**
 * Copyright(c) 2026 Trouve moi
 *
 * Ce fichier fait partie d’un projet développé par Auxioma Web Agency.
 * Tous droits réservés.
 *
 * Ce code source est la propriété exclusive de Auxioma Web Agency.
 * Toute reproduction, modification, distribution ou utilisation sans autorisation préalable est interdite.
 */

namespace App\Controller;

use App\Entity\ClientProfile;
use App\Entity\PrestataireAvailability;
use App\Entity\PrestataireInterventionZone;
use App\Entity\PrestataireProfile;
use App\Entity\PrestataireService;
use App\Entity\User;
use App\Enum\FavoriteTypeEnum;
use App\Form\AccountSettingsType;
use App\Form\PrestataireAvailabilityCollectionType;
use App\Form\PrestataireCompanyTabType;
use App\Form\PrestataireInterventionZoneType;
use App\Form\PrestataireServiceType;
use App\Repository\FavoriteRepository;
use App\Repository\PrestataireProfileRepository;
use App\Repository\PrestataireServiceRepository;
use App\Repository\ServiceCategoryRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use App\Service\SireneClient;

class ProfileController extends AbstractController
{
    #[Route('/prestataire/parametres', name: 'app_prestataire_settings')]
    public function settings(
        Request $request,
        EntityManagerInterface $entityManager,
        ServiceCategoryRepository $categoryRepository,
        FormFactoryInterface $formFactory,
        SireneClient $sireneClient,
    ): Response {
    // utilisateur connecté
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // profil prestataire
        if (null === $user->getPrestataireProfile()) {
            $profile = new PrestataireProfile();
            $user->setPrestataireProfile($profile);
            $profile->setAccount($user);
        }

        $prestataireProfile = $user->getPrestataireProfile();

        // calendrier des disponibilités
        $existingDays = [];
        foreach ($prestataireProfile->getAvailabilities() as $availability) {
            $existingDays[] = $availability->getDayOfWeek();
        }

        for ($day = 1; $day <= 7; ++$day) {
            if (!in_array($day, $existingDays, true)) {
                $availability = new PrestataireAvailability();
                $availability->setPrestataireProfile($prestataireProfile);
                $availability->setDayOfWeek($day);

                $prestataireProfile->addAvailability($availability);
                $entityManager->persist($availability);
            }
        }

        $entityManager->persist($prestataireProfile);
        $entityManager->flush();

        // tri des disponibilités
        $availabilities = $prestataireProfile->getAvailabilities()->toArray();
        usort(
            $availabilities,
            static fn(PrestataireAvailability $a, PrestataireAvailability $b): int => $a->getDayOfWeek() <=> $b->getDayOfWeek()
        );

        // formulaire profil utilisateur
        $userForm = $formFactory->createNamed(
            'user_profile_form',
            \App\Form\UserProfileTabType::class,
            $user
        );

        // formulaire entreprise
        $companyForm = $formFactory->createNamed(
            'company_form',
            PrestataireCompanyTabType::class,
            $prestataireProfile
        );

        // formulaire profil public
        $publicProfileForm = $formFactory->createNamed(
            'public_profile_form',
            \App\Form\PrestatairePublicProfileTabType::class,
            $prestataireProfile
        );

        // formulaire disponibilités
        $availabilityForm = $this->createForm(
            PrestataireAvailabilityCollectionType::class,
            $prestataireProfile,
            [
                'action' => $this->generateUrl('app_prestataire_settings'),
                'method' => 'POST',
            ]
        );

        // formulaire ajout de zone
        $zone = new PrestataireInterventionZone();
        $zone->setPrestataireProfile($prestataireProfile);

        $zoneForm = $formFactory->createNamed(
            'zone_form',
            PrestataireInterventionZoneType::class,
            $zone,
            [
                'action' => $this->generateUrl('app_prestataire_zone_add'),
                'method' => 'POST',
            ]
        );

        // liste des zones
        $zones = $prestataireProfile->getPrestataireInterventionZones();

        // traitement des requêtes formulaires
        $userForm->handleRequest($request);
        $publicProfileForm->handleRequest($request);
        $companyForm->handleRequest($request);
        $availabilityForm->handleRequest($request);

        // état initial de la future modale de prévisualisation
        $companyVerificationPreview = null;
        $openCompanyVerificationModal = false;

        // infos utilisateur
        if ($userForm->isSubmitted() && $userForm->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Vos informations personnelles ont été enregistrées.');

            return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'profile-panel']);
        }

        // infos profil public
        if ($publicProfileForm->isSubmitted() && $publicProfileForm->isValid()) {
            if ($prestataireProfile && $prestataireProfile->getCompanyName()) {
                $prestataireProfile->setSlug(
                    mb_strtolower(str_replace(' ', '-', $prestataireProfile->getCompanyName()))
                );
            }

            $entityManager->persist($prestataireProfile);
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil public a été enregistré.');

            return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'profile-panel']);
        }

        // infos entreprise
        if ($companyForm->isSubmitted() && $companyForm->isValid()) {
            if ($prestataireProfile && $prestataireProfile->getCompanyName()) {
                $prestataireProfile->setSlug(
                    mb_strtolower(str_replace(' ', '-', $prestataireProfile->getCompanyName()))
                );
            }

            // données brutes du formulaire entreprise
            $companyFormData = $request->request->all('company_form');

            // action : clic sur "Vérifier mon entreprise"
            $isVerifyCompanyAction = is_array($companyFormData) && isset($companyFormData['verifyCompany']);

            // action : clic sur "Accepter le pré-remplissage"
            $isAcceptCompanyVerificationAction = $request->request->has('acceptCompanyVerification');

            // action : clic sur "Refuser"
            $isRejectCompanyVerificationAction = $request->request->has('rejectCompanyVerification');

            // refus du pré-remplissage
            if ($isRejectCompanyVerificationAction) {
                $this->addFlash('info', 'Le pré-remplissage automatique a été refusé.');

                return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'company-panel']);
            }

            // acceptation du pré-remplissage
            if ($isAcceptCompanyVerificationAction) {
                $previewPayload = $request->getSession()->get('company_verification_preview');

                if (!is_array($previewPayload) || empty($previewPayload['fields'])) {
                    $this->addFlash('warning', 'La prévisualisation a expiré. Veuillez relancer la vérification.');

                    return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'company-panel']);
                }

                $fields = $previewPayload['fields'];

                if (!empty($fields['companyName'])) {
                    $prestataireProfile->setCompanyName($fields['companyName']);
                }

                if (!empty($fields['legalName'])) {
                    $prestataireProfile->setLegalName($fields['legalName']);
                }

                if (!empty($fields['structureType'])) {
                    $prestataireProfile->setStructureType($fields['structureType']);
                }

                if (!empty($fields['address'])) {
                    $prestataireProfile->setAddress($fields['address']);
                }

                if (!empty($fields['postalCode'])) {
                    $prestataireProfile->setPostalCode($fields['postalCode']);
                }

                if (!empty($fields['city'])) {
                    $prestataireProfile->setCity($fields['city']);
                }

                if (!empty($fields['country'])) {
                    $prestataireProfile->setCountry($fields['country']);
                }

                if ($prestataireProfile->getCompanyName()) {
                    $prestataireProfile->setSlug(
                        mb_strtolower(str_replace(' ', '-', $prestataireProfile->getCompanyName()))
                    );
                }

                $entityManager->persist($prestataireProfile);
                $entityManager->flush();

                $request->getSession()->remove('company_verification_preview');

                $this->addFlash('success', 'Les informations officielles de l’entreprise ont été injectées dans votre fiche.');

                return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'company-panel']);
            }

            // clic sur le bouton de vérification entreprise
            if ($isVerifyCompanyAction) {
                $siret = $prestataireProfile->getSiret();

                if (!$siret) {
                    $this->addFlash('warning', 'Veuillez renseigner un numéro SIRET avant de lancer la vérification.');

                    return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'company-panel']);
                }

                try {
                    $previewPayload = $sireneClient->buildCompanyPreviewFromSiret($siret);

                    $companyVerificationPreview = [
                        'siret' => $previewPayload['siret'],
                        'fields' => [
                            [
                                'label' => 'Nom de l’entreprise / Enseigne',
                                'current' => $prestataireProfile->getCompanyName(),
                                'incoming' => $previewPayload['fields']['companyName'] ?? null,
                            ],
                            [
                                'label' => 'Raison sociale',
                                'current' => $prestataireProfile->getLegalName(),
                                'incoming' => $previewPayload['fields']['legalName'] ?? null,
                            ],
                            [
                                'label' => 'Forme juridique',
                                'current' => $prestataireProfile->getStructureType(),
                                'incoming' => $previewPayload['fields']['structureType'] ?? null,
                            ],
                            [
                                'label' => 'Adresse',
                                'current' => $prestataireProfile->getAddress(),
                                'incoming' => $previewPayload['fields']['address'] ?? null,
                            ],
                            [
                                'label' => 'Code postal',
                                'current' => $prestataireProfile->getPostalCode(),
                                'incoming' => $previewPayload['fields']['postalCode'] ?? null,
                            ],
                            [
                                'label' => 'Ville',
                                'current' => $prestataireProfile->getCity(),
                                'incoming' => $previewPayload['fields']['city'] ?? null,
                            ],
                            [
                                'label' => 'Pays',
                                'current' => $prestataireProfile->getCountry(),
                                'incoming' => $previewPayload['fields']['country'] ?? null,
                            ],
                        ],
                    ];

                    $request->getSession()->set('company_verification_preview', $previewPayload);
                    $openCompanyVerificationModal = true;
                } catch (\Throwable $e) {
                    $this->addFlash('danger', $e->getMessage());

                    return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'company-panel']);
                }
            } else {
                // sauvegarde classique entreprise
                $entityManager->persist($prestataireProfile);
                $entityManager->flush();

                $this->addFlash('success', 'Les informations de l’entreprise ont été enregistrées.');

                return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'company-panel']);
            }
        }

        // disponibilités
        if ($availabilityForm->isSubmitted() && $availabilityForm->isValid()) {
            $entityManager->persist($prestataireProfile);
            $entityManager->flush();

            $this->addFlash('success', 'Vos disponibilités ont bien été enregistrées.');

            return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'dispo-panel']);
        }

        // carte des zones d’intervention
        $zoneMap = null;
        $firstMappableZone = null;

        foreach ($zones as $existingZone) {
            if (
                null !== $existingZone
                && null !== $existingZone->getLatitude()
                && null !== $existingZone->getLongitude()
            ) {
                $firstMappableZone = $existingZone;
                break;
            }
        }

        if (null !== $firstMappableZone) {
            $zoneMap = (new Map('default'))
                ->center(new Point(
                    (float) $firstMappableZone->getLatitude(),
                    (float) $firstMappableZone->getLongitude()
                ))
                ->zoom(8)
                ->options(
                    (new LeafletOptions())
                        ->tileLayer(new TileLayer(
                            url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                            options: ['maxZoom' => 19]
                        ))
                );

            foreach ($zones as $existingZone) {
                if (
                    null !== $existingZone
                    && null !== $existingZone->getLatitude()
                    && null !== $existingZone->getLongitude()
                ) {
                    $label = $existingZone->getCity() ?: 'Zone d’intervention';

                    $zoneMap->addMarker(new Marker(
                        position: new Point(
                            (float) $existingZone->getLatitude(),
                            (float) $existingZone->getLongitude()
                        ),
                        title: $label,
                        infoWindow: new InfoWindow(
                            content: '<strong>' . htmlspecialchars($label) . '</strong><br>Rayon : ' . (int) $existingZone->getRadiusKm() . ' km'
                        )
                    ));
                }
            }
        }

        // rendu de la page paramètres
        return $this->render('profile/prestataire_profile.html.twig', [
            'userForm' => $userForm->createView(),
            'publicProfileForm' => $publicProfileForm->createView(),
            'companyForm' => $companyForm->createView(),
            'zoneForm' => $zoneForm->createView(),
            'zones' => $zones,
            'user' => $user,
            'categories' => $categoryRepository->findWithSubCategories(),
            'zoneMap' => $zoneMap,
            'availabilityForm' => $availabilityForm->createView(),
            'availabilities' => $availabilities,
            'companyVerificationPreview' => $companyVerificationPreview,
            'openCompanyVerificationModal' => $openCompanyVerificationModal,
        ]);
    }

    #[Route('/prestataire/service/ajouter', name: 'app_prestataire_add_service', methods: ['POST'])]
    public function addService(
        Request $request,
        EntityManagerInterface $em,
        ServiceRepository $serviceRepo,
        SluggerInterface $slugger,
    ): Response {
        $serviceId = $request->request->get('service_id');
        $user = $this->getUser();
        $service = $serviceRepo->find($serviceId);

        if (!$service || !($user instanceof \App\Entity\User) || !$user->getPrestataireProfile()) {
            $this->addFlash('error', 'Une erreur est survenue.');

            return $this->redirectToRoute('app_prestataire_settings');
        }

        $exists = $em->getRepository(PrestataireService::class)->findOneBy([
            'prestataire' => $user->getPrestataireProfile(),
            'service' => $service,
        ]);

        if ($exists) {
            $this->addFlash('warning', 'Vous proposez déjà ce service !');
        } else {
            $pService = new PrestataireService();
            $pService->setPrestataire($user->getPrestataireProfile());
            $pService->setService($service);
            $pService->setIsActive(true);

            $baseSlug = (string) $slugger->slug($service->getName() ?: 'prestation')->lower();
            $uniqueSlug = sprintf('%s-%s', $baseSlug, substr(bin2hex(random_bytes(4)), 0, 8));
            $pService->setSlug($uniqueSlug);

            $em->persist($pService);
            $em->flush();

            $this->addFlash('success', 'Service ajouté !');
        }

        return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'services-panel']);
    }

    // Suppression d'un service
    #[Route('/prestataire/service/supprimer/{id}', name: 'app_prestataire_service_delete', methods: ['POST'])]
    public function delete(Request $request, PrestataireService $ps, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (
            !$user instanceof \App\Entity\User
            || !$user->getPrestataireProfile()
            || $ps->getPrestataire() !== $user->getPrestataireProfile()
        ) {
            throw $this->createAccessDeniedException();
        }
        if ($this->isCsrfTokenValid('delete' . $ps->getId(), $request->request->get('_token'))) {
            $em->remove($ps);
            $em->flush();
            $this->addFlash('success', 'Le service a bien été retiré de votre profil.');
        }

        // On redirige vers la route nommée avec l'ancre
        return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'services-panel']);
    }

    #[Route('/prestataire/service/editer/{id}', name: 'app_prestataire_service_edit')]
    public function edit(Request $request, PrestataireService $ps, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (
            !$user instanceof \App\Entity\User
            || !$user->getPrestataireProfile()
            || $ps->getPrestataire() !== $user->getPrestataireProfile()
        ) {
            throw $this->createAccessDeniedException();
        }

        $oldReduction = $ps->getTauxReduction();

        $form = $this->createForm(PrestataireServiceType::class, $ps);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ('quote' === $ps->getPricingType()) {
                $ps->setPrixCatalogue('0');
                $ps->setTauxReduction(null);
                $ps->setPromotionCreatedAt(null);
            } else {
                $newReduction = $ps->getTauxReduction();

                $oldReductionValue = null !== $oldReduction ? (float) $oldReduction : 0;
                $newReductionValue = null !== $newReduction ? (float) $newReduction : 0;

                if ($newReductionValue > 0) {
                    if ($oldReductionValue <= 0 || $oldReductionValue !== $newReductionValue) {
                        $ps->setPromotionCreatedAt(new \DateTimeImmutable());
                    }
                } else {
                    $ps->setPromotionCreatedAt(null);
                }
            }

            $em->flush();

            $this->addFlash('success', 'Tarifs mis à jour !');

            return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'services-panel']);
        }

        return $this->render('prestataire/edit_service.html.twig', [
            'form' => $form->createView(),
            'ps' => $ps,
        ]);
    }

    /**
     * PARAMETRES PROFILE CLIENT.
     */
    #[Route('/client/parametres', name: 'app_client_settings')]
    public function clientSettings(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // 1. Initialisation à la volée du ClientProfile s'il n'existe pas encore
        if (null === $user->getClientProfile()) {
            $profile = new ClientProfile();

            $user->setClientProfile($profile);
            $profile->setAccount($user);
        }

        // 2. Utiliser le formulaire global AccountSettingsType lié au $user
        $form = $this->createForm(AccountSettingsType::class, $user, [
            'profile_type' => \in_array('ROLE_PRESTATAIRE', $user->getRoles(), true) ? 'prestataire' : 'client',
        ]);
        $form->handleRequest($request);

        // 3. Traitement de la soumission
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil client a été mis à jour avec succès !');

            return $this->redirectToRoute('app_client_settings');
        }

        // 4. Envoi à la vue client dédiée
        return $this->render('profile/client_profile.html.twig', [
            'settingsForm' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/client/parametres/favoris', name: 'app_client_settings_favorites', methods: ['GET'])]
    public function clientFavorites(
        FavoriteRepository $favoriteRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
        PrestataireServiceRepository $prestataireServiceRepository,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $favorites = $favoriteRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        $providerIds = [];
        $prestationIds = [];
        $bonsPlanIds = [];

        foreach ($favorites as $favorite) {
            $type = $favorite->getType();

            if ($type === FavoriteTypeEnum::PRESTATAIRE) {
                $providerIds[] = $favorite->getTargetId();
            }

            if ($type === FavoriteTypeEnum::PRESTATION) {
                $prestationIds[] = $favorite->getTargetId();
            }

            if ($type === FavoriteTypeEnum::BON_PLAN) {
                $bonsPlanIds[] = $favorite->getTargetId();
            }
        }

        $favoriteProviders = !empty($providerIds)
            ? $prestataireProfileRepository->findBy(['id' => array_unique($providerIds)])
            : [];

        $favoritePrestations = !empty($prestationIds)
            ? $prestataireServiceRepository->findBy(['id' => array_unique($prestationIds)])
            : [];

        $favoriteBonsPlans = !empty($bonsPlanIds)
            ? $prestataireServiceRepository->findBy(['id' => array_unique($bonsPlanIds)])
            : [];

        return $this->render('client/client_favorite.html.twig', [
            'user' => $user,
            'favoriteProviders' => $favoriteProviders,
            'favoritePrestations' => $favoritePrestations,
            'favoriteBonsPlans' => $favoriteBonsPlans,
        ]);
    }
}
