<?php

namespace App\Controller\Prestataire;

use App\Entity\Conversation;
use App\Entity\PrestataireRevenueEntry;
use App\Entity\Message;
use App\Entity\MessageAttachment;
use App\Entity\PrestataireProfile;
use App\Entity\User;
use App\Enum\MessageTypeEnum;
use App\Enum\NotificationTypeEnum;
use App\Enum\QuoteRequestStatusEnum;
use App\Form\MessageType;
use App\Form\PrestataireRevenueEntryType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\PrestataireAppointmentRepository;
use App\Repository\PrestataireProfileRepository;
use App\Repository\PrestataireRevenueEntryRepository;
use App\Repository\PrestataireServiceRepository;
use App\Repository\QuoteRequestRepository;
use App\Repository\ReviewRepository;
use App\Service\ConversationMessageManager;
use App\Service\NotificationManager;
use App\Service\PrestataireProfileCompletionService;
use App\Service\PrestataireRevenueOverviewBuilder;
use App\Service\RealtimeAuthTokenManager;
use App\Service\RealtimeNotifier;
use App\Service\Subscription\SubscriptionAccessManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Gère les actions liées à prestataire dashboard.
 */
#[IsGranted('ROLE_PRESTATAIRE')]
final class PrestataireDashboardController extends AbstractController
{
    private const DASHBOARD_PAGE_SIZE = 10;
    private const CONVERSATION_PAGE_SIZE = 5;
    private const REVENUE_PAYOUTS_PAGE_SIZE = 7;

    public function __construct(
        private readonly PrestataireProfileCompletionService $prestataireProfileCompletionService,
        private readonly RealtimeAuthTokenManager $realtimeAuthTokenManager,
        private readonly PrestataireRevenueOverviewBuilder $prestataireRevenueOverviewBuilder,
    ) {
    }

    #[Route('/prestataire/espace-pro', name: 'app_prestataire_dashboard', methods: ['GET', 'POST'])]
    /**
     * Affiche la page principale de ce contrôleur.
     *
     * @return Response
     */
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        PrestataireProfileRepository $prestataireProfileRepository,
        PrestataireRevenueEntryRepository $prestataireRevenueEntryRepository,
        PrestataireServiceRepository $prestataireServiceRepository,
        PrestataireAppointmentRepository $prestataireAppointmentRepository,
        QuoteRequestRepository $quoteRequestRepository,
        ConversationRepository $conversationRepository,
        MessageRepository $messageRepository,
        ReviewRepository $reviewRepository,
        PaginatorInterface $paginator,
        SubscriptionAccessManager $subscriptionAccessManager,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getAuthenticatedPrestataireUser();
        $prestataireProfile = $this->getPrestataireProfile($user, $prestataireProfileRepository);
        $manualRevenueEntry = $this->resolveEditableRevenueEntry($request, $prestataireProfile, $prestataireRevenueEntryRepository);
        $isEditingRevenue = null !== $manualRevenueEntry->getId();
        $activeRevenueSubtab = $this->resolveRevenueSubtab($request);
        $manualRevenueForm = $this->createRevenueForm($manualRevenueEntry, $prestataireProfile);
        $manualRevenueForm->handleRequest($request);

        if ($manualRevenueForm->isSubmitted()) {
            if ($manualRevenueForm->isValid()) {
                $manualRevenueEntry->setPrestataire($prestataireProfile);

                if (
                    null === $manualRevenueEntry->getServiceLabel()
                    && $manualRevenueEntry->getPrestataireService() !== null
                ) {
                    $manualRevenueEntry->setServiceLabel($manualRevenueEntry->getPrestataireService()?->getDisplayTitle());
                }

                $entityManager->persist($manualRevenueEntry);
                $entityManager->flush();

                $this->addFlash('success', $isEditingRevenue
                    ? 'Le revenu externe a bien été mis à jour.'
                    : 'Le revenu externe a bien été ajouté.');

                return $this->redirectToRoute('app_prestataire_dashboard', [
                    'tab' => 'revenus',
                    'revenues_subtab' => $activeRevenueSubtab,
                    '_fragment' => 'revenus-main-panel',
                ], 303);
            }

            return $this->render(
                'prestataire/dashboard/prestataire_dashboard.html.twig',
                $this->buildDashboardViewData(
                    request: $request,
                    entityManager: $entityManager,
                    user: $user,
                    prestataireProfile: $prestataireProfile,
                    prestataireServiceRepository: $prestataireServiceRepository,
                    prestataireAppointmentRepository: $prestataireAppointmentRepository,
                    quoteRequestRepository: $quoteRequestRepository,
                    conversationRepository: $conversationRepository,
                    messageRepository: $messageRepository,
                    reviewRepository: $reviewRepository,
                    paginator: $paginator,
                    subscriptionAccessManager: $subscriptionAccessManager,
                    forcedActiveTab: 'revenus',
                    revenueFormView: $manualRevenueForm->createView(),
                    revenueFormEntry: $manualRevenueEntry,
                ),
                new Response('', Response::HTTP_UNPROCESSABLE_ENTITY)
            );
        }

