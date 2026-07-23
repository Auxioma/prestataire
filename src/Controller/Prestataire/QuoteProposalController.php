<?php

namespace App\Controller\Prestataire;

use App\Entity\Conversation;
use App\Entity\PrestataireProfile;
use App\Entity\QuoteProposal;
use App\Entity\QuoteRequest;
use App\Entity\User;
use App\Enum\NotificationTypeEnum;
use App\Enum\QuoteProposalStatusEnum;
use App\Enum\QuoteRequestStatusEnum;
use App\Form\QuoteProposalType;
use App\Repository\ConversationRepository;
use App\Repository\PrestataireProfileRepository;
use App\Repository\QuoteProposalRepository;
use App\Repository\QuoteRequestRepository;
use App\Service\NotificationManager;
use App\Service\QuoteProposalDocumentResolver;
use App\Service\QuoteProposalManager;
use App\Service\QuoteProposalNativePdfGenerator;
use App\Service\QuoteProposalPdfResponseFactory;
use App\Service\Subscription\SubscriptionAccessManager;
use App\Service\Subscription\SubscriptionCreditManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/prestataire/devis', name: 'app_prestataire_quote_proposal_')]
#[IsGranted('ROLE_PRESTATAIRE')]
/**
 * Gère les actions liées à quote proposal.
 */
class QuoteProposalController extends AbstractController
{
    #[Route('/new/{id}', name: 'new', methods: ['GET'])]
    /**
     * Affiche et traite le formulaire de création.
     *
     * @return Response
     */
    public function new(
        int $id,
        Request $request,
        QuoteRequestRepository $quoteRequestRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
        ConversationRepository $conversationRepository,
        QuoteProposalManager $quoteProposalManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $quoteRequest = $quoteRequestRepository->find($id);

        if (!$quoteRequest instanceof QuoteRequest) {
            throw $this->createNotFoundException('Demande introuvable.');
        }

        $prestataire = $this->getCurrentPrestataire($prestataireProfileRepository);
        $this->assertPrestataireCanAccessQuoteRequest($quoteRequest, $prestataire, $conversationRepository);

        $conversation = $this->resolveConversation($request, $conversationRepository, $quoteRequest, $prestataire);
        $proposal = $quoteProposalManager->getOrCreateDraft($quoteRequest, $prestataire, $conversation);

        return $this->redirectToRoute('app_prestataire_quote_proposal_edit', [
            'publicReference' => $proposal->getPublicReference(),
            'origin' => $request->query->get('origin'),
        ]);
    }

    #[Route('/{publicReference}/edit', name: 'edit', methods: ['GET', 'POST'])]
    /**
     * Affiche et traite le formulaire de modification.
     *
     * @return Response
     */
    public function edit(
        string $publicReference,
        Request $request,
        PrestataireProfileRepository $prestataireProfileRepository,
        QuoteProposalRepository $quoteProposalRepository,
        QuoteProposalManager $quoteProposalManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $prestataire = $this->getCurrentPrestataire($prestataireProfileRepository);

        $proposal = $quoteProposalRepository->findOneForPrestataireByPublicReferenceIncludingArchived($publicReference, $prestataire);

        if (!$proposal instanceof QuoteProposal) {
            throw $this->createNotFoundException('Devis introuvable.');
        }

        if ($proposal->isAccepted()) {
            $this->addFlash('warning', 'Ce devis a été accepté par le client et ne peut plus être modifié.');

            return $this->redirectToRoute('app_prestataire_quote_proposal_show', [
                'publicReference' => $proposal->getPublicReference(),
            ], 303);
        }

        if ($proposal->getStatus()->isDraft()) {
            $quoteProposalManager->refreshDraftSnapshot($proposal);
        }

        $form = $this->createForm(QuoteProposalType::class, $proposal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quoteProposalManager->save($proposal, true);

            $this->addFlash('success', 'Le devis a bien été enregistré. Si tout est correct, allez dans l’aperçu puis finalisez-le pour que le client puisse l’étudier.');

            return $this->redirectToRoute('app_prestataire_quote_proposal_edit', [
                'publicReference' => $proposal->getPublicReference(),
                'origin' => $request->query->get('origin'),
            ]);
        }

        $statusCode = $form->isSubmitted() && !$form->isValid()
            ? Response::HTTP_UNPROCESSABLE_ENTITY
            : Response::HTTP_OK;

        return $this->render('quote_proposal/edit.html.twig', [
            'proposal' => $proposal,
            'form' => $form->createView(),
            'origin' => $request->query->get('origin'),
        ], new Response(status: $statusCode));
    }

