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

use App\Dto\PrestataireSettingsForms;
use App\Entity\ClientProfile;
use App\Entity\PrestataireDocument;
use App\Entity\PrestataireProfile;
use App\Entity\PrestataireService;
use App\Entity\User;
use App\Enum\FavoriteTypeEnum;
use App\Form\AccountSettingsType;
use App\Form\AccountDeletionType;
use App\Form\AccountPasswordChangeType;
use App\Form\PrestataireServiceType;
use App\Repository\FavoriteRepository;
use App\Repository\PrestataireProfileRepository;
use App\Repository\PrestataireServiceRepository;
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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;


/**
 * Gère les actions liées à profile.
 */
class ProfileController extends AbstractController
{
    public function __construct(
        private readonly PrestataireProfileManager $prestataireProfileManager,
        private readonly PrestataireAvailabilityManager $prestataireAvailabilityManager,
        private readonly PrestataireProfileCompletionService $prestataireProfileCompletionService,
        private readonly CompanyVerificationManager $companyVerificationManager,
        private readonly PrestataireSettingsFormsFactory $prestataireSettingsFormsFactory,
        private readonly AccountSecurityManager $accountSecurityManager,
        private readonly PrestataireSearchIndexer $prestataireSearchIndexer,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RequestStack $requestStack,
    ) {
    }

    // #region Settings
    #[Route('/prestataire/parametres', name: 'app_prestataire_settings')]
    /**
     * Affiche et traite les paramètres associés à ce contrôleur.
     *
     * @return Response
     */
    public function settings(
        Request $request,
        EntityManagerInterface $entityManager,
        ServiceCategoryRepository $categoryRepository,
        SireneClient $sireneClient,
    ): Response {
        /** @var \App\Entity\User|null $user */
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
            request: $request,
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
    // #endregion

    // #region Utilisateur
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
    // #endregion

    // #region notifications
    private function handleNotificationForm(
        EntityManagerInterface $entityManager,
        FormInterface $notificationForm,
        User $user,
    ): ?Response {
        if (!$notificationForm->isSubmitted() || !$notificationForm->isValid()) {
            return null;
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Vos préférences de notifications ont bien été enregistrées.');

        return $this->redirectToRoute('app_prestataire_settings', [
            'tab' => 'notif',
        ]);
    }
    // #endregion

    // #region sécurité
    private function handlePasswordForm(
        EntityManagerInterface $entityManager,
        FormInterface $passwordForm,
        User $user,
        string $redirectRoute,
    ): ?Response {
        if (!$passwordForm->isSubmitted() || !$passwordForm->isValid()) {
            return null;
        }

        $plainPassword = $passwordForm->get('plainPassword')->getData();
        \assert(\is_string($plainPassword));

        $this->accountSecurityManager->changePassword($user, $plainPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Votre mot de passe a bien été mis à jour.');

        return $this->redirectToRoute($redirectRoute, [
            'tab' => 'security',
        ]);
    }

    private function handleDeletionForm(
        EntityManagerInterface $entityManager,
        FormInterface $deletionForm,
        User $user,
    ): ?Response {
        if (!$deletionForm->isSubmitted() || !$deletionForm->isValid()) {
            return null;
        }

        $this->accountSecurityManager->softDelete($user);

        $entityManager->persist($user);
        $entityManager->flush();

        $this->tokenStorage->setToken(null);

        $request = $this->requestStack->getCurrentRequest();
        if ($request?->hasSession()) {
            $request->getSession()->invalidate();
        }

        $response = new RedirectResponse($this->generateUrl('app_home'));
        $response->headers->clearCookie('REMEMBERME', '/');

        if (\function_exists('session_name')) {
            $sessionCookieName = session_name();
            if (\is_string($sessionCookieName) && '' !== $sessionCookieName) {
                $response->headers->clearCookie($sessionCookieName, '/');
            }
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');

        return $response;
    }

    private function resolveActiveTab(
        string $defaultTab,
        ?FormInterface $availabilityForm,
        ?FormInterface $notificationForm,
        FormInterface $passwordForm,
        FormInterface $deletionForm,
    ): string {
        if (null !== $availabilityForm && $availabilityForm->isSubmitted() && !$availabilityForm->isValid()) {
            return 'dispo';
        }

        if (null !== $notificationForm && $notificationForm->isSubmitted() && !$notificationForm->isValid()) {
            return 'notif';
        }

        if (($passwordForm->isSubmitted() && !$passwordForm->isValid()) || ($deletionForm->isSubmitted() && !$deletionForm->isValid())) {
            return 'security';
        }

        return $defaultTab;
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
    // #endregion

    // #region services
    #[Route('/prestataire/service/ajouter', name: 'app_prestataire_add_service', methods: ['POST'])]
    /**
     * Traite l’action "addService" du contrôleur Profile.
     *
     * @return Response
     */
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
            $this->prestataireProfileCompletionService->syncCompletionScore($user, $user->getPrestataireProfile());
            $em->persist($user->getPrestataireProfile());
            $em->flush();

            $this->addFlash('success', 'Service ajouté !');
        }

        return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'services-panel']);
    }
    #endregion

    // #region Suppression d'un service
    #[Route('/prestataire/service/supprimer/{id}', name: 'app_prestataire_service_delete', methods: ['POST'])]
    /**
     * Supprime la ressource demandée.
     *
     * @return Response
     */
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
            $this->prestataireProfileCompletionService->syncCompletionScore($user, $user->getPrestataireProfile());
            $em->persist($user->getPrestataireProfile());
            $em->flush();
            $this->addFlash('success', 'Le service a bien été retiré de votre profil.');
        }

