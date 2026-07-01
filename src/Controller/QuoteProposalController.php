<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\PrestataireProfile;
use App\Entity\QuoteProposal;
use App\Entity\QuoteRequest;
use App\Form\QuoteProposalType;
use App\Repository\ConversationRepository;
use App\Repository\PrestataireProfileRepository;
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
        QuoteRequest $quoteRequest,
        Request $request,
        PrestataireProfileRepository $prestataireProfileRepository,
        ConversationRepository $conversationRepository,
        QuoteProposalManager $quoteProposalManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $prestataire = $this->getCurrentPrestataire($prestataireProfileRepository);
        $this->assertPrestataireCanAccessQuoteRequest($quoteRequest, $prestataire, $conversationRepository);

        $conversation = $this->resolveConversation($request, $conversationRepository, $quoteRequest, $prestataire);
        $proposal = $quoteProposalManager->getOrCreateDraft($quoteRequest, $prestataire, $conversation);

        return $this->redirectToRoute('app_prestataire_quote_proposal_edit', [
            'id' => $proposal->getId(),
            'origin' => $request->query->get('origin'),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        QuoteProposal $proposal,
        Request $request,
        PrestataireProfileRepository $prestataireProfileRepository,
        QuoteProposalManager $quoteProposalManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $prestataire = $this->getCurrentPrestataire($prestataireProfileRepository);
        $this->assertPrestataireOwnsProposal($proposal, $prestataire);

        if (!$proposal->getStatus()->isDraft()) {
            $this->addFlash('warning', 'Seuls les devis en brouillon peuvent être modifiés.');

            return $this->redirectToRoute('app_prestataire_quote_proposal_show', [
                'id' => $proposal->getId(),
            ]);
        }

        $quoteProposalManager->refreshDraftSnapshot($proposal);

        $form = $this->createForm(QuoteProposalType::class, $proposal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quoteProposalManager->save($proposal, true);

            $this->addFlash('success', 'Le devis a bien été enregistré.');

            return $this->redirectToRoute('app_prestataire_quote_proposal_edit', [
                'id' => $proposal->getId(),
                'origin' => $request->query->get('origin'),
            ]);
        }

        return $this->render('quote_proposal/edit.html.twig', [
            'proposal' => $proposal,
            'form' => $form->createView(),
            'origin' => $request->query->get('origin'),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        QuoteProposal $proposal,
        PrestataireProfileRepository $prestataireProfileRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $prestataire = $this->getCurrentPrestataire($prestataireProfileRepository);
        $this->assertPrestataireOwnsProposal($proposal, $prestataire);

        return $this->render('quote_proposal/show.html.twig', [
            'proposal' => $proposal,
        ]);
    }

    #[Route('/{id}/finalize', name: 'finalize', methods: ['POST'])]
    public function finalize(
        QuoteProposal $proposal,
        Request $request,
        PrestataireProfileRepository $prestataireProfileRepository,
        QuoteProposalManager $quoteProposalManager,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $prestataire = $this->getCurrentPrestataire($prestataireProfileRepository);
        $this->assertPrestataireOwnsProposal($proposal, $prestataire);

        if (!$this->isCsrfTokenValid('finalize_quote_proposal_' . $proposal->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($proposal->getStatus()->isDeleted()) {
            $this->addFlash('warning', 'Ce devis a été supprimé.');

            return $this->redirectToRoute('app_prestataire_quote_proposal_show', [
                'id' => $proposal->getId(),
            ]);
        }

        $quoteProposalManager->finalize($proposal);
        $entityManager->flush();

        $this->addFlash('success', 'Le devis a bien été finalisé.');

        return $this->redirectToRoute('app_prestataire_quote_proposal_show', [
            'id' => $proposal->getId(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        QuoteProposal $proposal,
        Request $request,
        PrestataireProfileRepository $prestataireProfileRepository,
        QuoteProposalManager $quoteProposalManager,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PRESTATAIRE');

        $prestataire = $this->getCurrentPrestataire($prestataireProfileRepository);
        $this->assertPrestataireOwnsProposal($proposal, $prestataire);

        if (!$this->isCsrfTokenValid('delete_quote_proposal_' . $proposal->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $quoteProposalManager->softDelete($proposal);
        $entityManager->flush();

        $this->addFlash('success', 'Le devis a bien été supprimé.');

        return $this->redirectToRoute('app_prestataire_quote_request_show', [
            'id' => $proposal->getQuoteRequest()->getId(),
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

    private function assertPrestataireOwnsProposal(QuoteProposal $proposal, PrestataireProfile $prestataire): void
    {
        if ($proposal->getPrestataire()?->getId() !== $prestataire->getId()) {
            throw $this->createAccessDeniedException('Accès non autorisé à ce devis.');
        }
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