<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\PrestataireProfile;
use App\Entity\PrestataireService;
use App\Entity\QuoteRequest;
use App\Entity\User;
use App\Enum\MessageTypeEnum;
use App\Enum\NotificationTypeEnum;
use App\Enum\QuoteRequestStatusEnum;
use App\Form\MessageType;
use App\Form\QuoteRequestType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\QuoteRequestRepository;
use App\Service\ConversationMessageManager;
use App\Service\NotificationManager;
use App\Service\RealtimeNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;


#[Route('/demandes-de-devis', name: 'app_quote_request')]
final class QuoteRequestController extends AbstractController
{
    #[Route('', name: '_index', methods: ['GET'])]
    public function index(
        Request $request,
        QuoteRequestRepository $quoteRequestRepository,
        PaginatorInterface $paginator
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getClientProfile() || !$this->isGranted('ROLE_CLIENT')) {
            throw $this->createAccessDeniedException('Accès réservé aux clients.');
        }

        $queryBuilder = $quoteRequestRepository->createQueryBuilder('qr')
            ->where('qr.client = :client')
            ->andWhere('qr.deletedAt IS NULL')
            ->setParameter('client', $user->getClientProfile())
            ->orderBy('qr.createdAt', 'DESC');

        $quoteRequests = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            8
        );

        return $this->render('quote_request/index.html.twig', [
            'quoteRequests' => $quoteRequests,
        ]);
    }

    #[Route('/nouvelle/prestataire/{prestataireSlug}', name: '_new_by_prestataire', methods: ['GET', 'POST'])]
    #[Route('/nouvelle/{slug}', name: '_new', methods: ['GET', 'POST'], defaults: ['slug' => null])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        NotificationManager $notificationManager,
        #[MapEntity(mapping: ['slug' => 'slug'])] ?PrestataireService $prestation = null,
        #[MapEntity(mapping: ['prestataireSlug' => 'slug'])] ?PrestataireProfile $prestataire = null
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getClientProfile() || !$this->isGranted('ROLE_CLIENT')) {
            throw $this->createAccessDeniedException('Accès réservé aux clients.');
        }

        $quoteRequest = new QuoteRequest();
        $quoteRequest->setClient($user->getClientProfile());

        if (!$prestation && !$prestataire) {
            throw $this->createNotFoundException('Contexte manquant pour créer une demande de devis.');
        }

        if ($prestation) {
            if (!$prestation->isActive()) {
                throw $this->createNotFoundException('Prestation introuvable.');
            }

            $prestataire = $prestation->getPrestataire();

            if (!$prestataire) {
                throw $this->createNotFoundException('Prestataire introuvable.');
            }

            $quoteRequest->setPrestation($prestation);
            $quoteRequest->setPrestataire($prestataire);
        } else {
            $activePrestations = $prestataire
                ->getPrestataireServices()
                ->filter(static fn($ps) => $ps->isActive());

            if ($activePrestations->isEmpty()) {
                $this->addFlash('warning', 'Ce prestataire ne propose actuellement aucune prestation disponible pour une demande de devis.');

                return $this->redirectToRoute('app_prestataire_show', [
                    'slug' => $prestataire->getSlug(),
                ]);
            }

            $quoteRequest->setPrestataire($prestataire);
        }

        $form = $this->createForm(QuoteRequestType::class, $quoteRequest, [
            'prestataire' => $prestataire,
            'locked_prestation' => null !== $prestation,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $selectedPrestation = $quoteRequest->getPrestation();

            if (!$selectedPrestation) {
                $form->addError(new FormError('Veuillez sélectionner un service.'));
            } elseif ($selectedPrestation->getPrestataire()?->getId() !== $prestataire?->getId()) {
                $form->addError(new FormError('Le service sélectionné ne correspond pas au prestataire choisi.'));
            } elseif (!$selectedPrestation->isActive()) {
                $form->addError(new FormError('Le service sélectionné n’est pas disponible.'));
            }

            if ($form->isValid()) {
                $quoteRequest->setPrestataire($selectedPrestation->getPrestataire());
                $quoteRequest->setUpdatedAt(new \DateTimeImmutable());

                $baseSlug = $slugger
                    ->slug($quoteRequest->getTitle() ?: 'demande-de-devis')
                    ->lower()
                    ->toString();

                $quoteRequest->setSlug($baseSlug . '-' . substr(bin2hex(random_bytes(4)), 0, 8));

                $entityManager->persist($quoteRequest);
                $entityManager->flush();

                $prestataireUser = $quoteRequest->getPrestataire()?->getAccount();

                if ($prestataireUser instanceof User) {
                    $clientLabel = trim(sprintf(
                        '%s %s',
                        $user->getFirstName() ?? '',
                        $user->getLastName() ?? ''
                    ));

                    if ('' === $clientLabel) {
                        $clientLabel = 'Un client';
                    }

                    $notificationManager->notify(
                        $prestataireUser,
                        NotificationTypeEnum::QUOTE_REQUEST_RECEIVED,
                        'Nouvelle demande de prestation',
                        sprintf(
                            '%s vous a envoyé une nouvelle demande%s.',
                            $clientLabel,
                            $quoteRequest->getTitle() ? ' : ' . $quoteRequest->getTitle() : ''
                        ),
                        $this->generateUrl('app_prestataire_quote_request_show', [
                            'slug' => $quoteRequest->getSlug(),
                        ]),
                        [
                            'quoteRequestId' => $quoteRequest->getId(),
                            'quoteRequestSlug' => $quoteRequest->getSlug(),
                            'clientId' => $user->getId(),
                            'prestationId' => $selectedPrestation->getId(),
                        ]
                    );
                }

                $this->addFlash('success', 'Votre demande de devis a bien été envoyée.');

                return $this->redirectToRoute('app_quote_request_show', [
                    'slug' => $quoteRequest->getSlug(),
                ]);
            }
        }

        return $this->render('quote_request/new.html.twig', [
            'form' => $form->createView(),
            'quoteRequest' => $quoteRequest,
            'prestataire' => $prestataire,
            'prestation' => $prestation,
        ]);
    }

    #[Route('/{slug}', name: '_show', methods: ['GET', 'POST'])]
    public function show(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] QuoteRequest $quoteRequest,
        ConversationRepository $conversationRepository,
        MessageRepository $messageRepository,
        EntityManagerInterface $entityManager,
        RealtimeNotifier $realtimeNotifier,
        NotificationManager $notificationManager,
        ConversationMessageManager $conversationMessageManager,
    ): Response {
        $user = $this->getUser();

        // =========================
        // Sécurité d'accès client
        // =========================
        if (!$user instanceof User || !$user->getClientProfile() || !$this->isGranted('ROLE_CLIENT')) {
            throw $this->createAccessDeniedException('Accès réservé aux clients.');
        }

        if ($quoteRequest->getClient()?->getId() !== $user->getClientProfile()?->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas consulter cette demande.');
        }

        if ($quoteRequest->isDeleted()) {
            throw $this->createNotFoundException('Cette demande n’est plus disponible.');
        }

        // =========================
        // Chargement conversation
        // =========================
        $conversation = $conversationRepository->findOneByQuoteRequest($quoteRequest);
        $messages = $conversation ? $messageRepository->findByConversationOrderedByCreatedAt($conversation) : [];

        // =========================
        // Présence de photos
        // =========================
        $hasConversationPhotos = false;

        if ($conversation instanceof Conversation) {
            foreach ($conversation->getMessages() as $message) {
                foreach ($message->getAttachments() as $attachment) {
                    if ($attachment->getFileName()) {
                        $hasConversationPhotos = true;
                        break 2;
                    }
                }
            }
        }

        // =========================
        // Formulaire de message
        // =========================
        $messageForm = null;
        $canSendMessage = $conversation && \in_array($quoteRequest->getStatus()->value, ['accepted', 'answered'], true);

        if ($canSendMessage) {
            $message = new Message();
            $message->setConversation($conversation);
            $message->setAuthor($user);

            $messageForm = $this->createForm(MessageType::class, $message);
            $messageForm->handleRequest($request);

            // =========================
            // Envoi d'un message
            // =========================
            if ($messageForm->isSubmitted() && $messageForm->isValid()) {
                /** @var array<\Symfony\Component\HttpFoundation\File\UploadedFile>|null $uploadedFiles */
                $uploadedFiles = $messageForm->get('attachments')->getData();
                $uploadedFiles = is_array($uploadedFiles) ? $uploadedFiles : [];

                $isPrepared = $conversationMessageManager->prepareMessage(
                    $message,
                    $user,
                    $message->getContent(),
                    $uploadedFiles,
                    MessageTypeEnum::USER
                );

                if (!$isPrepared) {
                    $this->addFlash('danger', 'Vous devez saisir un message ou ajouter au moins une photo.');

                    return $this->redirectToRoute('app_quote_request_show', [
                        'slug' => $quoteRequest->getSlug(),
                        '_fragment' => 'quote-conversation',
                    ], 303);
                }

                $entityManager->persist($message);

                $quoteRequest->setUpdatedAt(new \DateTimeImmutable());
                $entityManager->flush();

                // =========================
                // Temps réel + notification
                // =========================
                $realtimeNotifier->notifyMessageCreated($conversation->getId(), $message);

                $prestataireUser = $conversation->getPrestataire()?->getAccount();

                if ($prestataireUser instanceof User && $prestataireUser->getId() !== $user->getId()) {
                    $notificationManager->notify(
                        $prestataireUser,
                        NotificationTypeEnum::MESSAGE_RECEIVED,
                        'Nouveau message',
                        'Vous avez reçu un nouveau message de la part d’un client.',
                        $this->generateUrl('app_prestataire_dashboard', [
                            'conversation' => $conversation->getId(),
                            'tab' => 'messages',
                            '_fragment' => 'messages-main-panel',
                        ]),
                        [
                            'conversationId' => $conversation->getId(),
                            'messageId' => $message->getId(),
                            'quoteRequestId' => $quoteRequest->getId(),
                            'quoteRequestSlug' => $quoteRequest->getSlug(),
                            'senderId' => $user->getId(),
                        ]
                    );
                }

                return $this->redirectToRoute('app_quote_request_show', [
                    'slug' => $quoteRequest->getSlug(),
                    '_fragment' => 'quote-conversation',
                ], 303);
            }
        }

        // =========================
        // Rendu de la page
        // =========================
        return $this->render('quote_request/show.html.twig', [
            'quoteRequest' => $quoteRequest,
            'conversation' => $conversation,
            'messages' => $messages,
            'messageForm' => $messageForm?->createView(),
            'hasConversationPhotos' => $hasConversationPhotos,
        ]);
    }

    #[Route('/{slug}/delete', name: '_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] QuoteRequest $quoteRequest,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getClientProfile() || !$this->isGranted('ROLE_CLIENT')) {
            throw $this->createAccessDeniedException('Accès réservé aux clients.');
        }

        if ($quoteRequest->getClient()?->getId() !== $user->getClientProfile()?->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette demande.');
        }

        if ($quoteRequest->isDeleted()) {
            $this->addFlash('warning', 'Cette demande a déjà été supprimée.');

            return $this->redirectToRoute('app_quote_request_index');
        }

        if (
            !$this->isCsrfTokenValid(
                'delete-quote-request-' . $quoteRequest->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_quote_request_show', [
                'slug' => $quoteRequest->getSlug(),
            ]);
        }

        if (null !== $quoteRequest->getConversation()) {
            $this->addFlash('warning', 'Cette demande ne peut plus être supprimée car une conversation est déjà liée.');

            return $this->redirectToRoute('app_quote_request_show', [
                'slug' => $quoteRequest->getSlug(),
            ]);
        }

        $quoteRequest
            ->setDeletedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        if (!in_array(
            $quoteRequest->getStatus(),
            [QuoteRequestStatusEnum::SUBMITTED, QuoteRequestStatusEnum::DENIED],
            true
        )) {
            $this->addFlash('warning', 'Cette demande ne peut plus être supprimée dans son état actuel.');

            return $this->redirectToRoute('app_quote_request_show', [
                'slug' => $quoteRequest->getSlug(),
            ]);
        }

        $entityManager->flush();

        $this->addFlash('success', 'La demande de devis a bien été supprimée.');

        return $this->redirectToRoute('app_quote_request_index');
    }

    #[Route('/{slug}/photos', name: '_photos', methods: ['GET'])]
    public function photos(
        #[MapEntity(mapping: ['slug' => 'slug'])] QuoteRequest $quoteRequest,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getClientProfile() || !$this->isGranted('ROLE_CLIENT')) {
            throw $this->createAccessDeniedException('Accès réservé aux clients.');
        }

        if ($quoteRequest->getClient()?->getId() !== $user->getClientProfile()?->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas consulter les photos de cette demande.');
        }

        if ($quoteRequest->isDeleted()) {
            throw $this->createNotFoundException('Cette demande n’est plus disponible.');
        }

        $conversation = $quoteRequest->getConversation();

        if (!$conversation) {
            throw $this->createNotFoundException('Aucune conversation disponible.');
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
                ];
            }
        }

        usort($mediaItems, static fn(array $a, array $b) => ($a['createdAt'] <=> $b['createdAt']));

        return $this->render('conversation/gallery.html.twig', [
            'conversation' => $conversation,
            'quoteRequest' => $quoteRequest,
            'mediaItems' => $mediaItems,
            'backUrl' => $this->generateUrl('app_quote_request_show', [
                'slug' => $quoteRequest->getSlug(),
                '_fragment' => 'quote-conversation',
            ]),
            'deleteRouteName' => 'app_conversation_attachment_delete',
            'galleryContext' => 'client',
        ]);
    }
}
