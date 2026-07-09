<?php

namespace App\Service\Subscription;

use App\Entity\QuoteRequest;
use App\Entity\Subscription\PrestataireSubscription;
use App\Entity\Subscription\SubscriptionCreditMovement;
use App\Enum\SubscriptionCreditMovementTypeEnum;
use App\Repository\Subscription\SubscriptionCreditMovementRepository;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionCreditManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SubscriptionCreditMovementRepository $subscriptionCreditMovementRepository,
    ) {
    }

    public function grantCredits(
        PrestataireSubscription $subscription,
        int $credits,
        SubscriptionCreditMovementTypeEnum $type,
        ?string $description = null,
        ?array $metadata = null,
    ): SubscriptionCreditMovement {
        if ($credits <= 0) {
            throw new \InvalidArgumentException('Le nombre de crédits ajoutés doit être strictement positif.');
        }

        $subscription->grantCredits($credits)->setUpdatedAt(new \DateTimeImmutable());

        $movement = (new SubscriptionCreditMovement())
            ->setPrestataireProfile($subscription->getPrestataireProfile())
            ->setSubscription($subscription)
            ->setType($type)
            ->setCreditsDelta($credits)
            ->setBalanceAfter($subscription->getRemainingCredits())
            ->setDescription($description)
            ->setMetadata($metadata);

        $this->entityManager->persist($movement);

        return $movement;
    }

    public function consumeQuoteResponseCredit(
        PrestataireSubscription $subscription,
        QuoteRequest $quoteRequest,
        ?string $description = null,
    ): SubscriptionCreditMovement {
        if (!$subscription->canRespondToQuoteRequests()) {
            throw new \DomainException('Le prestataire ne dispose pas des droits nécessaires pour répondre à ce devis.');
        }

        $existingMovement = $this->subscriptionCreditMovementRepository->findOneConsumptionForQuoteRequest($quoteRequest);
        if ($existingMovement instanceof SubscriptionCreditMovement) {
            return $existingMovement;
        }

        $subscription->consumeCredits(1)->setUpdatedAt(new \DateTimeImmutable());

        $movement = (new SubscriptionCreditMovement())
            ->setPrestataireProfile($subscription->getPrestataireProfile())
            ->setSubscription($subscription)
            ->setQuoteRequest($quoteRequest)
            ->setType(SubscriptionCreditMovementTypeEnum::QUOTE_RESPONSE_CONSUMPTION)
            ->setCreditsDelta(-1)
            ->setBalanceAfter($subscription->getRemainingCredits())
            ->setDescription($description ?? 'Consommation automatique d’un crédit pour répondre à une demande de devis.');

        $this->entityManager->persist($movement);

        return $movement;
    }
}
