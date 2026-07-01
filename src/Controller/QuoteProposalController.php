<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\PrestataireProfile;
use App\Entity\QuoteProposal;
use App\Entity\QuoteRequest;
use App\Form\QuoteProposalType;
use App\Repository\ConversationRepository;
use App\Repository\PrestataireProfileRepository;
use App\Repository\QuoteProposalRepository;
use App\Repository\QuoteRequestRepository;
use App\Service\QuoteProposalManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        ]);
    }

    #[Route('/{publicReference}/finalize', name: 'finalize', methods: ['POST'])]
    public function finalize(
        string $publicReference,
        Request $request,
        PrestataireProfileRepository $prestataireProfileRepository,
        QuoteProposalRepository $quoteProposalRepository,
        QuoteProposalManager $quoteProposalManager,
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