        // On redirige vers la route nommée avec l'ancre
        return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'services-panel']);
    }
    #endregion

    // #region Edition d'un service
    #[Route('/prestataire/service/editer/{id}', name: 'app_prestataire_service_edit')]
    /**
     * Affiche et traite le formulaire de modification.
     *
     * @return Response
     */
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
    #endregion

    // #region Suppression d'un document
    #[Route('/prestataire/document/{id}/supprimer', name: 'app_prestataire_document_delete', methods: ['POST'])]
    /**
     * Traite l’action "deleteDocument" du contrôleur Profile.
     *
     * @return Response
     */
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
    #endregion


    // #region PARAMETRES PROFILE CLIENT

    #region Affichage
    #[Route('/client/parametres', name: 'app_client_settings')]
    /**
     * Traite l’action "clientSettings" du contrôleur Profile.
     *
     * @return Response
     */
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
        $passwordForm = $this->createForm(AccountPasswordChangeType::class, null, [
            'action' => $this->generateUrl('app_client_settings'),
            'method' => 'POST',
        ]);
        $deletionForm = $this->createForm(AccountDeletionType::class, null, [
            'action' => $this->generateUrl('app_client_settings'),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);
        $passwordForm->handleRequest($request);
        $deletionForm->handleRequest($request);
        $activeTab = $request->query->get('tab', 'personal');

        // 3. Traitement de la soumission
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil client a été mis à jour avec succès !');

            return $this->redirectToRoute('app_client_settings');
        }

        if ($response = $this->handlePasswordForm(
            entityManager: $entityManager,
            passwordForm: $passwordForm,
            user: $user,
            redirectRoute: 'app_client_settings',
        )) {
            return $response;
        }

        if ($response = $this->handleDeletionForm(
            entityManager: $entityManager,
            deletionForm: $deletionForm,
            user: $user,
        )) {
            return $response;
        }

        // 4. Envoi à la vue client dédiée
        return $this->render('profile/client_profile.html.twig', [
            'settingsForm' => $form->createView(),
            'user' => $user,
            'passwordForm' => $passwordForm->createView(),
            'deletionForm' => $deletionForm->createView(),
            'activeTab' => $this->resolveActiveTab(
                defaultTab: $activeTab,
                availabilityForm: null,
                notificationForm: null,
                passwordForm: $passwordForm,
                deletionForm: $deletionForm,
            ),
        ]);
    }
    #endregion

    #region Affichage des favoris
    #[Route('/client/parametres/favoris', name: 'app_client_settings_favorites', methods: ['GET'])]
    /**
     * Traite l’action "clientFavorites" du contrôleur Profile.
     *
     * @return Response
     */
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
