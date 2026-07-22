<?php

namespace App\Service\Subscription;

use App\Entity\PrestataireProfile;
use App\Entity\Subscription\PrestataireSubscription;
use App\Enum\SubscriptionBillingPeriodEnum;
use App\Enum\SubscriptionCreditMovementTypeEnum;
use App\Enum\SubscriptionStatusEnum;
use App\Repository\Subscription\PrestataireSubscriptionRepository;
use App\Repository\Subscription\SubscriptionPlanRepository;
use Doctrine\ORM\EntityManagerInterface;

final class SubscriptionFallbackManager
{
    private const FREE_PLAN_CODE = 'free';

    public function __construct(
        private readonly SubscriptionPlanRepository $subscriptionPlanRepository,
        private readonly PrestataireSubscriptionRepository $prestataireSubscriptionRepository,
        private readonly SubscriptionCreditManager $subscriptionCreditManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function shouldFallbackToFree(PrestataireSubscription $subscription, ?\DateTimeImmutable $at = null): bool
    {
        $at ??= new \DateTimeImmutable();
        $plan = $subscription->getPlan();

        if (null === $plan || self::FREE_PLAN_CODE === $plan->getCode()) {
            return false;
        }

        if ($subscription->getStatus()->isUsable()) {
            return false;
        }

        if ($subscription->getEndedAt() instanceof \DateTimeImmutable && $subscription->getEndedAt() <= $at) {
            return true;
        }

        if ($subscription->getCurrentPeriodEnd() instanceof \DateTimeImmutable && $subscription->getCurrentPeriodEnd() <= $at) {
            return true;
        }

        return \in_array($subscription->getStatus(), [
            SubscriptionStatusEnum::CANCELED,
            SubscriptionStatusEnum::UNPAID,
            SubscriptionStatusEnum::INCOMPLETE_EXPIRED,
            SubscriptionStatusEnum::PAUSED,
        ], true);
    }

    public function fallbackToFree(
        PrestataireProfile $prestataireProfile,
        ?PrestataireSubscription $sourceSubscription = null,
        ?string $reason = null,
    ): PrestataireSubscription {
        $sourceSubscription ??= $this->prestataireSubscriptionRepository->findLatestForPrestataire($prestataireProfile);

        $freePlan = $this->subscriptionPlanRepository->findOneActiveByCode(self::FREE_PLAN_CODE);
        if (null === $freePlan) {
            throw new \RuntimeException(sprintf('Le plan gratuit "%s" est introuvable.', self::FREE_PLAN_CODE));
        }

        $now = new \DateTimeImmutable();
        $freeSubscription = $this->prestataireSubscriptionRepository->findCurrentUsableForPrestataire($prestataireProfile, $now);
        $freeSubscriptionAlreadyUsable = $freeSubscription instanceof PrestataireSubscription
            && self::FREE_PLAN_CODE === $freeSubscription->getPlan()?->getCode();

        if (!$freeSubscriptionAlreadyUsable) {
            $freeSubscription = $this->prestataireSubscriptionRepository
                ->findLatestForPrestataireAndPlanCode($prestataireProfile, self::FREE_PLAN_CODE)
                ?? new PrestataireSubscription();

            $freeSubscription
                ->setPrestataireProfile($prestataireProfile)
                ->setCustomer($sourceSubscription?->getCustomer())
                ->setPlan($freePlan)
                ->setPlanPrice($freePlan->getCurrentPriceForPeriod(SubscriptionBillingPeriodEnum::MONTHLY))
                ->setBillingPeriod(SubscriptionBillingPeriodEnum::MONTHLY)
                ->setStatus(SubscriptionStatusEnum::ACTIVE)
                ->setStartedAt($freeSubscription->getStartedAt() ?? $now)
                ->setCurrentPeriodStart($now)
                ->setCurrentPeriodEnd(null)
                ->setCancelAtPeriodEnd(false)
                ->setCancellationRequestedAt(null)
                ->setCanceledAt(null)
                ->setEndedAt(null)
                ->setUpdatedAt($now);

            if (null === $freeSubscription->getId()) {
                $freeSubscription
                    ->setCreditsGrantedCurrentPeriod(0)
                    ->setCreditsConsumedCurrentPeriod(0);
            }
        }

        $remainingCreditsToTransfer = 0;

        if (
            $sourceSubscription instanceof PrestataireSubscription
            && $sourceSubscription->getId() !== $freeSubscription->getId()
        ) {
            $remainingCreditsToTransfer = $sourceSubscription->getRemainingCredits();

            if ($remainingCreditsToTransfer > 0) {
                $this->subscriptionCreditManager->debitCredits(
                    $sourceSubscription,
                    $remainingCreditsToTransfer,
                    SubscriptionCreditMovementTypeEnum::CORRECTION,
                    'Transfert des crédits restants vers le plan gratuit.',
                    ['reason' => $reason ?? 'fallback_to_free']
                );
            }

            $sourceSubscription
                ->setStatus(SubscriptionStatusEnum::CANCELED)
                ->setCancelAtPeriodEnd(false)
                ->setCancellationRequestedAt($sourceSubscription->getCancellationRequestedAt() ?? $now)
                ->setCanceledAt($sourceSubscription->getCanceledAt() ?? $now)
                ->setEndedAt($sourceSubscription->getEndedAt() ?? $now)
                ->setUpdatedAt($now);

            $this->entityManager->persist($sourceSubscription);
        }

        if ($remainingCreditsToTransfer > 0 || !$freeSubscriptionAlreadyUsable) {
            // A fallback to the free plan must preserve only the current paid subscription balance.
            // Never resurrect one-time welcome credits stored on an older free subscription.
            $freeSubscription
                ->setCreditsGrantedCurrentPeriod(0)
                ->setCreditsConsumedCurrentPeriod(0)
                ->setUpdatedAt($now);
        }

        if ($remainingCreditsToTransfer > 0) {
            $this->subscriptionCreditManager->grantCredits(
                $freeSubscription,
                $remainingCreditsToTransfer,
                SubscriptionCreditMovementTypeEnum::CORRECTION,
                'Conservation des crédits restants après retour au plan gratuit.',
                ['reason' => $reason ?? 'fallback_to_free']
            );
        }

        $this->entityManager->persist($freeSubscription);

        return $freeSubscription;
    }

    public function ensureFreeSubscription(PrestataireProfile $prestataireProfile): PrestataireSubscription
    {
        $now = new \DateTimeImmutable();
        $existingUsable = $this->prestataireSubscriptionRepository->findCurrentUsableForPrestataire($prestataireProfile, $now);

        if ($existingUsable instanceof PrestataireSubscription && self::FREE_PLAN_CODE === $existingUsable->getPlan()?->getCode()) {
            return $existingUsable;
        }

        $freePlan = $this->subscriptionPlanRepository->findOneActiveByCode(self::FREE_PLAN_CODE);
        if (null === $freePlan) {
            throw new \RuntimeException(sprintf('Le plan gratuit "%s" est introuvable.', self::FREE_PLAN_CODE));
        }

        $freeSubscription = $this->prestataireSubscriptionRepository
            ->findLatestForPrestataireAndPlanCode($prestataireProfile, self::FREE_PLAN_CODE)
            ?? new PrestataireSubscription();

        $freeSubscription
            ->setPrestataireProfile($prestataireProfile)
            ->setPlan($freePlan)
            ->setPlanPrice($freePlan->getCurrentPriceForPeriod(SubscriptionBillingPeriodEnum::MONTHLY))
            ->setBillingPeriod(SubscriptionBillingPeriodEnum::MONTHLY)
            ->setStatus(SubscriptionStatusEnum::ACTIVE)
            ->setStartedAt($freeSubscription->getStartedAt() ?? $now)
            ->setCurrentPeriodStart($now)
            ->setCurrentPeriodEnd(null)
            ->setCancelAtPeriodEnd(false)
            ->setCancellationRequestedAt(null)
            ->setCanceledAt(null)
            ->setEndedAt(null)
            ->setUpdatedAt($now);

        if (null === $freeSubscription->getId()) {
            $freeSubscription
                ->setCreditsGrantedCurrentPeriod(0)
                ->setCreditsConsumedCurrentPeriod(0);
        }

        $this->entityManager->persist($freeSubscription);

        return $freeSubscription;
    }
}
