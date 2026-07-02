<?php

namespace App\Controller;

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
use App\Service\QuoteProposalManager;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[Route('/prestataire/devis', name: 'app_prestataire_quote_proposal_')]
class QuoteProposalController extends AbstractController
{
    #[Route('/new/{id}', name: 'new', methods: ['GET'])]
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
    public function edit(
        string $publicReference,
        Request $request,
        PrestataireProfileRepository $prestataireProfileRepository,
        QuoteProposalRepository $quoteProposalRepository,
        QuoteProposalManager $quoteProposalManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $prestataire = $this->getCurrentPrestataire($prestataireProfileRepository);

        $proposal = $quoteProposalRepository->findOneForPrestataireByPublicReference($publicReference, $prestataire);

        if (!$proposal instanceof QuoteProposal) {
            throw $this->createNotFoundException('Devis introuvable.');
        }

        if ($proposal->getStatus()->isDraft()) {
            $quoteProposalManager->refreshDraftSnapshot($proposal);
        }

        $form = $this->createForm(QuoteProposalType::class, $proposal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quoteProposalManager->save($proposal, true);

            $this->addFlash('success', 'Le devis a bien été enregistré.');

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
    public function show(
        string $publicReference,
        PrestataireProfileRepository $prestataireProfileRepository,
        QuoteProposalRepository $quoteProposalRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $prestataire = $this->getCurrentPrestataire($prestataireProfileRepository);

        $proposal = $quoteProposalRepository->findOneForPrestataireByPublicReference($publicReference, $prestataire);

        if (!$proposal instanceof QuoteProposal) {
            throw $this->createNotFoundException('Devis introuvable.');
        }

        return $this->render('quote_proposal/show.html.twig', [
            'proposal' => $proposal,
            'isReadOnlyView' => false,
            'viewerContext' => 'prestataire',
        ]);
    }

    #[Route('/{publicReference}/finalize', name: 'finalize', methods: ['POST'])]
    public function finalize(
        string $publicReference,
        Request $request,
        PrestataireProfileRepository $prestataireProfileRepository,
        QuoteProposalRepository $quoteProposalRepository,
        QuoteProposalManager $quoteProposalManager,
        NotificationManager $notificationManager,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $prestataire = $this->getCurrentPrestataire($prestataireProfileRepository);

        $securedProposal = $quoteProposalRepository->findOneForPrestataireByPublicReference($publicReference, $prestataire);

        if (!$securedProposal instanceof QuoteProposal) {
            throw $this->createNotFoundException('Devis introuvable.');
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

        if ($securedProposal->getItems()->isEmpty()) {
            $this->addFlash('warning', 'Ajoutez au moins une ligne avant de finaliser le devis.');

            return $this->redirectToRoute('app_prestataire_quote_proposal_edit', [
                'publicReference' => $securedProposal->getPublicReference(),
            ], 303);
        }

        $quoteProposalManager->finalize($securedProposal);

        $quoteRequest = $securedProposal->getQuoteRequest();

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

        $proposal = $quoteProposalRepository->findOneForPrestataireByPublicReference($publicReference, $prestataire);

        if (!$proposal instanceof QuoteProposal) {
            throw $this->createNotFoundException('Devis introuvable.');
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
    public function archive(
        string $publicReference,
        Request $request,
        PrestataireProfileRepository $prestataireProfileRepository,
        QuoteProposalRepository $quoteProposalRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $prestataire = $this->getCurrentPrestataire($prestataireProfileRepository);

        $proposal = $quoteProposalRepository->findOneForPrestataireByPublicReference($publicReference, $prestataire);

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

        $proposal
            ->setArchivedByPrestataireAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        if ($quoteRequest instanceof QuoteRequest) {
            $quoteRequest
                ->setArchivedByPrestataireAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());

            foreach ($linkedProposals as $linkedProposal) {
                if ($linkedProposal->getStatus() === QuoteProposalStatusEnum::FINALIZED) {
                    $linkedProposal->setStatus(QuoteProposalStatusEnum::ARCHIVED);
                    $linkedProposal->setUpdatedAt(new \DateTimeImmutable());
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
    public function showPdf(
        string $publicReference,
        QuoteProposalRepository $quoteProposalRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
        UploaderHelper $uploaderHelper,
        RequestStack $requestStack,
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

        if (!$proposal->isFinalized()) {
            throw $this->createNotFoundException('Ce devis n’est pas disponible.');
        }

        $prestataireLogoUrl = null;

        if ($prestataire->getLogo()) {
            $relativePath = $uploaderHelper->asset($prestataire, 'logoFile');

            if ($relativePath) {
                $request = $requestStack->getCurrentRequest();

                if ($request instanceof Request) {
                    $prestataireLogoUrl = $request->getSchemeAndHttpHost() . $relativePath;
                }
            }
        }

        $html = $this->renderView('quote_proposal/proposal_pdf.html.twig', [
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

    #[Route('/{publicReference}/archived-show', name: 'archived_show', methods: ['GET'])]
    public function archivedShow(
        string $publicReference,
        PrestataireProfileRepository $prestataireProfileRepository,
        QuoteProposalRepository $quoteProposalRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $prestataire = $this->getCurrentPrestataire($prestataireProfileRepository);

        $proposal = $quoteProposalRepository->findOneForPrestataireByPublicReference($publicReference, $prestataire);

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