    #[Route('/{publicReference}', name: 'show', methods: ['GET'])]
    /**
     * Affiche le détail de la ressource demandée.
     *
     * @return Response
     */
    public function show(
        string $publicReference,
        PrestataireProfileRepository $prestataireProfileRepository,
        QuoteProposalRepository $quoteProposalRepository,
        QuoteProposalDocumentResolver $documentResolver,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $prestataire = $this->getCurrentPrestataire($prestataireProfileRepository);

        $proposal = $quoteProposalRepository->findOneForPrestataireByPublicReferenceIncludingArchived($publicReference, $prestataire);

        if (!$proposal instanceof QuoteProposal) {
            throw $this->createNotFoundException('Devis introuvable.');
        }

        if ($proposal->isArchivedByPrestataire()) {
            return $this->redirectToRoute('app_prestataire_quote_proposal_archived_show', [
                'publicReference' => $proposal->getPublicReference(),
            ], 303);
        }

        return $this->render('quote_proposal/show.html.twig', [
            'proposal' => $proposal,
            'isReadOnlyView' => false,
            'viewerContext' => 'prestataire',
            'resolvedDocument' => $documentResolver->resolve($proposal),
            'renderNativeDetails' => $documentResolver->shouldRenderNativeDetails($proposal),
        ]);
    }

