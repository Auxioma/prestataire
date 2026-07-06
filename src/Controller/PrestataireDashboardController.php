<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\MessageAttachment;
use App\Entity\User;
use App\Enum\MessageTypeEnum;
use App\Enum\NotificationTypeEnum;
use App\Enum\QuoteRequestStatusEnum;
use App\Form\MessageType;
use App\Repository\ConversationRepository;
use App\Repository\PrestataireProfileRepository;
use App\Repository\PrestataireServiceRepository;
use App\Repository\QuoteRequestRepository;
use App\Service\ConversationMessageManager;
use App\Service\NotificationManager;
use App\Service\RealtimeNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PrestataireDashboardController extends AbstractController
{
    #[Route('/prestataire/espace-pro', name: 'app_prestataire_dashboard', methods: ['GET'])]
    public function index(
        Request $request,
        PrestataireProfileRepository $prestataireProfileRepository,
        PrestataireServiceRepository $prestataireServiceRepository,
        QuoteRequestRepository $quoteRequestRepository,
        ConversationRepository $conversationRepository,
        PaginatorInterface $paginator,
    ): Response {
        /*
         * ------------------------------------------------------------
         * 1. Sécurité : utilisateur connecté + rôle prestataire requis
         * ------------------------------------------------------------
         */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isGranted('ROLE_PRESTATAIRE')) {
            throw $this->createAccessDeniedException('Accès réservé aux prestataires.');
        }

        /*
         * ------------------------------------------------------------
         * 2. Chargement du profil prestataire connecté
         * ------------------------------------------------------------
         */
        $prestataireProfile = $prestataireProfileRepository->findOneBy([
            'account' => $user,
        ]);

        if (!$prestataireProfile) {
            throw $this->createAccessDeniedException('Profil prestataire introuvable.');
        }

        /*
         * ------------------------------------------------------------
         * 3. Chargement des prestations du prestataire
         * ------------------------------------------------------------
         */
        $prestations = $prestataireServiceRepository->findBy(
            ['prestataire' => $prestataireProfile],
            ['updatedAt' => 'DESC', 'createdAt' => 'DESC']
        );

        /*
         * ------------------------------------------------------------
         * 4. Préparation du tri et pagination des demandes de devis
         * ------------------------------------------------------------
         */
        $quoteSort = $request->query->get('quote_sort', 'recent');

        $quoteOrderBy = match ($quoteSort) {
            'oldest' => ['createdAt' => 'ASC'],
            'budget_asc' => ['budgetAmount' => 'ASC'],
            'budget_desc' => ['budgetAmount' => 'DESC'],
            default => ['createdAt' => 'DESC'],
        };

        $quoteQueryBuilder = $quoteRequestRepository->createQueryBuilder('qr')
            ->where('qr.prestataire = :prestataire')
            ->andWhere('qr.deletedAt IS NULL')
            ->andWhere('qr.archivedByPrestataireAt IS NULL')
            ->andWhere('qr.status IN (:activeStatuses)')
            ->setParameter('prestataire', $prestataireProfile)
            ->setParameter('activeStatuses', [
                QuoteRequestStatusEnum::SUBMITTED,
                QuoteRequestStatusEnum::ACCEPTED,
                QuoteRequestStatusEnum::ANSWERED,
                QuoteRequestStatusEnum::CLOSED,
            ]);

        foreach ($quoteOrderBy as $field => $direction) {
            $quoteQueryBuilder->addOrderBy('qr.' . $field, $direction);
        }

        $quoteRequests = $paginator->paginate(
            $quoteQueryBuilder,
            $request->query->getInt('quotePage', 1),
            10,
            [
                'pageParameterName' => 'quotePage',
            ]
        );

        /*
         * ------------------------------------------------------------
         * 4.bis Préparation du tri et pagination des demandes de devis archivées
         * ------------------------------------------------------------
         */
        $archivedQuoteQueryBuilder = $quoteRequestRepository->createQueryBuilder('qr')
            ->where('qr.prestataire = :prestataire')
            ->andWhere('qr.archivedByPrestataireAt IS NOT NULL')
            ->andWhere('qr.deletedAt IS NULL')
            ->setParameter('prestataire', $prestataireProfile);

        foreach ($quoteOrderBy as $field => $direction) {
            $archivedQuoteQueryBuilder->addOrderBy('qr.' . $field, $direction);
        }

        $archivedQuoteRequests = $paginator->paginate(
            $archivedQuoteQueryBuilder,
            $request->query->getInt('archivedQuotePage', 1),
            10,
            [
                'pageParameterName' => 'archivedQuotePage',
            ]
        );

        /*
         * ------------------------------------------------------------
         * 5. Chargement des conversations et détermination de l’active
         * ------------------------------------------------------------
         */
        $conversationId = $request->query->get('conversation');

        $conversations = $conversationRepository->findBy(
            ['prestataire' => $prestataireProfile],
            ['lastMessageAt' => 'DESC', 'createdAt' => 'DESC']
        );

        $activeConversation = null;
        $activeTab = $request->query->get('tab', 'dashboard');

        if (!empty($conversations)) {
            if (is_string($conversationId) || is_numeric($conversationId)) {
                foreach ($conversations as $conversation) {
                    if ((string) $conversation->getId() === (string) $conversationId) {
                        $activeConversation = $conversation;
                        break;
                    }
                }
            }

            if (!$activeConversation instanceof Conversation) {
                $activeConversation = $conversations[0];
            }
        }

        // Determine si la conversation actuelle contient des photos
        $hasConversationPhotos = false;

        if ($activeConversation instanceof Conversation) {
            foreach ($activeConversation->getMessages() as $message) {
                foreach ($message->getAttachments() as $attachment) {
                    if ($attachment->getFileName()) {
                        $hasConversationPhotos = true;
                        break 2;
                    }
                }
            }
        }

        /*
         * ------------------------------------------------------------
         * 6. Construction du formulaire de message
         *    Version simple : un seul champ fichier multiple
         * ------------------------------------------------------------
         */
        $messageForm = null;

        if ($activeConversation) {
            $message = new Message();

            $messageForm = $this->createForm(MessageType::class, $message, [
                'action' => $this->generateUrl('app_prestataire_conversation_message_send', [
                    'id' => $activeConversation->getId(),
                ]),
                'method' => 'POST',
            ])->createView();
        }

        /*
         * ------------------------------------------------------------
         * 7. Rendu final du dashboard prestataire
         * ------------------------------------------------------------
         */
        return $this->render('prestataire_dashboard/prestataire_dashboard.html.twig', [
            'user' => $user,
            'prestataireProfile' => $prestataireProfile,
            'prestations' => $prestations,
            'quoteRequests' => $quoteRequests,
            'quoteSort' => $quoteSort,
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'messageForm' => $messageForm,
            'activeTab' => $activeTab,
            'hasConversationPhotos' => $hasConversationPhotos,
            'archivedQuoteRequests' => $archivedQuoteRequests,
        ]);
    }

    #[Route('/prestataire/espace-pro/conversation/{id}/message', name: 'app_prestataire_conversation_message_send', methods: ['POST'])]
    public function sendMessage(
        #[MapEntity(id: 'id')] Conversation $conversation,
        Request $request,
        EntityManagerInterface $entityManager,
        RealtimeNotifier $realtimeNotifier,
        NotificationManager $notificationManager,
        QuoteRequestRepository $quoteRequestRepository,
        ConversationRepository $conversationRepository,
        PrestataireServiceRepository $prestataireServiceRepository,
        ConversationMessageManager $conversationMessageManager,
    ): Response {
        /*
     * ------------------------------------------------------------
     * 1. Sécurité : utilisateur connecté + profil prestataire valide
     * ------------------------------------------------------------
     */
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $prestataireProfile = $user->getPrestataireProfile();

        if ($conversation->getPrestataire()?->getId() !== $prestataireProfile->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas répondre à cette conversation.');
        }

        /*
     * ------------------------------------------------------------
     * 2. Préparation du message et du formulaire
     * ------------------------------------------------------------
     */
        $message = new Message();

        $form = $this->createForm(MessageType::class, $message, [
            'action' => $this->generateUrl('app_prestataire_conversation_message_send', [
                'id' => $conversation->getId(),
            ]),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        /*
     * ------------------------------------------------------------
     * 3. Traitement du submit valide
     * ------------------------------------------------------------
     */
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

            /*
         * ------------------------------------------------------------
         * 4. Mise à jour des dates de conversation
         * ------------------------------------------------------------
         */
            if (method_exists($conversation, 'setLastMessageAt')) {
                $conversation->setLastMessageAt(new \DateTimeImmutable());
            }

            if (method_exists($conversation, 'setUpdatedAt')) {
                $conversation->setUpdatedAt(new \DateTimeImmutable());
            }

            /*
         * ------------------------------------------------------------
         * 5. Persistance du message et des pièces jointes
         * ------------------------------------------------------------
         */
            $entityManager->persist($message);
            $entityManager->flush();

            /*
         * ------------------------------------------------------------
         * 6. Temps réel : diffusion immédiate du nouveau message
         * ------------------------------------------------------------
         */
            $realtimeNotifier->notifyMessageCreated($conversation->getId(), $message);

            /*
         * ------------------------------------------------------------
         * 7. Notification applicative au client
         * ------------------------------------------------------------
         */
            $clientUser = $conversation->getClient()?->getAccount();

            if ($clientUser instanceof \App\Entity\User && $clientUser->getId() !== $user->getId()) {
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

            /*
         * ------------------------------------------------------------
         * 8. Redirection post-submit
         * ------------------------------------------------------------
         */
            return $this->redirectToRoute('app_prestataire_dashboard', [
                'conversation' => $conversation->getId(),
                'tab' => 'messages',
                '_fragment' => 'messages-main-panel',
            ], 303);
        }

        /*
     * ------------------------------------------------------------
     * 9. Rechargement des données du dashboard si formulaire invalide
     * ------------------------------------------------------------
     */
        $quoteSort = $request->query->get('quoteSort', 'latest');

        $quoteOrderBy = match ($quoteSort) {
            'oldest' => ['createdAt' => 'ASC'],
            'budget_asc' => ['budgetAmount' => 'ASC'],
            'budget_desc' => ['budgetAmount' => 'DESC'],
            default => ['createdAt' => 'DESC'],
        };

        $prestations = $prestataireServiceRepository->findBy(
            ['prestataire' => $prestataireProfile],
            ['createdAt' => 'DESC']
        );

        $quoteRequests = $quoteRequestRepository->findBy(
            ['prestataire' => $prestataireProfile],
            $quoteOrderBy
        );

        $conversations = $conversationRepository->findBy(
            ['prestataire' => $prestataireProfile],
            ['lastMessageAt' => 'DESC', 'createdAt' => 'DESC']
        );

        $archivedQuoteRequests = $quoteRequestRepository->findBy(
            ['prestataire' => $prestataireProfile,],
            ['updatedAt' => 'DESC','createdAt' => 'DESC',]
        );

        /*
     * ------------------------------------------------------------
     * 10. Réaffichage du dashboard avec le formulaire et ses erreurs
     * ------------------------------------------------------------
     */
        return $this->render('prestataire_dashboard/prestataire_dashboard.html.twig', [
            'user' => $user,
            'prestataireProfile' => $prestataireProfile,
            'prestations' => $prestations,
            'quoteRequests' => $quoteRequests,
            'archivedQuoteRequests' => $archivedQuoteRequests,
            'quoteSort' => $quoteSort,
            'conversations' => $conversations,
            'activeConversation' => $conversation,
            'messageForm' => $form->createView(),
            'activeTab' => 'messages',
        ], new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[Route('/prestataire/espace-pro/conversation/{id}/photos', name: 'app_prestataire_conversation_photos', methods: ['GET'])]
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
}
