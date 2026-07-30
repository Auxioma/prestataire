<?php

namespace App\Controller;

use App\Entity\PrestataireProfile;
use App\Entity\QuoteRequest;
use App\Entity\Review;
use App\Entity\User;
use App\Form\ReviewType;
use App\Repository\QuoteRequestRepository;
use App\Repository\ReviewRepository;
use App\Service\ReviewManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/avis', name: 'app_review_')]
final class ReviewController extends AbstractController
{
    #[Route('/demande/{quoteRequestSlug}/nouveau', name: 'create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        string $quoteRequestSlug,
        QuoteRequestRepository $quoteRequestRepository,
        ReviewManager $reviewManager,
    ): Response {
        $user = $this->getAuthenticatedClientUser();
        $client = $user->getClientProfile();
        $quoteRequest = $quoteRequestRepository->findOneForClientBySlug($quoteRequestSlug, $client);

        if (!$quoteRequest instanceof QuoteRequest) {
            throw $this->createNotFoundException('Cette demande n’est pas disponible.');
        }

        if (!$reviewManager->canClientReviewQuoteRequest($client, $quoteRequest)) {
            $this->addFlash('warning', 'Cette demande n’est pas éligible à un avis.');

            return $this->redirectToRoute($this->getQuoteRequestRouteName($quoteRequest), [
                'slug' => $quoteRequest->getSlug(),
            ]);
        }

        $prestataire = $quoteRequest->getPrestataire();

        if (null === $prestataire) {
            $this->addFlash('danger', 'Aucun prestataire ne peut être associé à cet avis.');

            return $this->redirectToRoute($this->getQuoteRequestRouteName($quoteRequest), [
                'slug' => $quoteRequest->getSlug(),
            ]);
        }

        $review = new Review();
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $reviewManager->createReview(
                    $client,
                    $prestataire,
                    $quoteRequest,
                    (int) $review->getRating(),
                    $review->getComment()
                );
            } catch (\DomainException $exception) {
                $this->addFlash('danger', $exception->getMessage());

                return $this->redirectToRoute($this->getQuoteRequestRouteName($quoteRequest), [
                    'slug' => $quoteRequest->getSlug(),
                ]);
            }

            $this->addFlash('success', 'Votre avis a bien été enregistré.');

            return $this->redirectToRoute('app_review_my_reviews');
        }

        return $this->render('review/create.html.twig', [
            'form' => $form->createView(),
            'quoteRequest' => $quoteRequest,
            'prestataire' => $prestataire,
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Review $review,
        ReviewManager $reviewManager,
    ): Response {
        $user = $this->getAuthenticatedClientUser();
        $client = $user->getClientProfile();

        if ($review->getClientProfile()?->getId() !== $client->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cet avis.');
        }

        $prestataire = $review->getPrestataireProfile();
        $quoteRequest = $review->getQuoteRequest();

        if (!$prestataire instanceof PrestataireProfile || !$quoteRequest instanceof QuoteRequest) {
            throw $this->createNotFoundException('Cet avis n’est pas modifiable.');
        }

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $reviewManager->updateReview(
                    $review,
                    (int) $review->getRating(),
                    $review->getComment()
                );
            } catch (\DomainException $exception) {
                $this->addFlash('danger', $exception->getMessage());

                return $this->redirectToRoute('app_review_my_reviews');
            }

            $this->addFlash('success', 'Votre avis a bien été modifié.');

            return $this->redirectToRoute('app_review_my_reviews');
        }

        return $this->render('review/create.html.twig', [
            'form' => $form->createView(),
            'quoteRequest' => $quoteRequest,
            'prestataire' => $prestataire,
            'review' => $review,
            'isEdit' => true,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Review $review,
        ReviewManager $reviewManager,
    ): Response {
        $user = $this->getAuthenticatedClientUser();
        $client = $user->getClientProfile();

        if ($review->getClientProfile()?->getId() !== $client->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cet avis.');
        }

        if (!$this->isCsrfTokenValid('delete-review-'.$review->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_review_my_reviews');
        }

        $reviewManager->deleteReview($review);
        $this->addFlash('success', 'Votre avis a bien été supprimé.');

        return $this->redirectToRoute('app_review_my_reviews');
    }

    #[Route('/mes-avis', name: 'my_reviews', methods: ['GET'])]
    public function myReviews(
        ReviewRepository $reviewRepository,
    ): Response {
        $user = $this->getAuthenticatedClientUser();
        $client = $user->getClientProfile();

        return $this->render('review/my_reviews.html.twig', [
            'reviews' => $reviewRepository->findByClientOrderedByDate($client),
            'pendingQuoteRequests' => $reviewRepository->findEligibleQuoteRequestsForClient($client),
        ]);
    }

    #[Route('/mes-avis-recus', name: 'prestataire_reviews', methods: ['GET'])]
    public function prestataireReviews(
        ReviewRepository $reviewRepository,
    ): Response {
        $user = $this->getAuthenticatedPrestataireUser();
        $prestataire = $user->getPrestataireProfile();

        return $this->render('review/prestataire_reviews.html.twig', [
            'prestataire' => $prestataire,
            'reviews' => $reviewRepository->findByPrestataireOrderedByDate($prestataire),
        ]);
    }

    #[Route('/prestataire/{slug}', name: 'public_prestataire_reviews', methods: ['GET'], requirements: ['slug' => '(?!mes-avis$|mes-avis-recus$)[a-z0-9-]+'])]
    public function publicPrestataireReviews(
        #[MapEntity(mapping: ['slug' => 'slug'])] PrestataireProfile $prestataire,
        ReviewRepository $reviewRepository,
    ): Response {
        if (!$prestataire->getCompanyName()) {
            throw $this->createNotFoundException('Ce profil professionnel n’est pas disponible.');
        }

        return $this->render('review/public_prestataire_reviews.html.twig', [
            'prestataire' => $prestataire,
            'reviews' => $reviewRepository->findPublicByPrestataireOrderedByDate($prestataire),
        ]);
    }

    private function getAuthenticatedClientUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getClientProfile() || !$this->isGranted('ROLE_CLIENT')) {
            throw $this->createAccessDeniedException('Accès réservé aux clients.');
        }

        return $user;
    }

    private function getAuthenticatedPrestataireUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getPrestataireProfile() || !$this->isGranted('ROLE_PRESTATAIRE')) {
            throw $this->createAccessDeniedException('Accès réservé aux prestataires.');
        }

        return $user;
    }

    private function getQuoteRequestRouteName(QuoteRequest $quoteRequest): string
    {
        return $quoteRequest->isArchivedByClient()
            ? 'app_quote_request_history_show'
            : 'app_quote_request_show';
    }
}