        return $this->render('prestataire/dashboard/prestataire_dashboard.html.twig', $this->buildDashboardViewData(
            request: $request,
            entityManager: $entityManager,
            user: $user,
            prestataireProfile: $prestataireProfile,
            prestataireServiceRepository: $prestataireServiceRepository,
            prestataireAppointmentRepository: $prestataireAppointmentRepository,
            quoteRequestRepository: $quoteRequestRepository,
            conversationRepository: $conversationRepository,
            messageRepository: $messageRepository,
            reviewRepository: $reviewRepository,
            paginator: $paginator,
            subscriptionAccessManager: $subscriptionAccessManager,
            revenueFormView: $manualRevenueForm->createView(),
            revenueFormEntry: $manualRevenueEntry,
        ));
    }

    #[Route('/prestataire/espace-pro/conversation/{id}/message', name: 'app_prestataire_conversation_message_send', methods: ['POST'])]
    /**
     * Traite l’action "sendMessage" du contrôleur Prestataire Dashboard.
     *
     * @return Response
     */
    public function sendMessage(
        #[MapEntity(id: 'id')] Conversation $conversation,
        Request $request,
        EntityManagerInterface $entityManager,
        RealtimeNotifier $realtimeNotifier,
        NotificationManager $notificationManager,
        QuoteRequestRepository $quoteRequestRepository,
        ConversationRepository $conversationRepository,
        MessageRepository $messageRepository,
        PrestataireServiceRepository $prestataireServiceRepository,
        PrestataireAppointmentRepository $prestataireAppointmentRepository,
        ReviewRepository $reviewRepository,
        ConversationMessageManager $conversationMessageManager,
        PaginatorInterface $paginator,
        SubscriptionAccessManager $subscriptionAccessManager,
    ): Response {
        $user = $this->getAuthenticatedPrestataireUser();
        $prestataireProfile = $user->getPrestataireProfile();

        if ($conversation->getPrestataire()?->getId() !== $prestataireProfile->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas répondre à cette conversation.');
        }

        if (!$subscriptionAccessManager->canUseInstantMessaging($prestataireProfile)) {
            if ($this->isAsyncConversationSubmit($request)) {
                return new JsonResponse([
                    'ok' => false,
                    'message' => 'La messagerie instantanee n’est pas incluse dans votre formule actuelle.',
                ], Response::HTTP_FORBIDDEN);
            }

            $this->addFlash('warning', 'La messagerie instantanée n’est pas incluse dans votre formule actuelle.');

            return $this->redirectToRoute('app_prestataire_dashboard', [
                'conversation' => $conversation->getId(),
                'tab' => 'messages',
                'scroll' => $this->resolveReturnScroll($request),
            ], 303);
        }

        $quoteRequest = $conversation->getQuoteRequest();
        $isConversationArchived = $quoteRequest instanceof \App\Entity\QuoteRequest
            && ($quoteRequest->isArchivedByClient() || $quoteRequest->isArchivedByPrestataire());

        if ($isConversationArchived) {
            if ($this->isAsyncConversationSubmit($request)) {
                return new JsonResponse([
                    'ok' => false,
                    'message' => 'La messagerie est cloturee car cette demande a ete archivee.',
                ], Response::HTTP_FORBIDDEN);
            }

            $this->addFlash('warning', 'La messagerie est clôturée car cette demande a été archivée.');

            return $this->redirectToRoute('app_prestataire_dashboard', [
                'conversation' => $conversation->getId(),
                'tab' => 'messages',
                'scroll' => $this->resolveReturnScroll($request),
            ], 303);
        }

        $message = new Message();
        $form = $this->createMessageForm($conversation, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<\Symfony\Component\HttpFoundation\File\UploadedFile>|null $uploadedFiles */
            $uploadedFiles = $form->get('attachments')->getData();
            $uploadedFiles = is_array($uploadedFiles) ? $uploadedFiles : [];

            $message->setConversation($conversation);

            $isPrepared = $conversationMessageManager->prepareMessage(
                $message,
                $user,
                $message->getContent(),
                $uploadedFiles,
                MessageTypeEnum::USER,
            );

            if (!$isPrepared) {
                if ($this->isAsyncConversationSubmit($request)) {
                    return new JsonResponse([
                        'ok' => false,
                        'message' => 'Vous devez saisir un message ou ajouter au moins une photo.',
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $this->addFlash('danger', 'Vous devez saisir un message ou ajouter au moins une photo.');

                return $this->redirectToRoute('app_prestataire_dashboard', [
                    'conversation' => $conversation->getId(),
                    'tab' => 'messages',
                    'scroll' => $this->resolveReturnScroll($request),
                ], 303);
            }

            if (method_exists($conversation, 'setLastMessageAt')) {
                $conversation->setLastMessageAt(new \DateTimeImmutable());
            }

            if (method_exists($conversation, 'setUpdatedAt')) {
                $conversation->setUpdatedAt(new \DateTimeImmutable());
            }

            $entityManager->persist($message);
            $entityManager->flush();
            $realtimeNotifier->notifyMessageCreated($conversation->getId(), $message);
            $clientUser = $conversation->getClient()?->getAccount();

            if ($clientUser instanceof User && $clientUser->getId() !== $user->getId()) {
                $notificationManager->notify(
                    $clientUser,
                    NotificationTypeEnum::MESSAGE_RECEIVED,
                    'Nouveau message',
                    'Vous avez reçu un nouveau message de la part d’un prestataire.',
                    $this->generateUrl('app_quote_request_show', [
                        'slug' => $conversation->getQuoteRequest()?->getSlug(),
                        '_fragment' => 'quote-conversation',
                    ]),
                    [
                        'conversationId' => $conversation->getId(),
                        'messageId' => $message->getId(),
                        'quoteRequestId' => $conversation->getQuoteRequest()?->getId(),
                        'quoteRequestSlug' => $conversation->getQuoteRequest()?->getSlug(),
                        'senderId' => $user->getId(),
                    ]
                );
            }

            if ($this->isAsyncConversationSubmit($request)) {
                return new JsonResponse(['ok' => true]);
            }

            return $this->redirectToRoute('app_prestataire_dashboard', [
                'conversation' => $conversation->getId(),
                'tab' => 'messages',
                'scroll' => $this->resolveReturnScroll($request),
            ], 303);
        }

        if ($this->isAsyncConversationSubmit($request)) {
            return new JsonResponse([
                'ok' => false,
                'message' => 'Le formulaire de message contient des erreurs.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->render(
            'prestataire/dashboard/prestataire_dashboard.html.twig',
            $this->buildDashboardViewData(
                request: $request,
                entityManager: $entityManager,
                user: $user,
                prestataireProfile: $prestataireProfile,
                prestataireServiceRepository: $prestataireServiceRepository,
                prestataireAppointmentRepository: $prestataireAppointmentRepository,
                quoteRequestRepository: $quoteRequestRepository,
                conversationRepository: $conversationRepository,
                messageRepository: $messageRepository,
                reviewRepository: $reviewRepository,
                paginator: $paginator,
                subscriptionAccessManager: $subscriptionAccessManager,
                messageFormView: $form->createView(),
                forcedActiveConversation: $conversation,
                forcedActiveTab: 'messages',
            ),
            new Response('', Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }

    #[Route('/prestataire/espace-pro/conversation/{id}/photos', name: 'app_prestataire_conversation_photos', methods: ['GET'])]
    /**
     * Traite l’action "conversationPhotos" du contrôleur Prestataire Dashboard.
     *
     * @return Response
     */
    public function conversationPhotos(
        #[MapEntity(id: 'id')] Conversation $conversation,
        Request $request,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $prestataireProfile = $user->getPrestataireProfile();

        if ($conversation->getPrestataire()?->getId() !== $prestataireProfile->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder aux photos de cette conversation.');
        }

        $mediaItems = [];

        foreach ($conversation->getMessages() as $message) {
            foreach ($message->getAttachments() as $attachment) {
                if (!$attachment->getFileName()) {
                    continue;
                }

                $author = $message->getAuthor();
                $isOwner = $author && $author->getId() === $user->getId();

                $mediaItems[] = [
                    'attachment' => $attachment,
                    'message' => $message,
                    'url' => '/uploads/messages/' . $attachment->getFileName(),
                    'authorName' => $author
                        ? trim(($author->getFirstName() ?? '') . ' ' . ($author->getLastName() ?? ''))
                        : 'Système',
                    'createdAt' => $message->getCreatedAt(),
                    'canDelete' => $isOwner,
                    'backUrl' => $this->generateUrl('app_prestataire_dashboard', [
                        'conversation' => $conversation->getId(),
                        'tab' => 'messages',
                        '_fragment' => 'messages-main-panel',
                    ]),
                ];
            }
        }

        usort($mediaItems, static fn(array $a, array $b) => ($a['createdAt'] <=> $b['createdAt']));

        return $this->render('conversation/gallery.html.twig', [
            'conversation' => $conversation,
            'mediaItems' => $mediaItems,
            'backUrl' => $this->generateUrl('app_prestataire_dashboard', [
                'conversation' => $conversation->getId(),
                'tab' => 'messages',
                '_fragment' => 'messages-main-panel',
            ]),
            'deleteRouteName' => 'app_conversation_attachment_delete',
            'galleryContext' => 'prestataire',
        ]);
    }


    #[Route('/conversation/attachment/{id}/delete', name: 'app_conversation_attachment_delete', methods: ['POST'])]
    /**
     * Traite l’action "deleteAttachment" du contrôleur Prestataire Dashboard.
     *
     * @return Response
     */
    public function deleteAttachment(
        Request $request,
        MessageAttachment $attachment,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $message = $attachment->getMessage();
        $conversation = $message?->getConversation();
        $author = $message?->getAuthor();

        if (!$message || !$conversation || !$author || $author->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette photo.');
        }

        if (!$this->isCsrfTokenValid(
            'delete_attachment_' . $attachment->getId(),
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $redirect = (string) $request->request->get('redirect', '');

        $entityManager->remove($attachment);
        $entityManager->flush();

        $this->addFlash('success', 'La photo a bien été supprimée.');

        if ($redirect !== '') {
            return $this->redirect($redirect, 303);
        }

        return $this->redirectToRoute('app_home');
    }

    private function getAuthenticatedPrestataireUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if (!$this->isGranted('ROLE_PRESTATAIRE')) {
            throw $this->createAccessDeniedException('Accès réservé aux prestataires.');
        }

        if (!$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Profil prestataire introuvable.');
        }

        return $user;
    }

    private function getPrestataireProfile(User $user, PrestataireProfileRepository $prestataireProfileRepository): PrestataireProfile
    {
        $prestataireProfile = $prestataireProfileRepository->findOneBy([
            'account' => $user,
        ]);

        if (!$prestataireProfile instanceof PrestataireProfile) {
            throw $this->createAccessDeniedException('Profil prestataire introuvable.');
        }

        return $prestataireProfile;
    }

    private function buildDashboardViewData(
        Request $request,
        EntityManagerInterface $entityManager,
        User $user,
        PrestataireProfile $prestataireProfile,
        PrestataireServiceRepository $prestataireServiceRepository,
        PrestataireAppointmentRepository $prestataireAppointmentRepository,
        QuoteRequestRepository $quoteRequestRepository,
        ConversationRepository $conversationRepository,
        MessageRepository $messageRepository,
        ReviewRepository $reviewRepository,
        PaginatorInterface $paginator,
        SubscriptionAccessManager $subscriptionAccessManager,
        ?FormView $messageFormView = null,
        ?Conversation $forcedActiveConversation = null,
        ?string $forcedActiveTab = null,
        ?FormView $revenueFormView = null,
        ?PrestataireRevenueEntry $revenueFormEntry = null,
    ): array {
        $quoteSort = $this->resolveQuoteSort($request);
        $quoteOrderBy = $this->resolveQuoteOrderBy($quoteSort);
        $activeTab = $forcedActiveTab ?? (string) $request->query->get('tab', 'dashboard');
        $isDashboardTab = 'dashboard' === $activeTab;
        $isMessagesTab = 'messages' === $activeTab;
        $isRevenusTab = 'revenus' === $activeTab;
        $isPrestationsTab = 'prestations' === $activeTab;
        $isDemandesTab = 'demandes' === $activeTab;
        $isArchivesTab = 'archives' === $activeTab;

        $allConversations = [];
        $activeConversation = $forcedActiveConversation;
        $conversations = [];
        $conversationUnreadCounts = [];
        $messageFormView ??= null;

        if ($isMessagesTab) {
            $allConversations = $this->loadConversations($conversationRepository, $prestataireProfile);
            $activeConversation = $forcedActiveConversation ?? $this->resolveActiveConversation(
                $allConversations,
                $request->query->get('conversation')
            );
            $conversations = $paginator->paginate(
                $allConversations,
                $request->query->getInt('conversationPage', 1),
                self::CONVERSATION_PAGE_SIZE,
                ['pageParameterName' => 'conversationPage']
            );
            $conversationUnreadCounts = $this->buildConversationUnreadCounts($allConversations, $user);
        }

        if (!$activeConversation instanceof Conversation && $forcedActiveConversation instanceof Conversation) {
            $activeConversation = $forcedActiveConversation;
        }

        $canUseInstantMessaging = $subscriptionAccessManager->canUseInstantMessaging($prestataireProfile);
        $isConversationArchived = $activeConversation?->getQuoteRequest() instanceof \App\Entity\QuoteRequest
            && (
                $activeConversation->getQuoteRequest()->isArchivedByClient()
                || $activeConversation->getQuoteRequest()->isArchivedByPrestataire()
            );

        if (
            null === $messageFormView
            && $activeConversation instanceof Conversation
            && $canUseInstantMessaging
            && !$isConversationArchived
        ) {
            $messageFormView = $this->createMessageForm($activeConversation, new Message())->createView();
        }

        if ($isMessagesTab) {
            $this->markActiveConversationMessagesAsRead($activeConversation, $user, $entityManager);
        }

        $completionReport = null;
        $mandatoryChecklist = null;
        $currentSubscription = null;
        $recentQuoteRequests = [];
        $recentMessages = [];
        $recentReviews = [];
        $upcomingAppointments = [];
        $remainingCredits = 0;
        $priorityAlerts = [];
        $showProfileCompletionModal = false;

        if ($isDashboardTab) {
            $completionReport = $this->prestataireProfileCompletionService->buildReport($user, $prestataireProfile);
            $mandatoryChecklist = $this->prestataireProfileCompletionService->buildMandatoryChecklist($user, $prestataireProfile);
            $currentSubscription = $subscriptionAccessManager->getCurrentUsableSubscription($prestataireProfile);
            $recentQuoteRequests = $quoteRequestRepository->findRecentForPrestataireDashboard($prestataireProfile, 5);
            $recentMessages = $messageRepository->findLatestForPrestataire($prestataireProfile, 5);
            $recentReviews = $reviewRepository->findRecentForPrestataireDashboard($prestataireProfile, 5);
            $upcomingAppointments = $prestataireAppointmentRepository->findUpcomingForDashboard((int) $prestataireProfile->getId(), 3);
            $remainingCredits = $subscriptionAccessManager->getRemainingCredits($prestataireProfile);
            $priorityAlerts = $this->buildPriorityAlerts(
                prestataireProfile: $prestataireProfile,
                user: $user,
                quoteRequestRepository: $quoteRequestRepository,
                messageRepository: $messageRepository,
                currentSubscription: $currentSubscription,
                remainingCredits: $remainingCredits,
                upcomingAppointments: $upcomingAppointments,
                mandatoryChecklist: $mandatoryChecklist,
            );
            $showProfileCompletionModal = 1 === (int) ($user->getLoginCount() ?? 0) && !$mandatoryChecklist['isComplete'];
        }

        $currentDate = new \DateTimeImmutable();
        $selectedRevenueMonth = max(1, min(12, $request->query->getInt('revenues_month', (int) $currentDate->format('n'))));
        $selectedRevenueYear = $request->query->getInt('revenues_year', (int) $currentDate->format('Y'));
        $activeRevenueSubtab = $this->resolveRevenueSubtab($request);
        $revenueHistorySort = $this->resolveRevenueHistorySort($request);
        $revenueHistoryStatus = $this->resolveRevenueHistoryStatus($request);
        $revenueOverview = null;
        $revenueHistoryItems = [];
        $revenueHistory = [];
        $revenuePayouts = [];

        if ($isRevenusTab) {
            $revenueOverview = $this->prestataireRevenueOverviewBuilder->build(
                $prestataireProfile,
                $selectedRevenueMonth,
                $selectedRevenueYear,
            );
            $revenueHistoryItems = $this->buildRevenueHistoryItems(
                $revenueOverview['history'],
                $revenueHistorySort,
                $revenueHistoryStatus,
            );
            $revenueHistory = $paginator->paginate(
                $revenueHistoryItems,
                $request->query->getInt('revenuePage', 1),
                self::DASHBOARD_PAGE_SIZE,
                ['pageParameterName' => 'revenuePage']
            );
            $revenuePayouts = $paginator->paginate(
                $revenueOverview['unpaid'],
                $request->query->getInt('revenuePayoutPage', 1),
                self::REVENUE_PAYOUTS_PAGE_SIZE,
                ['pageParameterName' => 'revenuePayoutPage']
            );
        }

        if (null === $revenueFormEntry) {
            $revenueFormEntry = new PrestataireRevenueEntry();
        }

        if ($isRevenusTab && null === $revenueFormView) {
            $revenueFormView = $this->createRevenueForm($revenueFormEntry, $prestataireProfile)->createView();
        }

        $prestations = $isPrestationsTab
            ? $prestataireServiceRepository->findBy(
                ['prestataire' => $prestataireProfile],
                ['updatedAt' => 'DESC', 'createdAt' => 'DESC']
            )
            : [];

        $quoteRequests = $isDemandesTab
            ? $paginator->paginate(
                $this->createQuoteRequestsQueryBuilder($quoteRequestRepository, $prestataireProfile, false, $quoteOrderBy),
                $request->query->getInt('quotePage', 1),
                self::DASHBOARD_PAGE_SIZE,
                ['pageParameterName' => 'quotePage']
            )
            : [];

        $archivedQuoteRequests = $isArchivesTab
            ? $paginator->paginate(
                $this->createQuoteRequestsQueryBuilder($quoteRequestRepository, $prestataireProfile, true, $quoteOrderBy),
                $request->query->getInt('archivedQuotePage', 1),
                self::DASHBOARD_PAGE_SIZE,
                ['pageParameterName' => 'archivedQuotePage']
            )
            : [];

        $unreadConversationCount = $isMessagesTab
            ? $this->countUnreadConversations($conversationUnreadCounts)
            : $messageRepository->countUnreadConversationsForPrestataire($prestataireProfile, $user);

        return [
            'user' => $user,
            'prestataireProfile' => $prestataireProfile,
            'completionReport' => $completionReport,
            'mandatoryChecklist' => $mandatoryChecklist,
            'priorityAlerts' => $priorityAlerts,
            'showProfileCompletionModal' => $showProfileCompletionModal,
            'profileCompletionSettingsUrl' => \is_array($mandatoryChecklist)
                ? $this->buildSettingsUrlFromChecklist($mandatoryChecklist)
                : $this->generateUrl('app_prestataire_settings', ['tab' => 'profile']),
            'recentQuoteRequests' => $recentQuoteRequests,
            'recentMessages' => $recentMessages,
            'recentReviews' => $recentReviews,
            'upcomingAppointments' => $upcomingAppointments,
            'currentSubscription' => $currentSubscription,
            'remainingCredits' => $remainingCredits,
            'prestations' => $prestations,
            'quoteRequests' => $quoteRequests,
            'archivedQuoteRequests' => $archivedQuoteRequests,
            'quoteSort' => $quoteSort,
            'conversations' => $conversations,
            'conversationUnreadCounts' => $conversationUnreadCounts,
            'unreadConversationCount' => $unreadConversationCount,
            'activeConversation' => $activeConversation,
            'messageForm' => $messageFormView,
            'revenueForm' => $revenueFormView,
            'revenueFormEntry' => $revenueFormEntry,
            'activeRevenueSubtab' => $activeRevenueSubtab,
            'revenueHistory' => $revenueHistory,
            'revenueHistoryCount' => \count($revenueHistoryItems),
            'revenueHistorySort' => $revenueHistorySort,
            'revenueHistoryStatus' => $revenueHistoryStatus,
            'revenuePayouts' => $revenuePayouts,
            'revenueOverview' => $revenueOverview,
            'selectedRevenueMonth' => $selectedRevenueMonth,
            'selectedRevenueYear' => $selectedRevenueYear,
            'canUseInstantMessaging' => $canUseInstantMessaging,
            'isConversationArchived' => $isConversationArchived,
            'activeTab' => $activeTab,
            'hasConversationPhotos' => $isMessagesTab ? $this->conversationHasPhotos($activeConversation) : false,
            'realtimeConversationToken' => $activeConversation instanceof Conversation
                ? $this->realtimeAuthTokenManager->createConversationToken($activeConversation->getId(), $user)
                : null,
        ];
    }

    private function createRevenueForm(
        PrestataireRevenueEntry $entry,
        PrestataireProfile $prestataireProfile,
    ): FormInterface {
        $routeParameters = [
            'tab' => 'revenus',
            '_fragment' => 'revenus-main-panel',
        ];

        if (null !== $entry->getId()) {
            $routeParameters['edit_revenue'] = $entry->getId();
        }

        return $this->createForm(PrestataireRevenueEntryType::class, $entry, [
            'prestataire' => $prestataireProfile,
            'action' => $this->generateUrl('app_prestataire_dashboard', $routeParameters),
            'method' => 'POST',
        ]);
    }

    private function resolveEditableRevenueEntry(
        Request $request,
        PrestataireProfile $prestataireProfile,
        PrestataireRevenueEntryRepository $prestataireRevenueEntryRepository,
    ): PrestataireRevenueEntry {
        $entryId = $request->query->get('edit_revenue');

        if (!\is_string($entryId) && !\is_numeric($entryId)) {
            return new PrestataireRevenueEntry();
        }

        $entry = $prestataireRevenueEntryRepository->find((string) $entryId);

        if (
            !$entry instanceof PrestataireRevenueEntry
            || $entry->getPrestataire()?->getId() !== $prestataireProfile->getId()
        ) {
            throw $this->createAccessDeniedException('Revenu externe introuvable.');
        }

        return $entry;
    }

    private function resolveRevenueHistorySort(Request $request): string
    {
        $sort = (string) $request->query->get('revenue_sort', 'date_desc');

        return \in_array($sort, ['date_asc', 'date_desc'], true) ? $sort : 'date_desc';
    }

    private function resolveRevenueHistoryStatus(Request $request): string
    {
        $status = (string) $request->query->get('revenue_status', 'all');

        return \in_array($status, ['all', 'paid', 'unpaid'], true) ? $status : 'all';
    }

    private function resolveRevenueSubtab(Request $request): string
    {
        $subtab = $request->request->get('revenues_subtab');

        if (!\is_string($subtab) || '' === $subtab) {
            $subtab = (string) $request->query->get('revenues_subtab', 'summary');
        }

        return \in_array($subtab, ['summary', 'history', 'payouts'], true) ? $subtab : 'summary';
    }

    /**
     * @param list<array<string, mixed>> $history
     *
     * @return list<array<string, mixed>>
     */
    private function buildRevenueHistoryItems(array $history, string $sort, string $status): array
    {
        $items = array_values(array_filter($history, static function (array $item) use ($status): bool {
            return match ($status) {
                'paid' => true === $item['isPaid'],
                'unpaid' => false === $item['isPaid'],
                default => true,
            };
        }));

        usort($items, static function (array $left, array $right) use ($sort): int {
            $comparison = $left['issuedAt']->getTimestamp() <=> $right['issuedAt']->getTimestamp();

            return 'date_asc' === $sort ? $comparison : -$comparison;
        });

        return $items;
    }

    private function markActiveConversationMessagesAsRead(
        ?Conversation $activeConversation,
        User $prestataireUser,
        EntityManagerInterface $entityManager,
    ): void {
        if (!$activeConversation instanceof Conversation) {
            return;
        }

        $now = new \DateTimeImmutable();
        $hasUpdates = false;

        foreach ($activeConversation->getMessages() as $message) {
            if ($message->isSystem()) {
                continue;
            }

            $author = $message->getAuthor();

            if (!$author instanceof User || $author->getId() === $prestataireUser->getId()) {
                continue;
            }

            if (null !== $message->getReadAt()) {
                continue;
            }

            $message->setReadAt($now);
            $hasUpdates = true;
        }

        if ($hasUpdates) {
            $entityManager->flush();
        }
    }

    /**
     * @param list<\App\Entity\PrestataireAppointment> $upcomingAppointments
     *
     * @return list<array{
     *     tone:string,
     *     title:string,
     *     text:string,
     *     href:string,
     *     label:string,
     *     items?:list<string>
     * }>
     */
    private function buildPriorityAlerts(
        PrestataireProfile $prestataireProfile,
        User $user,
        QuoteRequestRepository $quoteRequestRepository,
        MessageRepository $messageRepository,
        mixed $currentSubscription,
        int $remainingCredits,
        array $upcomingAppointments,
        array $mandatoryChecklist,
    ): array {
        $alerts = [];

        if (!$mandatoryChecklist['isComplete']) {
            $missingLabels = array_map(
                static fn (array $item): string => $item['label'],
                array_slice($mandatoryChecklist['missingItems'], 0, 4)
            );

            $alerts[] = [
                'tone' => 'warning',
                'title' => 'Profil à finaliser',
                'text' => sprintf(
                    '%d information%s indispensable%s encore manquante%s avant une mise en ligne optimale.',
                    count($mandatoryChecklist['missingItems']),
                    count($mandatoryChecklist['missingItems']) > 1 ? 's' : '',
                    count($mandatoryChecklist['missingItems']) > 1 ? 's' : '',
                    count($mandatoryChecklist['missingItems']) > 1 ? 's' : ''
                ),
                'href' => $this->buildSettingsUrlFromChecklist($mandatoryChecklist),
                'label' => 'Compléter maintenant',
                'items' => $missingLabels,
            ];
        }

        $unreadMessagesCount = $messageRepository->countUnreadIncomingForPrestataire($prestataireProfile, $user);
        if ($unreadMessagesCount > 0) {
            $alerts[] = [
                'tone' => 'gold',
                'title' => 'Messages à lire',
                'text' => sprintf(
                    '%d message%s client attend%s votre lecture.',
                    $unreadMessagesCount,
                    $unreadMessagesCount > 1 ? 's' : '',
                    $unreadMessagesCount > 1 ? 'ent' : ''
                ),
                'href' => $this->generateUrl('app_prestataire_dashboard', ['tab' => 'messages']) . '#messages-main-panel',
                'label' => 'Ouvrir la messagerie',
            ];
        }

        $submittedRequestsCount = $quoteRequestRepository->countForPrestataireByStatuses(
            $prestataireProfile,
            [QuoteRequestStatusEnum::SUBMITTED]
        );
        if ($submittedRequestsCount > 0) {
            $alerts[] = [
                'tone' => 'danger',
                'title' => 'Demandes en attente',
                'text' => sprintf(
                    '%d demande%s de devis attend%s encore votre prise en charge.',
                    $submittedRequestsCount,
                    $submittedRequestsCount > 1 ? 's' : '',
                    $submittedRequestsCount > 1 ? 'ent' : ''
                ),
                'href' => $this->generateUrl('app_prestataire_dashboard', ['tab' => 'demandes']) . '#demandes-main-panel',
                'label' => 'Traiter les demandes',
            ];
        }

        $acceptedRequestsCount = $quoteRequestRepository->countForPrestataireByStatuses(
            $prestataireProfile,
            [QuoteRequestStatusEnum::ACCEPTED]
        );
        if ($acceptedRequestsCount > 0) {
            $alerts[] = [
                'tone' => 'info',
                'title' => 'Devis à préparer',
                'text' => sprintf(
                    '%d dossier%s accepté%s pour étude mérite%s maintenant un chiffrage.',
                    $acceptedRequestsCount,
                    $acceptedRequestsCount > 1 ? 's' : '',
                    $acceptedRequestsCount > 1 ? 's' : '',
                    $acceptedRequestsCount > 1 ? 'nt' : ''
                ),
                'href' => $this->generateUrl('app_prestataire_dashboard', ['tab' => 'demandes']) . '#demandes-main-panel',
                'label' => 'Reprendre les dossiers',
            ];
        }

        if ([] !== $upcomingAppointments) {
            $nextAppointment = $upcomingAppointments[0];
            $nextStartsAt = $nextAppointment->getStartsAt();

            if ($nextStartsAt instanceof \DateTimeInterface) {
                $hoursUntil = (int) floor(($nextStartsAt->getTimestamp() - time()) / 3600);

                if ($hoursUntil <= 24) {
                    $alerts[] = [
                        'tone' => 'success',
                        'title' => 'Rendez-vous imminent',
                        'text' => sprintf(
                            '"%s" commence le %s.',
                            $nextAppointment->getTitle() ?? 'Votre prochain rendez-vous',
                            $nextStartsAt->format('d/m à H:i')
                        ),
                        'href' => $this->generateUrl('app_prestataire_dashboard', ['tab' => 'calendrier']) . '#calendrier-main-panel',
                        'label' => 'Voir l’agenda',
                    ];
                }
            }
        }

        if ($currentSubscription && $remainingCredits <= 2) {
            $alerts[] = [
                'tone' => 'warning',
                'title' => 'Crédits faibles',
                'text' => sprintf(
                    'Il ne vous reste plus que %d crédit%s sur votre abonnement actif.',
                    $remainingCredits,
                    $remainingCredits > 1 ? 's' : ''
                ),
                'href' => $this->generateUrl('app_subscription_index'),
                'label' => 'Gérer l’abonnement',
            ];
        }

        return array_slice($alerts, 0, 3);
    }

    private function resolveQuoteSort(Request $request): string
    {
        return (string) $request->query->get('quote_sort', 'recent');
    }

    private function resolveQuoteOrderBy(string $quoteSort): array
    {
        return match ($quoteSort) {
            'oldest' => ['createdAt' => 'ASC'],
            'budget_asc' => ['budgetAmount' => 'ASC'],
            'budget_desc' => ['budgetAmount' => 'DESC'],
            default => ['createdAt' => 'DESC'],
        };
    }

    private function createQuoteRequestsQueryBuilder(
        QuoteRequestRepository $quoteRequestRepository,
        PrestataireProfile $prestataireProfile,
        bool $archived,
        array $quoteOrderBy,
    ): QueryBuilder {
        $queryBuilder = $quoteRequestRepository->createQueryBuilder('qr')
            ->where('qr.prestataire = :prestataire')
            ->andWhere('qr.deletedAt IS NULL')
            ->setParameter('prestataire', $prestataireProfile);

        if ($archived) {
            $queryBuilder->andWhere('qr.archivedByPrestataireAt IS NOT NULL');
        } else {
            $queryBuilder
                ->andWhere('qr.archivedByPrestataireAt IS NULL')
                ->andWhere('qr.status IN (:activeStatuses)')
                ->setParameter('activeStatuses', [
                    QuoteRequestStatusEnum::SUBMITTED,
                    QuoteRequestStatusEnum::ACCEPTED,
                    QuoteRequestStatusEnum::ANSWERED,
                    QuoteRequestStatusEnum::CLOSED,
                ]);
        }

        foreach ($quoteOrderBy as $field => $direction) {
            $queryBuilder->addOrderBy('qr.' . $field, $direction);
        }

        return $queryBuilder;
    }

    /**
     * @return list<Conversation>
     */
    private function loadConversations(
        ConversationRepository $conversationRepository,
        PrestataireProfile $prestataireProfile,
    ): array {
        return $conversationRepository->createQueryBuilder('c')
            ->leftJoin('c.quoteRequest', 'qr')
            ->andWhere('c.prestataire = :prestataire')
            ->andWhere('qr IS NULL OR qr.archivedByPrestataireAt IS NULL')
            ->setParameter('prestataire', $prestataireProfile)
            ->addOrderBy('c.lastMessageAt', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<Conversation> $conversations
     */
    private function resolveActiveConversation(array $conversations, mixed $conversationId): ?Conversation
    {
        if ([] === $conversations) {
            return null;
        }

        if (is_string($conversationId) || is_numeric($conversationId)) {
            foreach ($conversations as $conversation) {
                if ((string) $conversation->getId() === (string) $conversationId) {
                    return $conversation;
                }
            }
        }

        return $conversations[0];
    }

    /**
     * @param list<Conversation> $conversations
     *
     * @return array<string, int>
     */
    private function buildConversationUnreadCounts(array $conversations, User $prestataireUser): array
    {
        $counts = [];

        foreach ($conversations as $conversation) {
            $unreadCount = 0;

            foreach ($conversation->getMessages() as $message) {
                if ($message->isSystem() || null !== $message->getReadAt()) {
                    continue;
                }

                $author = $message->getAuthor();

                if (!$author instanceof User || $author->getId() === $prestataireUser->getId()) {
                    continue;
                }

                ++$unreadCount;
            }

            $counts[(string) $conversation->getId()] = $unreadCount;
        }

        return $counts;
    }

    /**
     * @param array<string, int> $conversationUnreadCounts
     */
    private function countUnreadConversations(array $conversationUnreadCounts): int
    {
        return \count(array_filter(
            $conversationUnreadCounts,
            static fn (int $count): bool => $count > 0
        ));
    }

    private function conversationHasPhotos(?Conversation $conversation): bool
    {
        if (!$conversation instanceof Conversation) {
            return false;
        }

        foreach ($conversation->getMessages() as $message) {
            foreach ($message->getAttachments() as $attachment) {
                if ($attachment->getFileName()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function createMessageForm(Conversation $conversation, Message $message): FormInterface
    {
        return $this->createForm(MessageType::class, $message, [
            'action' => $this->generateUrl('app_prestataire_conversation_message_send', [
                'id' => $conversation->getId(),
            ]),
            'method' => 'POST',
        ]);
    }

    private function resolveReturnScroll(Request $request): ?int
    {
        $raw = $request->request->get('return_scroll');

        if (!\is_string($raw) && !\is_numeric($raw)) {
            return null;
        }

        $scroll = (int) $raw;

        return $scroll >= 0 ? $scroll : null;
    }

    private function isAsyncConversationSubmit(Request $request): bool
    {
        return $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept', ''), 'application/json');
    }

    private function buildSettingsUrlFromChecklist(array $mandatoryChecklist): string
    {
        $target = $mandatoryChecklist['missingItems'][0] ?? null;
        $parameters = [
            'tab' => $target['tab'] ?? 'profile',
        ];

        if (isset($target['fragment']) && null !== $target['fragment']) {
            $parameters['_fragment'] = $target['fragment'];
        }

        return $this->generateUrl('app_prestataire_settings', $parameters);
    }
}