    #[Route('/{publicReference}/finalize', name: 'finalize', methods: ['POST'])]
    /**
     * Traite l’action "finalize" du contrôleur Quote Proposal.
     *
     * @return Response
     */
    public function finalize(
        string $publicReference,
        Request $request,
        PrestataireProfileRepository $prestataireProfileRepository,
        QuoteProposalRepository $quoteProposalRepository,
        QuoteProposalManager $quoteProposalManager,
        NotificationManager $notificationManager,
        EntityManagerInterface $entityManager,
        SubscriptionAccessManager $subscriptionAccessManager,
        SubscriptionCreditManager $subscriptionCreditManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $prestataire = $this->getCurrentPrestataire($prestataireProfileRepository);

        $securedProposal = $quoteProposalRepository->findOneForPrestataireByPublicReference($publicReference, $prestataire);

        if (!$securedProposal instanceof QuoteProposal) {
            throw $this->createNotFoundException('Devis introuvable.');
        }

        if ($securedProposal->isAccepted()) {
            $this->addFlash('warning', 'Ce devis a déjà été accepté par le client.');

            return $this->redirectToRoute('app_prestataire_quote_proposal_show', [
                'publicReference' => $securedProposal->getPublicReference(),
            ], 303);
        }

        if (!$this->isCsrfTokenValid(
            'finalize-quote-proposal-' . $securedProposal->getId(),
            (string) $request->request->get('_token')
        )) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_prestataire_quote_proposal_show', [
                'publicReference' => $securedProposal->getPublicReference(),
            ], 303);
        }

        $activeSubscription = $subscriptionAccessManager->getCurrentUsableSubscription($prestataire);
        if (!$activeSubscription || !$activeSubscription->canRespondToQuoteRequests()) {
            $this->addFlash('warning', 'Un abonnement actif avec au moins un crédit est requis pour envoyer un devis.');

            return $this->redirectToRoute('app_subscription_index');
        }

        try {
            $quoteProposalManager->finalize($securedProposal);
        } catch (\DomainException $exception) {
            $this->addFlash('warning', $exception->getMessage());

            return $this->redirectToRoute('app_prestataire_quote_proposal_edit', [
                'publicReference' => $securedProposal->getPublicReference(),
            ], 303);
        }

        $quoteRequest = $securedProposal->getQuoteRequest();

        if ($quoteRequest instanceof QuoteRequest) {
            $subscriptionCreditManager->consumeQuoteResponseCredit(
                $activeSubscription,
                $quoteRequest,
                'Consommation automatique d’un crédit lors de l’envoi d’un devis finalisé.'
            );
        }

        if ($quoteRequest instanceof QuoteRequest && $quoteRequest->getStatus() !== QuoteRequestStatusEnum::CLOSED) {
            $quoteRequest->setStatus(QuoteRequestStatusEnum::ANSWERED);
            $quoteRequest->setUpdatedAt(new \DateTimeImmutable());
        }

        $clientUser = $quoteRequest?->getClient()?->getAccount();

        if ($clientUser instanceof User) {
            $notificationManager->notify(
                $clientUser,
                NotificationTypeEnum::QUOTE_PROPOSAL_RECEIVED,
                'Nouveau devis reçu',
                'Vous avez reçu un nouveau devis pour votre demande.',
                $this->generateUrl('app_quote_request_show', [
                    'slug' => $quoteRequest?->getSlug(),
                ]),
                [
                    'quoteProposalId' => $securedProposal->getId(),
                    'quoteProposalReference' => $securedProposal->getPublicReference(),
                    'quoteProposalNumber' => $securedProposal->getProposalNumber(),
                    'quoteRequestId' => $quoteRequest?->getId(),
                    'quoteRequestSlug' => $quoteRequest?->getSlug(),
                    'prestataireId' => $prestataire->getId(),
                ]
            );
        }

        $entityManager->flush();

        $this->addFlash('success', 'Le devis a bien été finalisé.');

        return $this->redirectToRoute('app_prestataire_quote_proposal_show', [
            'publicReference' => $securedProposal->getPublicReference(),
        ], 303);
    }

    #[Route('/{publicReference}/delete', name: 'delete', methods: ['POST'])]
    /**
     * Supprime la ressource demandée.
     *
     * @return Response
     */
    public function delete(
        string $publicReference,
        Request $request,
        PrestataireProfileRepository $prestataireProfileRepository,
        QuoteProposalRepository $quoteProposalRepository,
        QuoteProposalManager $quoteProposalManager,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $prestataire = $this->getCurrentPrestataire($prestataireProfileRepository);

        $proposal = $quoteProposalRepository->findOneForPrestataireByPublicReferenceIncludingArchived($publicReference, $prestataire);

        if (!$proposal instanceof QuoteProposal) {
            throw $this->createNotFoundException('Devis introuvable.');
        }

        if ($proposal->isAccepted()) {
            $this->addFlash('warning', 'Un devis accepté ne peut plus être supprimé.');

            return $this->redirectToRoute('app_prestataire_quote_proposal_show', [
                'publicReference' => $proposal->getPublicReference(),
            ], 303);
        }

        if (!$this->isCsrfTokenValid('delete_quote_proposal_' . $proposal->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $quoteProposalManager->softDelete($proposal);
        $entityManager->flush();

        $this->addFlash('success', 'Le devis a bien été supprimé.');

        return $this->redirectToRoute('app_prestataire_quote_request_show', [
            'slug' => $proposal->getQuoteRequest()->getSlug(),
        ]);
    }

    #[Route('/{publicReference}/archive', name: 'archive', methods: ['POST'])]
    /**
     * Traite l’action "archive" du contrôleur Quote Proposal.
     *
     * @return Response
     */
    public function archive(
        string $publicReference,
        Request $request,
        PrestataireProfileRepository $prestataireProfileRepository,
        QuoteProposalRepository $quoteProposalRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $prestataire = $this->getCurrentPrestataire($prestataireProfileRepository);

        $proposal = $quoteProposalRepository->findOneForPrestataireByPublicReferenceIncludingArchived($publicReference, $prestataire);

        if (!$proposal instanceof QuoteProposal) {
            throw $this->createNotFoundException('Devis introuvable.');
        }

        $quoteRequest = $proposal->getQuoteRequest();
        $linkedProposals = $quoteProposalRepository->findBy([
            'quoteRequest' => $quoteRequest,
            'deletedAt' => null,
        ]);

        if (!$this->isCsrfTokenValid(
            'archive_quote_proposal_' . $proposal->getId(),
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if (null !== $proposal->getArchivedByPrestataireAt()) {
            $this->addFlash('warning', 'Ce devis est déjà archivé.');

            return $this->redirectToRoute('app_prestataire_quote_request_show', [
                'slug' => $quoteRequest->getSlug(),
            ]);
        }

        $proposalUpdatedAt = new \DateTime();
        $requestUpdatedAt = new \DateTimeImmutable();
        $archivedAt = $requestUpdatedAt;

        $proposal
            ->setArchivedByPrestataireAt($archivedAt)
            ->setUpdatedAt($proposalUpdatedAt);

        if ($quoteRequest instanceof QuoteRequest) {
            $quoteRequest
                ->setArchivedByPrestataireAt($archivedAt)
                ->setUpdatedAt($requestUpdatedAt);

            foreach ($linkedProposals as $linkedProposal) {
                if (\in_array($linkedProposal->getStatus(), [
                    QuoteProposalStatusEnum::FINALIZED,
                    QuoteProposalStatusEnum::ACCEPTED,
                ], true)) {
                    $linkedProposal->setStatus(QuoteProposalStatusEnum::ARCHIVED);
                    $linkedProposal->setUpdatedAt($proposalUpdatedAt);
                }
            }
        }

        $entityManager->flush();

        $this->addFlash('success', 'Le devis a bien été archivé.');

        return $this->redirectToRoute('app_prestataire_quote_request_show', [
            'slug' => $quoteRequest->getSlug(),
        ], 303);
    }

    #[Route('/devis/{publicReference}/pdf', name: 'pdf', methods: ['GET'])]
    /**
     * Traite l’action "showPdf" du contrôleur Quote Proposal.
     *
     * @return Response
     */
    public function showPdf(
        string $publicReference,
        QuoteProposalRepository $quoteProposalRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
        QuoteProposalDocumentResolver $documentResolver,
        QuoteProposalPdfResponseFactory $pdfResponseFactory,
        QuoteProposalNativePdfGenerator $nativePdfGenerator,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$this->isGranted('ROLE_PRESTATAIRE')) {
            throw $this->createAccessDeniedException('Accès réservé aux prestataires.');
        }

        $prestataire = $this->getCurrentPrestataire($prestataireProfileRepository);

        $proposal = $quoteProposalRepository->findOneBy([
            'publicReference' => $publicReference,
            'prestataire' => $prestataire,
            'deletedAt' => null,
        ]);

        if (!$proposal instanceof QuoteProposal) {
            throw $this->createNotFoundException('Devis introuvable.');
        }

        $resolvedDocument = $documentResolver->resolve($proposal);

        if ($resolvedDocument->isStoredFile()) {
            return $pdfResponseFactory->createInlineResponse($resolvedDocument);
        }

        return new Response(
            $nativePdfGenerator->generatePdfOutput($proposal, 'quote_proposal/proposal_pdf.html.twig'),
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

    #[Route('/{publicReference}/archived-show', name: 'archived_show', methods: ['GET'])]
    /**
     * Traite l’action "archivedShow" du contrôleur Quote Proposal.
     *
     * @return Response
     */
    public function archivedShow(
        string $publicReference,
        PrestataireProfileRepository $prestataireProfileRepository,
        QuoteProposalRepository $quoteProposalRepository,
        QuoteProposalDocumentResolver $documentResolver,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $prestataire = $this->getCurrentPrestataire($prestataireProfileRepository);

        $proposal = $quoteProposalRepository->findOneForPrestataireByPublicReferenceIncludingArchived($publicReference, $prestataire);

        if (!$proposal instanceof QuoteProposal) {
            throw $this->createNotFoundException('Devis introuvable.');
        }

        if (null === $proposal->getArchivedByPrestataireAt()) {
            $this->addFlash('warning', 'Ce devis n’est pas archivé.');

            return $this->redirectToRoute('app_prestataire_quote_proposal_show', [
                'publicReference' => $proposal->getPublicReference(),
            ], 303);
        }

        return $this->render('quote_proposal/show.html.twig', [
            'proposal' => $proposal,
            'isReadOnlyView' => true,
            'viewerContext' => 'prestataire',
            'resolvedDocument' => $documentResolver->resolve($proposal),
            'renderNativeDetails' => $documentResolver->shouldRenderNativeDetails($proposal),
        ]);
    }

    private function getCurrentPrestataire(PrestataireProfileRepository $prestataireProfileRepository): PrestataireProfile
    {
        $user = $this->getUser();

        if ($user === null) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié.');
        }

        $prestataire = $prestataireProfileRepository->findOneBy(['account' => $user]);

        if (!$prestataire instanceof PrestataireProfile) {
            throw $this->createAccessDeniedException('Profil prestataire introuvable.');
        }

        return $prestataire;
    }

    private function assertPrestataireCanAccessQuoteRequest(
        QuoteRequest $quoteRequest,
        PrestataireProfile $prestataire,
        ConversationRepository $conversationRepository
    ): void {
        $conversation = $conversationRepository->findOneBy([
            'quoteRequest' => $quoteRequest,
            'prestataire' => $prestataire,
        ]);

        if ($conversation instanceof Conversation) {
            return;
        }

        throw $this->createAccessDeniedException('Accès non autorisé à cette demande.');
    }

    private function resolveConversation(
        Request $request,
        ConversationRepository $conversationRepository,
        QuoteRequest $quoteRequest,
        PrestataireProfile $prestataire
    ): ?Conversation {
        $conversationId = $request->query->get('conversation');

        if ($conversationId !== null) {
            $conversation = $conversationRepository->find($conversationId);

            if (
                $conversation instanceof Conversation
                && $conversation->getQuoteRequest()?->getId() === $quoteRequest->getId()
                && $conversation->getPrestataire()?->getId() === $prestataire->getId()
            ) {
                return $conversation;
            }
        }

        return $conversationRepository->findOneBy([
            'quoteRequest' => $quoteRequest,
            'prestataire' => $prestataire,
        ]);
    }
}
