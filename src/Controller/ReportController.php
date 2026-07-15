<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\QuoteRequest;
use App\Entity\Report;
use App\Entity\Review;
use App\Entity\User;
use App\Form\ReportType;
use App\Service\ReportManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/signalements', name: 'app_report_')]
final class ReportController extends AbstractController
{
    #[Route('/demande/{id}', name: 'quote_request', methods: ['GET', 'POST'])]
    public function quoteRequest(Request $request, QuoteRequest $quoteRequest, ReportManager $reportManager): Response
    {
        $user = $this->getAuthenticatedUser();
        $report = new Report();
        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $reportManager->createQuoteRequestReport($user, $quoteRequest, $report);
            } catch (\DomainException $exception) {
                $this->addFlash('danger', $exception->getMessage());

                return $this->redirect($this->resolveQuoteRequestBackUrl($user, $quoteRequest));
            }

            $this->addFlash('success', 'Votre signalement a bien été transmis à notre équipe.');

            return $this->redirect($this->resolveQuoteRequestBackUrl($user, $quoteRequest));
        }

        return $this->render('report/create.html.twig', [
            'form' => $form->createView(),
            'contextTitle' => 'Signaler cette demande de devis',
            'contextDescription' => $quoteRequest->getTitle() ?? 'Demande de devis',
            'backUrl' => $this->resolveQuoteRequestBackUrl($user, $quoteRequest),
        ]);
    }

    #[Route('/conversation/{id}', name: 'conversation', methods: ['GET', 'POST'])]
    public function conversation(Request $request, Conversation $conversation, ReportManager $reportManager): Response
    {
        $user = $this->getAuthenticatedUser();
        $report = new Report();
        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $reportManager->createConversationReport($user, $conversation, $report);
            } catch (\DomainException $exception) {
                $this->addFlash('danger', $exception->getMessage());

                return $this->redirect($this->resolveConversationBackUrl($user, $conversation));
            }

            $this->addFlash('success', 'Votre signalement a bien été transmis à notre équipe.');

            return $this->redirect($this->resolveConversationBackUrl($user, $conversation));
        }

        return $this->render('report/create.html.twig', [
            'form' => $form->createView(),
            'contextTitle' => 'Signaler cette conversation',
            'contextDescription' => $conversation->getQuoteRequest()?->getTitle() ?? 'Conversation liée à une demande',
            'backUrl' => $this->resolveConversationBackUrl($user, $conversation),
        ]);
    }

    #[Route('/avis/{id}', name: 'review', methods: ['GET', 'POST'])]
    public function review(Request $request, Review $review, ReportManager $reportManager): Response
    {
        $user = $this->getAuthenticatedUser();
        $report = new Report();
        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $reportManager->createReviewReport($user, $review, $report);
            } catch (\DomainException $exception) {
                $this->addFlash('danger', $exception->getMessage());

                return $this->redirectToRoute('app_review_prestataire_reviews');
            }

            $this->addFlash('success', 'Votre signalement a bien été transmis à notre équipe.');

            return $this->redirectToRoute('app_review_prestataire_reviews');
        }

        return $this->render('report/create.html.twig', [
            'form' => $form->createView(),
            'contextTitle' => 'Signaler cet avis',
            'contextDescription' => $review->getQuoteRequest()?->getTitle() ?? 'Avis reçu',
            'backUrl' => $this->generateUrl('app_review_prestataire_reviews'),
        ]);
    }

    private function getAuthenticatedUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour effectuer ce signalement.');
        }

        return $user;
    }

    private function resolveQuoteRequestBackUrl(User $user, QuoteRequest $quoteRequest): string
    {
        if ($user->getPrestataireProfile() && $quoteRequest->getPrestataire()?->getId() === $user->getPrestataireProfile()?->getId()) {
            return $this->generateUrl('app_prestataire_quote_request_show', ['slug' => $quoteRequest->getSlug()]);
        }

        return $this->generateUrl(
            $quoteRequest->isArchivedByClient() ? 'app_quote_request_history_show' : 'app_quote_request_show',
            ['slug' => $quoteRequest->getSlug()]
        );
    }

    private function resolveConversationBackUrl(User $user, Conversation $conversation): string
    {
        if ($user->getPrestataireProfile() && $conversation->getPrestataire()?->getId() === $user->getPrestataireProfile()?->getId()) {
            return $this->generateUrl('app_prestataire_dashboard', [
                'tab' => 'messages',
                'conversation' => $conversation->getId(),
            ]) . '#messages-main-panel';
        }

        return $this->generateUrl('app_quote_request_show', [
            'slug' => $conversation->getQuoteRequest()?->getSlug(),
            '_fragment' => 'quote-conversation',
        ]);
    }
}
