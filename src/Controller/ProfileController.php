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
use App\Entity\PrestataireDocument;
use App\Entity\PrestataireInterventionZone;
use App\Entity\PrestataireProfile;
use App\Entity\PrestataireService;
use App\Entity\User;
use App\Enum\FavoriteTypeEnum;
use App\Enum\PrestataireProfileStatusEnum;
use App\Enum\VerificationStatusEnum;
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
use App\Service\SireneClient;
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
use Symfony\Component\Form\FormInterface;


class ProfileController extends AbstractController
{
    // #region Settings
    #[Route('/prestataire/parametres', name: 'app_prestataire_settings')]
    public function settings(
        Request $request,
        EntityManagerInterface $entityManager,
        ServiceCategoryRepository $categoryRepository,
        FormFactoryInterface $formFactory,
        SireneClient $sireneClient,
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $prestataireProfile = $this->getOrCreatePrestataireProfile($user, $entityManager);
        $this->ensureDefaultAvailabilities($prestataireProfile, $entityManager);

        $availabilities = $this->getSortedAvailabilities($prestataireProfile);
        $documents = $this->getSortedDocuments($prestataireProfile);
        $zones = $prestataireProfile->getPrestataireInterventionZones();

        $forms = $this->buildSettingsForms(
            user: $user,
            prestataireProfile: $prestataireProfile,
            formFactory: $formFactory
        );

        $forms['userForm']->handleRequest($request);
        $forms['publicProfileForm']->handleRequest($request);
        $forms['companyForm']->handleRequest($request);
        $forms['availabilityForm']->handleRequest($request);
        $forms['documentForm']->handleRequest($request);

        $companyVerificationPreview = null;
        $openCompanyVerificationModal = false;

        if ($response = $this->handleDocumentForm(
            request: $request,
            entityManager: $entityManager,
            prestataireProfile: $prestataireProfile,
            documentForm: $forms['documentForm'],
            document: $forms['documentEntity'],
        )) {
            return $response;
        }

        if ($response = $this->handleUserForm(
            entityManager: $entityManager,
            userForm: $forms['userForm'],
            user: $user,
        )) {
            return $response;
        }

        if ($response = $this->handlePublicProfileForm(
            entityManager: $entityManager,
            publicProfileForm: $forms['publicProfileForm'],
            prestataireProfile: $prestataireProfile,
        )) {
            return $response;
        }

        $companyResult = $this->handleCompanyForm(
            request: $request,
            entityManager: $entityManager,
            companyForm: $forms['companyForm'],
            prestataireProfile: $prestataireProfile,
            sireneClient: $sireneClient,
        );

        if ($companyResult['response'] instanceof Response) {
            return $companyResult['response'];
        }

        $companyVerificationPreview = $companyResult['companyVerificationPreview'];
        $openCompanyVerificationModal = $companyResult['openCompanyVerificationModal'];

        if ($response = $this->handleAvailabilityForm(
            entityManager: $entityManager,
            availabilityForm: $forms['availabilityForm'],
            prestataireProfile: $prestataireProfile,
        )) {
            return $response;
        }

        $zoneMap = $this->buildZoneMap($zones);

        return $this->render('profile/prestataire_profile.html.twig', [
            'userForm' => $forms['userForm']->createView(),
            'publicProfileForm' => $forms['publicProfileForm']->createView(),
            'companyForm' => $forms['companyForm']->createView(),
            'zoneForm' => $forms['zoneForm']->createView(),
            'zones' => $zones,
            'user' => $user,
            'categories' => $categoryRepository->findWithSubCategories(),
            'zoneMap' => $zoneMap,
            'availabilityForm' => $forms['availabilityForm']->createView(),
            'availabilities' => $availabilities,
            'companyVerificationPreview' => $companyVerificationPreview,
            'openCompanyVerificationModal' => $openCompanyVerificationModal,
            'documentForm' => $forms['documentForm']->createView(),
            'documents' => $documents,
        ]);
    }

