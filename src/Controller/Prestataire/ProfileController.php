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

namespace App\Controller\Prestataire;

use App\Controller\AbstractProfileController;
use App\Entity\PrestataireDocument;
use App\Entity\PrestataireProfile;
use App\Entity\PrestataireService;
use App\Entity\User;
use App\Form\PrestataireServiceType;
use App\Repository\ServiceCategoryRepository;
use App\Repository\ServiceRepository;
use App\Service\AccountSecurityManager;
use App\Service\CompanyVerificationManager;
use App\Service\PrestataireAvailabilityManager;
use App\Service\PrestataireProfileCompletionService;
use App\Service\PrestataireProfileManager;
use App\Service\PrestataireSearchIndexer;
use App\Service\PrestataireSettingsFormsFactory;
use App\Service\SireneClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractProfileController
{
    public function __construct(
        private readonly PrestataireProfileManager $prestataireProfileManager,
        private readonly PrestataireAvailabilityManager $prestataireAvailabilityManager,
        private readonly PrestataireProfileCompletionService $prestataireProfileCompletionService,
        private readonly CompanyVerificationManager $companyVerificationManager,
        private readonly PrestataireSettingsFormsFactory $prestataireSettingsFormsFactory,
        AccountSecurityManager $accountSecurityManager,
        private readonly PrestataireSearchIndexer $prestataireSearchIndexer,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
    ) {
        parent::__construct($accountSecurityManager, $tokenStorage, $requestStack);
    }

    #[Route('/prestataire/parametres', name: 'app_prestataire_settings')]
    #[IsGranted('ROLE_PRESTATAIRE')]
    public function settings(
        Request $request,
        EntityManagerInterface $entityManager,
        ServiceCategoryRepository $categoryRepository,
        SireneClient $sireneClient,
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $prestataireProfile = $this->prestataireProfileManager->getOrCreateProfile($user);
        $this->prestataireProfileManager->ensureDefaultAvailabilities($prestataireProfile);

        $availabilities = $this->prestataireProfileManager->getSortedAvailabilities($prestataireProfile);
        $documents = $this->prestataireProfileManager->getSortedDocuments($prestataireProfile);
        $zones = $prestataireProfile->getPrestataireInterventionZones();

        $forms = $this->prestataireSettingsFormsFactory->create($user, $prestataireProfile);

        $forms->userForm->handleRequest($request);
        $forms->publicProfileForm->handleRequest($request);
        $forms->companyForm->handleRequest($request);
        $forms->availabilityForm->handleRequest($request);
        $forms->notificationForm->handleRequest($request);
        $forms->documentForm->handleRequest($request);
        $forms->passwordForm->handleRequest($request);
        $forms->deletionForm->handleRequest($request);

        $companyVerificationPreview = null;
        $openCompanyVerificationModal = false;
        $activeTab = $request->query->get('tab', 'profile');

        if ($response = $this->handleDocumentForm(
            entityManager: $entityManager,
            prestataireProfile: $prestataireProfile,
            documentForm: $forms->documentForm,
            document: $forms->documentEntity,
        )) {
            return $response;
        }

        if ($response = $this->handleUserForm(
            entityManager: $entityManager,
            userForm: $forms->userForm,
            user: $user,
            prestataireProfile: $prestataireProfile,
        )) {
            return $response;
        }

        if ($response = $this->handlePublicProfileForm(
            entityManager: $entityManager,
            publicProfileForm: $forms->publicProfileForm,
            prestataireProfile: $prestataireProfile,
        )) {
            return $response;
        }

        $companyResult = $this->handleCompanyForm(
            request: $request,
            entityManager: $entityManager,
            companyForm: $forms->companyForm,
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
            availabilityForm: $forms->availabilityForm,
            prestataireProfile: $prestataireProfile,
        )) {
            return $response;
        }

        if ($response = $this->handleNotificationForm(
            entityManager: $entityManager,
            notificationForm: $forms->notificationForm,
            user: $user,
            redirectRoute: 'app_prestataire_settings',
        )) {
            return $response;
        }

        if ($response = $this->handlePasswordForm(
            entityManager: $entityManager,
            passwordForm: $forms->passwordForm,
            user: $user,
            redirectRoute: 'app_prestataire_settings',
        )) {
            return $response;
        }

        if ($response = $this->handleDeletionForm(
            entityManager: $entityManager,
            deletionForm: $forms->deletionForm,
            user: $user,
        )) {
            return $response;
        }

        $zoneMap = $this->prestataireProfileManager->buildZoneMap($zones);

        return $this->render('profile/prestataire_profile.html.twig', [
            'userForm' => $forms->userForm->createView(),
            'publicProfileForm' => $forms->publicProfileForm->createView(),
            'companyForm' => $forms->companyForm->createView(),
            'zoneForm' => $forms->zoneForm->createView(),
            'zones' => $zones,
            'user' => $user,
            'categories' => $categoryRepository->findWithSubCategories(),
            'zoneMap' => $zoneMap,
            'availabilityForm' => $forms->availabilityForm->createView(),
            'availabilities' => $availabilities,
            'notificationForm' => $forms->notificationForm->createView(),
            'companyVerificationPreview' => $companyVerificationPreview,
            'openCompanyVerificationModal' => $openCompanyVerificationModal,
            'documentForm' => $forms->documentForm->createView(),
            'documents' => $documents,
            'passwordForm' => $forms->passwordForm->createView(),
            'deletionForm' => $forms->deletionForm->createView(),
            'activeTab' => $this->resolveActiveTab(
                defaultTab: $activeTab,
                availabilityForm: $forms->availabilityForm,
                notificationForm: $forms->notificationForm,
                passwordForm: $forms->passwordForm,
                deletionForm: $forms->deletionForm,
            ),
        ]);
    }

    private function handleDocumentForm(
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
            $this->prestataireProfileCompletionService->syncCompletionScore($prestataireProfile->getAccount(), $prestataireProfile);

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

    private function handleUserForm(
        EntityManagerInterface $entityManager,
        FormInterface $userForm,
        User $user,
        PrestataireProfile $prestataireProfile,
    ): ?Response {
        if (!$userForm->isSubmitted() || !$userForm->isValid()) {
            return null;
        }

        $this->prestataireProfileCompletionService->syncCompletionScore($user, $prestataireProfile);

        $entityManager->persist($user);
        $entityManager->persist($prestataireProfile);
        $entityManager->flush();

        $this->addFlash('success', 'Vos informations personnelles ont été enregistrées.');

        return $this->redirectToRoute('app_prestataire_settings', [
            'tab' => 'profile',
        ]);
    }

    private function handlePublicProfileForm(
        EntityManagerInterface $entityManager,
        FormInterface $publicProfileForm,
        PrestataireProfile $prestataireProfile,
    ): ?Response {
        if (!$publicProfileForm->isSubmitted() || !$publicProfileForm->isValid()) {
            return null;
        }

        $this->prestataireProfileManager->syncSlug($prestataireProfile);
        $this->prestataireProfileCompletionService->syncCompletionScore($prestataireProfile->getAccount(), $prestataireProfile);

        $entityManager->persist($prestataireProfile);
        $entityManager->flush();
        $this->reindexPrestataireSearchProfile($prestataireProfile);

        $this->addFlash('success', 'Votre profil public a été enregistré.');

        return $this->redirectToRoute('app_prestataire_settings', [
            'tab' => 'profile',
        ]);
    }

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

        $this->prestataireProfileManager->syncSlug($prestataireProfile);

        $companyFormData = $request->request->all('company_form');

        $isVerifyCompanyAction = is_array($companyFormData) && array_key_exists('verifyCompany', $companyFormData);
        $isAcceptCompanyVerificationAction = $request->request->has('acceptCompanyVerification');
        $isRejectCompanyVerificationAction = $request->request->has('rejectCompanyVerification');

        if ($isRejectCompanyVerificationAction) {
            $this->companyVerificationManager->clearPreview();
            $this->addFlash('info', 'Le pré-remplissage automatique a été refusé.');

            return [
                'response' => $this->redirectToRoute('app_prestataire_settings', ['tab' => 'company']),
                'companyVerificationPreview' => null,
                'openCompanyVerificationModal' => false,
            ];
        }

        if ($isAcceptCompanyVerificationAction) {
            try {
                $result = $this->companyVerificationManager->applyAcceptedPreview($prestataireProfile);
            } catch (\RuntimeException $e) {
                $this->addFlash('warning', $e->getMessage());

                return [
                    'response' => $this->redirectToRoute('app_prestataire_settings', ['tab' => 'company']),
                    'companyVerificationPreview' => null,
                    'openCompanyVerificationModal' => false,
                ];
            }

            $this->prestataireProfileCompletionService->syncCompletionScore($prestataireProfile->getAccount(), $prestataireProfile);
            $entityManager->persist($prestataireProfile);
            $entityManager->flush();

            if ($result['isVerified'] && !$result['isActive']) {
                $this->addFlash('warning', 'Le SIRET a bien été trouvé, mais l’établissement est indiqué comme fermé dans Sirene. Le profil n’a pas été activé automatiquement.');
            }

            if ($result['isVerified'] && $result['isActive']) {
                $this->reindexPrestataireSearchProfile($prestataireProfile);
            }

            $this->addFlash('success', 'Les informations officielles de l’entreprise ont été injectées dans votre fiche.');

            return [
                'response' => $this->redirectToRoute('app_prestataire_settings', ['tab' => 'company']),
                'companyVerificationPreview' => null,
                'openCompanyVerificationModal' => false,
            ];
        }

        if ($isVerifyCompanyAction) {
            try {
                $preview = $this->companyVerificationManager->buildPreview($prestataireProfile, $sireneClient);

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

        $this->prestataireProfileCompletionService->syncCompletionScore($prestataireProfile->getAccount(), $prestataireProfile);
        $entityManager->persist($prestataireProfile);
        $entityManager->flush();
        $this->reindexPrestataireSearchProfile($prestataireProfile);

        $this->addFlash('success', 'Les informations de l’entreprise ont été enregistrées.');

        return [
            'response' => $this->redirectToRoute('app_prestataire_settings', ['tab' => 'company']),
            'companyVerificationPreview' => null,
            'openCompanyVerificationModal' => false,
        ];
    }

    private function handleAvailabilityForm(
        EntityManagerInterface $entityManager,
        FormInterface $availabilityForm,
        PrestataireProfile $prestataireProfile,
    ): ?Response {
        if (!$availabilityForm->isSubmitted() || !$availabilityForm->isValid()) {
            return null;
        }

        $this->prestataireAvailabilityManager->prepareForPersistence($prestataireProfile);
        $this->prestataireProfileCompletionService->syncCompletionScore($prestataireProfile->getAccount(), $prestataireProfile);

        $entityManager->persist($prestataireProfile);
        $entityManager->flush();

        $this->addFlash(
            'success',
            $prestataireProfile->isOnVacation()
                ? 'Votre statut "En vacances" est activé. Vos horaires restent enregistrés.'
                : 'Vos disponibilités ont bien été enregistrées.'
        );

        return $this->redirectToRoute('app_prestataire_settings', [
            'tab' => 'dispo',
        ]);
    }

    private function reindexPrestataireSearchProfile(PrestataireProfile $prestataireProfile): void
    {
        if (
            $prestataireProfile->getProfileStatus()?->value !== 'ACTIVE'
            || $prestataireProfile->getVerificationStatus()?->value !== 'COMPANY_VERIFIED'
        ) {
            return;
        }

        try {
            $this->prestataireSearchIndexer->indexProfile($prestataireProfile);
        } catch (\Throwable) {
            $this->addFlash('warning', 'L’entreprise a bien été enregistrée, mais la mise en visibilité peut prendre quelques instants.');
        }
    }

    #[Route('/prestataire/service/ajouter', name: 'app_prestataire_add_service', methods: ['POST'])]
    #[IsGranted('ROLE_PRESTATAIRE')]
    public function addService(
        Request $request,
        EntityManagerInterface $em,
        ServiceRepository $serviceRepo,
        SluggerInterface $slugger,
    ): Response {
        if (!$this->isCsrfTokenValid('add_service', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Le jeton CSRF est invalide. Veuillez réessayer.');

            return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'services-panel']);
        }

        $serviceId = $request->request->get('service_id');
        $user = $this->getUser();
        $service = $serviceRepo->find($serviceId);

        if (!$service || !($user instanceof User) || !$user->getPrestataireProfile()) {
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
            $this->prestataireProfileCompletionService->syncCompletionScore($user, $user->getPrestataireProfile());
            $em->persist($user->getPrestataireProfile());
            $em->flush();

            $this->addFlash('success', 'Service ajouté !');
        }

        return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'services-panel']);
    }

    #[Route('/prestataire/service/supprimer/{id}', name: 'app_prestataire_service_delete', methods: ['POST'])]
    #[IsGranted('ROLE_PRESTATAIRE')]
    public function delete(Request $request, PrestataireService $ps, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (
            !$user instanceof User
            || !$user->getPrestataireProfile()
            || $ps->getPrestataire() !== $user->getPrestataireProfile()
        ) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $ps->getId(), $request->request->get('_token'))) {
            $em->remove($ps);
            $this->prestataireProfileCompletionService->syncCompletionScore($user, $user->getPrestataireProfile());
            $em->persist($user->getPrestataireProfile());
            $em->flush();
            $this->addFlash('success', 'Le service a bien été retiré de votre profil.');
        }

        return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'services-panel']);
    }

    #[Route('/prestataire/service/editer/{id}', name: 'app_prestataire_service_edit')]
    #[IsGranted('ROLE_PRESTATAIRE')]
    public function edit(Request $request, PrestataireService $ps, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (
            !$user instanceof User
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

            $this->prestataireProfileCompletionService->syncCompletionScore($user, $user->getPrestataireProfile());
            $em->persist($user->getPrestataireProfile());
            $em->flush();

            $this->addFlash('success', 'Tarifs mis à jour !');

            return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'services-panel']);
        }

        return $this->render('prestataire/edit_service.html.twig', [
            'form' => $form->createView(),
            'ps' => $ps,
        ]);
    }

    #[Route('/prestataire/document/{id}/supprimer', name: 'app_prestataire_document_delete', methods: ['POST'])]
    #[IsGranted('ROLE_PRESTATAIRE')]
    public function deleteDocument(
        Request $request,
        PrestataireDocument $document,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $prestataireProfile = $user->getPrestataireProfile();

        if (!$prestataireProfile) {
            $this->addFlash('danger', 'Profil prestataire introuvable.');

            return $this->redirectToRoute('app_prestataire_settings', [
                '_fragment' => 'company-panel',
            ]);
        }

        if ($document->getPrestataireProfile()?->getId() !== $prestataireProfile->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce document.');
        }

        if (!$this->isCsrfTokenValid('delete_document_' . $document->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Le jeton CSRF est invalide. Veuillez réessayer.');

            return $this->redirectToRoute('app_prestataire_settings', [
                '_fragment' => 'company-panel',
            ]);
        }

        $prestataireProfile->removeDocument($document);
        $this->prestataireProfileCompletionService->syncCompletionScore($user, $prestataireProfile);
        $entityManager->remove($document);
        $entityManager->persist($prestataireProfile);
        $entityManager->flush();

        $this->addFlash('success', 'Le document a bien été supprimé.');

        return $this->redirectToRoute('app_prestataire_settings', [
            '_fragment' => 'company-panel',
        ]);
    }
}
