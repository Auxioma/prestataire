<?php

namespace App\Service;

use App\Entity\ClientProfile;
use App\Entity\PrestataireProfile;
use App\Entity\QuoteRequest;
use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ReviewManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReviewRepository $reviewRepository,
    ) {
    }

    public function canClientReviewQuoteRequest(ClientProfile $client, QuoteRequest $quoteRequest): bool
    {
        if ($quoteRequest->getClient()?->getId() !== $client->getId()) {
            return false;
        }

        if (!$this->reviewRepository->hasAcceptedProposalForQuoteRequest($quoteRequest)) {
            return false;
        }

        return null === $this->reviewRepository->findOneByQuoteRequest($quoteRequest);
    }

    public function createReview(
        ClientProfile $client,
        PrestataireProfile $prestataire,
        QuoteRequest $quoteRequest,
        int $rating,
        ?string $comment
    ): Review {
        if ($quoteRequest->getClient()?->getId() !== $client->getId()) {
            throw new \DomainException('Cette demande n’appartient pas au client.');
        }

        if ($quoteRequest->getPrestataire()?->getId() !== $prestataire->getId()) {
            throw new \DomainException('Le prestataire de l’avis ne correspond pas à la demande.');
        }

        if (!$this->canClientReviewQuoteRequest($client, $quoteRequest)) {
            throw new \DomainException('Cette demande n’est pas éligible à un avis.');
        }

        $review = (new Review())
            ->setClientProfile($client)
            ->setPrestataireProfile($prestataire)
            ->setQuoteRequest($quoteRequest)
            ->setRating($rating)
            ->setComment($this->normalizeComment($comment));

        $this->entityManager->persist($review);
        $this->entityManager->flush();

        $this->refreshAverageRating($prestataire);

        return $review;
    }

    public function deleteReview(Review $review): void
    {
        $prestataire = $review->getPrestataireProfile();

        if (!$prestataire instanceof PrestataireProfile) {
            throw new \DomainException('Impossible de recalculer la réputation de ce prestataire.');
        }

        $this->entityManager->remove($review);
        $this->entityManager->flush();

        $this->refreshAverageRating($prestataire);
    }

    public function refreshAverageRating(PrestataireProfile $prestataire): void
    {
        $averageRating = $this->reviewRepository->computeAverageRating($prestataire);
        $reviewsCount = $this->reviewRepository->countByPrestataire($prestataire);

        $prestataire->setAverageRating(number_format($averageRating ?? 0, 2, '.', ''));
        $prestataire->setReviewsCount($reviewsCount);

        $this->entityManager->flush();
    }

    private function normalizeComment(?string $comment): ?string
    {
        $comment = null !== $comment ? trim($comment) : null;

        return '' === $comment ? null : $comment;
    }
}
