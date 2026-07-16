<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\MessageAttachment;
use App\Entity\PrestataireProfile;
use App\Entity\User;
use App\Enum\MessageTypeEnum;
use App\Enum\NotificationTypeEnum;
use App\Enum\QuoteRequestStatusEnum;
use App\Form\MessageType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\PrestataireProfileRepository;
use App\Repository\PrestataireServiceRepository;
use App\Repository\QuoteRequestRepository;
use App\Repository\ReviewRepository;
use App\Service\ConversationMessageManager;
use App\Service\NotificationManager;
use App\Service\PrestataireProfileCompletionService;
use App\Service\RealtimeNotifier;
use App\Service\Subscription\SubscriptionAccessManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Gère les actions liées à prestataire dashboard.
 */
final class PrestataireDashboardController extends AbstractController
{
    private const DASHBOARD_PAGE_SIZE = 10;

    public function __construct(
        private readonly PrestataireProfileCompletionService $prestataireProfileCompletionService,
    ) {
    }

    #[Route('/prestataire/espace-pro', name: 'app_prestataire_dashboard', methods: ['GET'])]
    /**
     * Affiche la page principale de ce contrôleur.
     *
     * @return Response
     */
    public function index(
        Request $request,
        PrestataireProfileRepository $prestataireProfileRepository,
        PrestataireServiceRepository $prestataireServiceRepository,
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

        return $this->render('prestataire_dashboard/prestataire_dashboard.html.twig', $this->buildDashboardViewData(
            request: $request,
            user: $user,
            prestataireProfile: $prestataireProfile,
            prestataireServiceRepository: $prestataireServiceRepository,
            quoteRequestRepository: $quoteRequestRepository,
            conversationRepository: $conversationRepository,
            messageRepository: $messageRepository,
            reviewRepository: $reviewRepository,
            paginator: $paginator,
            subscriptionAccessManager: $subscriptionAccessManager,
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
            $this->addFlash('warning', 'Un abonnement actif est requis pour utiliser la messagerie instantanée.');

            return $this->redirectToRoute('app_subscription_index');
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
                $this->addFlash('danger', 'Vous devez saisir un message ou ajouter au moins une photo.');

                return $this->redirectToRoute('app_prestataire_dashboard', [
                    'conversation' => $conversation->getId(),
                    'tab' => 'messages',
                    '_fragment' => 'messages-main-panel',
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

            return $this->redirectToRoute('app_prestataire_dashboard', [
                'conversation' => $conversation->getId(),
                'tab' => 'messages',
                '_fragment' => 'messages-main-panel',
                ], 303);
        }

        return $this->render(
            'prestataire_dashboard/prestataire_dashboard.html.twig',
            $this->buildDashboardViewData(
                request: $request,
                user: $user,
                prestataireProfile: $prestataireProfile,
                prestataireServiceRepository: $prestataireServiceRepository,
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
        User $user,
        PrestataireProfile $prestataireProfile,
        PrestataireServiceRepository $prestataireServiceRepository,
        QuoteRequestRepository $quoteRequestRepository,
        ConversationRepository $conversationRepository,
        MessageRepository $messageRepository,
        ReviewRepository $reviewRepository,
        PaginatorInterface $paginator,
        SubscriptionAccessManager $subscriptionAccessManager,
        ?FormView $messageFormView = null,
        ?Conversation $forcedActiveConversation = null,
        ?string $forcedActiveTab = null,
    ): array {
        $quoteSort = $this->resolveQuoteSort($request);
        $quoteOrderBy = $this->resolveQuoteOrderBy($quoteSort);
        $conversations = $this->loadConversations($conversationRepository, $prestataireProfile);
        $activeConversation = $forcedActiveConversation ?? $this->resolveActiveConversation(
            $conversations,
            $request->query->get('conversation')
        );

        if (!$activeConversation instanceof Conversation && $forcedActiveConversation instanceof Conversation) {
            $activeConversation = $forcedActiveConversation;
        }

        if (null === $messageFormView && $activeConversation instanceof Conversation) {
            $messageFormView = $this->createMessageForm($activeConversation, new Message())->createView();
        }

        $completionReport = $this->prestataireProfileCompletionService->buildReport($user, $prestataireProfile);
        $currentSubscription = $subscriptionAccessManager->getCurrentUsableSubscription($prestataireProfile);

        return [
            'user' => $user,
            'prestataireProfile' => $prestataireProfile,
            'completionReport' => $completionReport,
            'recentQuoteRequests' => $quoteRequestRepository->findRecentForPrestataireDashboard($prestataireProfile, 5),
            'recentMessages' => $messageRepository->findLatestForPrestataire($prestataireProfile, 5),
            'recentReviews' => $reviewRepository->findRecentForPrestataireDashboard($prestataireProfile, 5),
            'currentSubscription' => $currentSubscription,
            'remainingCredits' => $subscriptionAccessManager->getRemainingCredits($prestataireProfile),
            'prestations' => $prestataireServiceRepository->findBy(
                ['prestataire' => $prestataireProfile],
                ['updatedAt' => 'DESC', 'createdAt' => 'DESC']
            ),
            'quoteRequests' => $paginator->paginate(
                $this->createQuoteRequestsQueryBuilder($quoteRequestRepository, $prestataireProfile, false, $quoteOrderBy),
                $request->query->getInt('quotePage', 1),
                self::DASHBOARD_PAGE_SIZE,
                ['pageParameterName' => 'quotePage']
            ),
            'archivedQuoteRequests' => $paginator->paginate(
                $this->createQuoteRequestsQueryBuilder($quoteRequestRepository, $prestataireProfile, true, $quoteOrderBy),
                $request->query->getInt('archivedQuotePage', 1),
                self::DASHBOARD_PAGE_SIZE,
                ['pageParameterName' => 'archivedQuotePage']
            ),
            'quoteSort' => $quoteSort,
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'messageForm' => $messageFormView,
            'activeTab' => $forcedActiveTab ?? (string) $request->query->get('tab', 'dashboard'),
            'hasConversationPhotos' => $this->conversationHasPhotos($activeConversation),
        ];
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
}
