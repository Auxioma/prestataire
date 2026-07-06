<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\PrestataireProfile;
use App\Entity\PrestataireService;
use App\Entity\QuoteProposal;
use App\Entity\QuoteRequest;
use App\Entity\User;
use App\Enum\MessageTypeEnum;
use App\Enum\NotificationTypeEnum;
use App\Enum\QuoteProposalStatusEnum;
use App\Enum\QuoteRequestStatusEnum;
use App\Form\MessageType;
use App\Form\QuoteRequestType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\QuoteProposalRepository;
use App\Repository\QuoteRequestRepository;
use App\Service\ConversationMessageManager;
use App\Service\NotificationManager;
use App\Service\RealtimeNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;


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

        $queryBuilder = $quoteRequestRepository->createActiveForClientQueryBuilder($user->getClientProfile());

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

#[Route(
    '/{slug}',
    name: '_show',
    methods: ['GET', 'POST'],
    requirements: ['slug' => '(?!(historique|nouvelle)$)[a-z0-9-]+']
)]
    public function show(
        Request $request,
        string $slug,
        QuoteRequestRepository $quoteRequestRepository,
        ConversationRepository $conversationRepository,
        MessageRepository $messageRepository,
        QuoteProposalRepository $quoteProposalRepository,
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

        $quoteRequest = $quoteRequestRepository->findOneActiveForClientBySlug(
            $slug,
            $user->getClientProfile()
        );

        if (!$quoteRequest instanceof QuoteRequest) {
            throw $this->createNotFoundException('Cette demande n’est pas disponible.');
        }

        // =========================
        // Chargement conversation
        // =========================
        $conversation = $conversationRepository->findOneByQuoteRequest($quoteRequest);
        $messages = $conversation ? $messageRepository->findByConversationOrderedByCreatedAt($conversation) : [];

        // =========================
        // Chargement des devis finalisés
        // =========================
        $quoteResponses = $quoteProposalRepository->findBy(
            [
                'quoteRequest' => $quoteRequest,
                'deletedAt' => null,
                'archivedByPrestataireAt' => null,
            ],
            [
                'finalizedAt' => 'DESC',
                'createdAt' => 'DESC',
            ]
        );

        $quoteResponses = array_values(array_filter(
            $quoteResponses,
            static fn(QuoteProposal $proposal): bool => $proposal->isFinalized() || $proposal->isAccepted()
        ));

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
            'quoteResponses' => $quoteResponses,
            'isArchivedView' => false,
        ]);
    }

    #[Route('/{slug}/delete', name: '_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        string $slug,
        QuoteRequestRepository $quoteRequestRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getClientProfile() || !$this->isGranted('ROLE_CLIENT')) {
            throw $this->createAccessDeniedException('Accès réservé aux clients.');
        }

        $quoteRequest = $quoteRequestRepository->findOneActiveForClientBySlug(
            $slug,
            $user->getClientProfile()
        );

        if (!$quoteRequest instanceof QuoteRequest) {
            throw $this->createNotFoundException('Cette demande n’est pas disponible.');
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

        $quoteRequest
            ->setDeletedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        $this->addFlash('success', 'La demande de devis a bien été supprimée.');

        return $this->redirectToRoute('app_quote_request_index');
    }

    #[Route('/{slug}/photos', name: '_photos', methods: ['GET'])]
    public function photos(
        string $slug,
        QuoteRequestRepository $quoteRequestRepository,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getClientProfile() || !$this->isGranted('ROLE_CLIENT')) {
            throw $this->createAccessDeniedException('Accès réservé aux clients.');
        }

        $quoteRequest = $quoteRequestRepository->findOneActiveForClientBySlug(
            $slug,
            $user->getClientProfile()
        );

        if (!$quoteRequest instanceof QuoteRequest) {
            throw $this->createNotFoundException('Cette demande n’est pas disponible.');
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

    #[Route('/devis/{publicReference}', name: '_quote_proposal_show', methods: ['GET'])]
    public function showProposal(
        string $publicReference,
        QuoteProposalRepository $quoteProposalRepository,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getClientProfile() || !$this->isGranted('ROLE_CLIENT')) {
            throw $this->createAccessDeniedException('Accès réservé aux clients.');
        }

        $proposal = $quoteProposalRepository->findOneVisibleForClientByPublicReference(
            $publicReference,
            $user->getClientProfile()
        );

        if (!$proposal instanceof QuoteProposal) {
            throw $this->createNotFoundException('Devis introuvable.');
        }

        return $this->render('quote_request/proposal_show.html.twig', [
            'proposal' => $proposal,
            'quoteRequest' => $proposal->getQuoteRequest(),
            'viewerContext' => 'client',
        ]);
    }

    #[Route('/devis/{publicReference}/pdf', name: '_quote_proposal_pdf', methods: ['GET'])]
    public function showProposalPdf(
        string $publicReference,
        QuoteProposalRepository $quoteProposalRepository,
        UploaderHelper $uploaderHelper,
        RequestStack $requestStack,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getClientProfile() || !$this->isGranted('ROLE_CLIENT')) {
            throw $this->createAccessDeniedException('Accès réservé aux clients.');
        }

        $proposal = $quoteProposalRepository->findOneVisibleForClientByPublicReference(
            $publicReference,
            $user->getClientProfile()
        );

        if (!$proposal instanceof QuoteProposal) {
            throw $this->createNotFoundException('Devis introuvable.');
        }

        $prestataire = $proposal->getQuoteRequest()?->getPrestataire();
        $prestataireLogoUrl = null;

        if ($prestataire instanceof PrestataireProfile && $prestataire->getLogo()) {
            $relativePath = $uploaderHelper->asset($prestataire, 'logoFile');

            if ($relativePath) {
                $request = $requestStack->getCurrentRequest();

                if ($request instanceof Request) {
                    $prestataireLogoUrl = $request->getSchemeAndHttpHost() . $relativePath;
                }
            }
        }

        $html = $this->renderView('quote_request/proposal_pdf.html.twig', [
            'proposal' => $proposal,
            'quoteRequest' => $proposal->getQuoteRequest(),
            'prestataireLogoUrl' => $prestataireLogoUrl,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf(
                    'inline; filename="%s.pdf"',
                    $proposal->getProposalNumber() ?: 'devis'
                ),
            ]
        );
    }

    #[Route('/devis/{publicReference}/accept', name: '_quote_proposal_accept', methods: ['POST'])]
    public function acceptProposal(
        string $publicReference,
        Request $request,
        QuoteProposalRepository $quoteProposalRepository,
        EntityManagerInterface $entityManager,
        NotificationManager $notificationManager,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getClientProfile() || !$this->isGranted('ROLE_CLIENT')) {
            throw $this->createAccessDeniedException('Accès réservé aux clients.');
        }

        $proposal = $quoteProposalRepository->findOneVisibleForClientByPublicReference(
            $publicReference,
            $user->getClientProfile()
        );

        if (!$proposal instanceof QuoteProposal) {
            throw $this->createNotFoundException('Devis introuvable.');
        }

        if (!$this->isCsrfTokenValid(
            'accept_quote_proposal_' . $proposal->getId(),
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if (!$proposal->isFinalized()) {
            $this->addFlash('warning', 'Ce devis ne peut pas être accepté.');

            return $this->redirectToRoute('app_quote_request_quote_proposal_show', [
                'publicReference' => $proposal->getPublicReference(),
            ], 303);
        }

        $proposal->setStatus(QuoteProposalStatusEnum::ACCEPTED);
        $proposal->setAcceptedAt(new \DateTimeImmutable());
        $proposal->setUpdatedAt(new \DateTime());

        $quoteRequest = $proposal->getQuoteRequest();
        if ($quoteRequest instanceof QuoteRequest) {
            $quoteRequest->setStatus(QuoteRequestStatusEnum::CLOSED);
            $quoteRequest->setUpdatedAt(new \DateTimeImmutable());
        }

        $prestataireUser = $proposal->getPrestataire()?->getAccount();
        if ($prestataireUser instanceof User) {
            $notificationManager->notify(
                $prestataireUser,
                NotificationTypeEnum::QUOTE_PROPOSAL_RECEIVED,
                'Devis accepté',
                'Votre devis a été accepté par le client.',
                $this->generateUrl('app_prestataire_quote_request_show', [
                    'slug' => $quoteRequest?->getSlug(),
                ]),
                [
                    'quoteProposalId' => $proposal->getId(),
                    'quoteProposalReference' => $proposal->getPublicReference(),
                    'quoteProposalNumber' => $proposal->getProposalNumber(),
                    'quoteRequestId' => $quoteRequest?->getId(),
                    'quoteRequestSlug' => $quoteRequest?->getSlug(),
                ]
            );
        }

        $entityManager->flush();

        $this->addFlash('success', 'Le devis a bien été accepté.');

        return $this->redirectToRoute('app_quote_request_quote_proposal_show', [
            'publicReference' => $proposal->getPublicReference(),
        ], 303);
    }

    #[Route('/historique', name: '_history', methods: ['GET'])]
    public function history(
        Request $request,
        QuoteRequestRepository $quoteRequestRepository,
        PaginatorInterface $paginator,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getClientProfile() || !$this->isGranted('ROLE_CLIENT')) {
            throw $this->createAccessDeniedException('Accès réservé aux clients.');
        }

        $queryBuilder = $quoteRequestRepository->createArchivedForClientQueryBuilder($user->getClientProfile());

        $archivedQuoteRequests = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            8
        );

        return $this->render('quote_request/archives.html.twig', [
            'archivedQuoteRequests' => $archivedQuoteRequests,
        ]);
    }

    #[Route('/historique/{slug}', name: '_history_show', methods: ['GET'])]
    public function historyShow(
        string $slug,
        QuoteRequestRepository $quoteRequestRepository,
        ConversationRepository $conversationRepository,
        MessageRepository $messageRepository,
        QuoteProposalRepository $quoteProposalRepository,
    ): Response {

        $user = $this->getUser();

        if (!$user instanceof User || !$user->getClientProfile() || !$this->isGranted('ROLE_CLIENT')) {
            throw $this->createAccessDeniedException('Accès réservé aux clients.');
        }

        $quoteRequest = $quoteRequestRepository->findOneArchivedForClientBySlug($slug, $user->getClientProfile());

        if (!$quoteRequest instanceof QuoteRequest) {
            throw $this->createNotFoundException('Cette demande archivée est introuvable.');
        }

        $conversation = $conversationRepository->findOneByQuoteRequest($quoteRequest);
        $messages = $conversation ? $messageRepository->findByConversationOrderedByCreatedAt($conversation) : [];

$quoteResponses = $quoteProposalRepository->findBy(
    [
        'quoteRequest' => $quoteRequest,
        'deletedAt' => null,
    ],
    [
        'finalizedAt' => 'DESC',
        'createdAt' => 'DESC',
    ]
);

$quoteResponses = array_values(array_filter(
    $quoteResponses,
    static fn(QuoteProposal $proposal): bool => $proposal->isFinalized() || $proposal->isAccepted()
));

        return $this->render('quote_request/archived_show.html.twig', [
            'quoteRequest' => $quoteRequest,
            'conversation' => $conversation,
            'messages' => $messages,
            'quoteResponses' => $quoteResponses,
        ]);
    }

    #[Route('/{slug}/archive', name: '_mark_as_archived', methods: ['POST'])]
    public function markAsArchived(
        Request $request,
        string $slug,
        QuoteRequestRepository $quoteRequestRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getClientProfile() || !$this->isGranted('ROLE_CLIENT')) {
            throw $this->createAccessDeniedException('Accès réservé aux clients.');
        }

        $quoteRequest = $quoteRequestRepository->findOneActiveForClientBySlug($slug, $user->getClientProfile());

        if (!$quoteRequest instanceof QuoteRequest) {
            throw $this->createNotFoundException('Cette demande n’est pas disponible.');
        }

        if (!$this->isCsrfTokenValid(
            'archive-quote-request-' . $quoteRequest->getId(),
            (string) $request->request->get('_token')
        )) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_quote_request_show', [
                'slug' => $quoteRequest->getSlug(),
            ]);
        }

        if (null !== $quoteRequest->getArchivedByClientAt()) {
            $this->addFlash('warning', 'Cette demande est déjà archivée.');

            return $this->redirectToRoute('app_quote_request_history');
        }

        $quoteRequest
            ->setArchivedByClientAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        $this->addFlash('success', 'La demande de devis a bien été archivée.');

        return $this->redirectToRoute('app_quote_request_history');
    }
}