    // #region Creation/récuperation de profil
    private function getOrCreatePrestataireProfile(User $user, EntityManagerInterface $entityManager): PrestataireProfile
    {
        if (null === $user->getPrestataireProfile()) {
            $profile = new PrestataireProfile();
            $user->setPrestataireProfile($profile);
            $profile->setAccount($user);

            $entityManager->persist($profile);
            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $user->getPrestataireProfile();
    }
    // #endregion

    // #region Initialisation des disponibilités 
    private function ensureDefaultAvailabilities(PrestataireProfile $prestataireProfile, EntityManagerInterface $entityManager): void
    {
        $existingDays = [];

        foreach ($prestataireProfile->getAvailabilities() as $availability) {
            $existingDays[] = $availability->getDayOfWeek();
        }

        $hasChanges = false;

        for ($day = 1; $day <= 7; ++$day) {
            if (!in_array($day, $existingDays, true)) {
                $availability = new PrestataireAvailability();
                $availability->setPrestataireProfile($prestataireProfile);
                $availability->setDayOfWeek($day);

                $prestataireProfile->addAvailability($availability);
                $entityManager->persist($availability);
                $hasChanges = true;
            }
        }

        if ($hasChanges) {
            $entityManager->persist($prestataireProfile);
            $entityManager->flush();
        }
    }
    // #endregion

    // #region Tri des disponibilités
    private function getSortedAvailabilities(PrestataireProfile $prestataireProfile): array
    {
        $availabilities = $prestataireProfile->getAvailabilities()->toArray();

        usort(
            $availabilities,
            static fn(PrestataireAvailability $a, PrestataireAvailability $b): int => $a->getDayOfWeek() <=> $b->getDayOfWeek()
        );

        return $availabilities;
    }
    // #endregion

    // #region Tri des documments
    private function getSortedDocuments(PrestataireProfile $prestataireProfile): array
    {
        $documents = $prestataireProfile->getDocuments()->toArray();

        usort(
            $documents,
            static fn(PrestataireDocument $a, PrestataireDocument $b): int => ($b->getCreatedAt()?->getTimestamp() ?? 0) <=> ($a->getCreatedAt()?->getTimestamp() ?? 0)
        );

        return $documents;
    }
    // #endregion

    // #region Construction de tous les formulaires
    private function buildSettingsForms(
        User $user,
        PrestataireProfile $prestataireProfile,
        FormFactoryInterface $formFactory,
    ): array {
        $userForm = $formFactory->createNamed(
            'user_profile_form',
            \App\Form\UserProfileTabType::class,
            $user
        );

        $companyForm = $formFactory->createNamed(
            'company_form',
            PrestataireCompanyTabType::class,
            $prestataireProfile
        );

        $publicProfileForm = $formFactory->createNamed(
            'public_profile_form',
            \App\Form\PrestatairePublicProfileTabType::class,
            $prestataireProfile
        );

        $availabilityForm = $this->createForm(
            PrestataireAvailabilityCollectionType::class,
            $prestataireProfile,
            [
                'action' => $this->generateUrl('app_prestataire_settings'),
                'method' => 'POST',
            ]
        );

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

        $document = new PrestataireDocument();
        $document->setPrestataireProfile($prestataireProfile);

        $documentForm = $formFactory->createNamed(
            'document_form',
            \App\Form\PrestataireDocumentType::class,
            $document,
            [
                'action' => $this->generateUrl('app_prestataire_settings'),
                'method' => 'POST',
            ]
        );

        return [
            'userForm' => $userForm,
            'companyForm' => $companyForm,
            'publicProfileForm' => $publicProfileForm,
            'availabilityForm' => $availabilityForm,
            'zoneForm' => $zoneForm,
            'documentForm' => $documentForm,
            'documentEntity' => $document,
        ];
    }
    // #endregion

    // METHODES DE TRAITEMENT DES FORMULAIRES
    // #region Documents
    private function handleDocumentForm(
        Request $request,
        EntityManagerInterface $entityManager,
        PrestataireProfile $prestataireProfile,
        FormInterface $documentForm,
        PrestataireDocument $document,
    ): ?Response {
        if (!$documentForm->isSubmitted()) {
            return null;
        }

        if ($documentForm->isValid()) {
            $document->setPrestataireProfile($prestataireProfile);
            $prestataireProfile->addDocument($document);

            $entityManager->persist($document);
            $entityManager->persist($prestataireProfile);
            $entityManager->flush();

            $this->addFlash('success', 'Le document a bien été ajouté.');

            return $this->redirectToRoute('app_prestataire_settings', [
                'tab' => 'company',
            ]);
        }

        $this->addFlash('danger', 'Le document n’a pas pu être ajouté. Vérifiez les champs du formulaire.');

        return null;
    }
    // #endregion

    // #region Utilisateur
    private function handleUserForm(
        EntityManagerInterface $entityManager,
        FormInterface $userForm,
        User $user,
    ): ?Response {
        if (!$userForm->isSubmitted() || !$userForm->isValid()) {
            return null;
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Vos informations personnelles ont été enregistrées.');

        return $this->redirectToRoute('app_prestataire_settings', [
            'tab' => 'profile',
        ]);
    }
    // #endregion

    // #region Profil Public
    private function handlePublicProfileForm(
        EntityManagerInterface $entityManager,
        FormInterface $publicProfileForm,
        PrestataireProfile $prestataireProfile,
    ): ?Response {
        if (!$publicProfileForm->isSubmitted() || !$publicProfileForm->isValid()) {
            return null;
        }

        $this->updatePrestataireSlug($prestataireProfile);

        $entityManager->persist($prestataireProfile);
        $entityManager->flush();

        $this->addFlash('success', 'Votre profil public a été enregistré.');

        return $this->redirectToRoute('app_prestataire_settings', [
            'tab' => 'profile',
        ]);
    }
    // #endregion

    // #region Informations de entreprise
    private function handleCompanyForm(
        Request $request,
        EntityManagerInterface $entityManager,
        FormInterface $companyForm,
        PrestataireProfile $prestataireProfile,
        SireneClient $sireneClient,
    ): array {
        $companyVerificationPreview = null;
        $openCompanyVerificationModal = false;

        if (!$companyForm->isSubmitted() || !$companyForm->isValid()) {
            return [
                'response' => null,
                'companyVerificationPreview' => $companyVerificationPreview,
                'openCompanyVerificationModal' => $openCompanyVerificationModal,
            ];
        }

        $this->updatePrestataireSlug($prestataireProfile);

        $companyFormData = $request->request->all('company_form');

        $isVerifyCompanyAction = is_array($companyFormData) && array_key_exists('verifyCompany', $companyFormData);
        $isAcceptCompanyVerificationAction = $request->request->has('acceptCompanyVerification');
        $isRejectCompanyVerificationAction = $request->request->has('rejectCompanyVerification');

        if ($isRejectCompanyVerificationAction) {
            $this->addFlash('info', 'Le pré-remplissage automatique a été refusé.');

            return [
                'response' => $this->redirectToRoute('app_prestataire_settings', ['tab' => 'company']),
                'companyVerificationPreview' => null,
                'openCompanyVerificationModal' => false,
            ];
        }

        if ($isAcceptCompanyVerificationAction) {
            $response = $this->applyAcceptedCompanyVerification($request, $entityManager, $prestataireProfile);

            return [
                'response' => $response,
                'companyVerificationPreview' => null,
                'openCompanyVerificationModal' => false,
            ];
        }

        if ($isVerifyCompanyAction) {
            try {
                $preview = $this->buildCompanyVerificationPreview($request, $prestataireProfile, $sireneClient);

                return [
                    'response' => null,
                    'companyVerificationPreview' => $preview,
                    'openCompanyVerificationModal' => true,
                ];
            } catch (\Throwable $e) {
                $this->addFlash('danger', $e->getMessage());

                return [
                    'response' => $this->redirectToRoute('app_prestataire_settings', ['tab' => 'company']),
                    'companyVerificationPreview' => null,
                    'openCompanyVerificationModal' => false,
                ];
            }
        }

        $entityManager->persist($prestataireProfile);
        $entityManager->flush();

        $this->addFlash('success', 'Les informations de l’entreprise ont été enregistrées.');

        return [
            'response' => $this->redirectToRoute('app_prestataire_settings', ['tab' => 'company']),
            'companyVerificationPreview' => null,
            'openCompanyVerificationModal' => false,
        ];
    }
    // #endregion

    // #region disponibilités
    private function handleAvailabilityForm(
        EntityManagerInterface $entityManager,
        FormInterface $availabilityForm,
        PrestataireProfile $prestataireProfile,
    ): ?Response {
        if (!$availabilityForm->isSubmitted() || !$availabilityForm->isValid()) {
            return null;
        }

        $entityManager->persist($prestataireProfile);
        $entityManager->flush();

        $this->addFlash('success', 'Vos disponibilités ont bien été enregistrées.');

        return $this->redirectToRoute('app_prestataire_settings', [
            'tab' => 'dispo',
        ]);
    }
    // #endregion

    // #region Slug
    private function updatePrestataireSlug(PrestataireProfile $prestataireProfile): void
    {
        if ($prestataireProfile->getCompanyName()) {
            $prestataireProfile->setSlug(
                mb_strtolower(str_replace(' ', '-', $prestataireProfile->getCompanyName()))
            );
        }
    }
    // #endregion

    // #region application du pre remplissage
    private function applyAcceptedCompanyVerification(
        Request $request,
        EntityManagerInterface $entityManager,
        PrestataireProfile $prestataireProfile,
    ): Response {
        $previewPayload = $request->getSession()->get('company_verification_preview');

        if (!is_array($previewPayload) || empty($previewPayload['fields'])) {
            $this->addFlash('warning', 'La prévisualisation a expiré. Veuillez relancer la vérification.');

            return $this->redirectToRoute('app_prestataire_settings', ['tab' => 'company']);
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

        $this->updatePrestataireSlug($prestataireProfile);

        $isVerified = !empty($previewPayload['isVerified']);
        $isActive = !empty($previewPayload['isActive']);

        if ($isVerified) {
            $prestataireProfile->setVerificationStatus(VerificationStatusEnum::COMPANY_VERIFIED);
        }

        if ($isVerified && $isActive) {
            $prestataireProfile->setProfileStatus(PrestataireProfileStatusEnum::ACTIVE);
        }

        if ($isVerified && !$isActive) {
            $prestataireProfile->setProfileStatus(PrestataireProfileStatusEnum::PENDING_VALIDATION);
            $this->addFlash('warning', 'Le SIRET a bien été trouvé, mais l’établissement est indiqué comme fermé dans Sirene. Le profil n’a pas été activé automatiquement.');
        }

        $entityManager->persist($prestataireProfile);
        $entityManager->flush();

        $request->getSession()->remove('company_verification_preview');

        $this->addFlash('success', 'Les informations officielles de l’entreprise ont été injectées dans votre fiche.');

        return $this->redirectToRoute('app_prestataire_settings', ['tab' => 'company']);
    }
    // #endregion

    // #region construction de la preview
    private function buildCompanyVerificationPreview(
        Request $request,
        PrestataireProfile $prestataireProfile,
        SireneClient $sireneClient,
    ): array {
        $siret = $prestataireProfile->getSiret();

        if (!$siret) {
            throw new \RuntimeException('Veuillez renseigner un numéro SIRET avant de lancer la vérification.');
        }

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

        return $companyVerificationPreview;
    }
    // #endregion

    // #region construction de la cartes des zones
    private function buildZoneMap(iterable $zones): ?Map
    {
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

        if (null === $firstMappableZone) {
            return null;
        }

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

        return $zoneMap;
    }
    #endregion
    #endregion


    // #region services
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
    #endregion

    // #region Suppression d'un service
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
    #endregion

    // #region Edition d'un service
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
    #endregion

    // #region Suppression d'un document
    #[Route('/prestataire/document/{id}/supprimer', name: 'app_prestataire_document_delete', methods: ['POST'])]
    public function deleteDocument(
        Request $request,
        PrestataireDocument $document,
        EntityManagerInterface $entityManager,
    ): Response {
    // utilisateur connecté
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // profil prestataire courant
        $prestataireProfile = $user->getPrestataireProfile();

        if (!$prestataireProfile) {
            $this->addFlash('danger', 'Profil prestataire introuvable.');

            return $this->redirectToRoute('app_prestataire_settings', [
                '_fragment' => 'company-panel',
            ]);
        }

        // sécurité : le document doit appartenir au prestataire connecté
        if ($document->getPrestataireProfile()?->getId() !== $prestataireProfile->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce document.');
        }

        // validation CSRF
        if (!$this->isCsrfTokenValid('delete_document_' . $document->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Le jeton CSRF est invalide. Veuillez réessayer.');

            return $this->redirectToRoute('app_prestataire_settings', [
                '_fragment' => 'company-panel',
            ]);
        }

        // suppression document
        $entityManager->remove($document);
        $entityManager->flush();

        $this->addFlash('success', 'Le document a bien été supprimé.');

        return $this->redirectToRoute('app_prestataire_settings', [
            '_fragment' => 'company-panel',
        ]);
    }
    #endregion


    // #region PARAMETRES PROFILE CLIENT

    #region Affichage
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
    #endregion

    #region Affichage des favoris
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
    #endregion

    // #endregion


}
