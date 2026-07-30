<?php

namespace App\Service;

use App\Entity\ClientProfile;
use App\Entity\PrestataireProfile;
use App\Entity\QuoteRequest;
use App\Entity\Review;
use App\Enum\NotificationTypeEnum;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ReviewManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReviewRepository $reviewRepository,
        private readonly NotificationManager $notificationManager,
        private readonly UrlGeneratorInterface $urlGenerator,
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
        $this->notifyPrestataireOfNewReview($review);

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

    public function updateReview(Review $review, int $rating, ?string $comment): Review
    {
        $prestataire = $review->getPrestataireProfile();

        if (!$prestataire instanceof PrestataireProfile) {
            throw new \DomainException('Impossible de mettre à jour cet avis.');
        }

        $review
            ->setRating($rating)
            ->setComment($this->normalizeComment($comment))
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
        $this->refreshAverageRating($prestataire);

        return $review;
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

    private function notifyPrestataireOfNewReview(Review $review): void
    {
        $prestataireAccount = $review->getPrestataireProfile()?->getAccount();
        $quoteRequest = $review->getQuoteRequest();

        if (null === $prestataireAccount) {
            return;
        }

        $clientFirstName = trim((string) ($review->getClientProfile()?->getAccount()?->getFirstName() ?? ''));
        $rating = max(0, min(5, (int) ($review->getRating() ?? 0)));
        $title = 'Vous avez reçu un nouvel avis';
        $body = sprintf(
            '%s vous a laissé une note de %d/5%s.',
            '' !== $clientFirstName ? $clientFirstName : 'Un client',
            $rating,
            $quoteRequest?->getTitle() ? sprintf(' pour "%s"', $quoteRequest->getTitle()) : ''
        );

        $this->notificationManager->notify(
            $prestataireAccount,
            NotificationTypeEnum::REVIEW_RECEIVED,
            $title,
            $body,
            $this->urlGenerator->generate('app_review_prestataire_reviews') . '#review-' . $review->getId(),
            [
                'reviewId' => $review->getId(),
                'quoteRequestId' => $quoteRequest?->getId(),
                'quoteRequestSlug' => $quoteRequest?->getSlug(),
                'prestataireProfileId' => $review->getPrestataireProfile()?->getId(),
                'rating' => $rating,
            ]
        );
    }
}
