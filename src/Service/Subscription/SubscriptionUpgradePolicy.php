<?php

namespace App\Service\Subscription;

use App\Entity\Subscription\PrestataireSubscription;
use App\Entity\Subscription\SubscriptionPlan;
use App\Enum\SubscriptionBillingPeriodEnum;

final class SubscriptionUpgradePolicy
{
    public function assertCanPurchasePlan(
        ?PrestataireSubscription $currentSubscription,
        SubscriptionPlan $targetPlan,
        SubscriptionBillingPeriodEnum $targetBillingPeriod,
    ): void
    {
        $currentPlan = $currentSubscription?->getPlan();

        if (!$currentPlan instanceof SubscriptionPlan) {
            return;
        }

        if (!$this->isStrictlyHigherPlan(
            $targetPlan,
            $targetBillingPeriod,
            $currentPlan,
            $currentSubscription->getBillingPeriod(),
        )) {
            throw new \DomainException('Vous ne pouvez recharger vos crédits qu’en passant à une formule strictement supérieure à votre formule actuelle.');
        }
    }

    public function isStrictlyHigherPlan(
        SubscriptionPlan $targetPlan,
        SubscriptionBillingPeriodEnum $targetBillingPeriod,
        SubscriptionPlan $currentPlan,
        SubscriptionBillingPeriodEnum $currentBillingPeriod,
    ): bool
    {
        return $this->getPlanRank($targetPlan, $targetBillingPeriod) > $this->getPlanRank($currentPlan, $currentBillingPeriod);
    }

    public function calculateCappedRemainingCredits(int $currentRemainingCredits, int $includedCredits): int
    {
        $includedCredits = max(0, $includedCredits);
        $currentRemainingCredits = max(0, $currentRemainingCredits);

        if (0 === $includedCredits) {
            return 0;
        }

        return min($currentRemainingCredits + $includedCredits, $includedCredits * 2);
    }

    private function getPlanRank(SubscriptionPlan $plan, SubscriptionBillingPeriodEnum $billingPeriod): int
    {
        $periodRank = match ($billingPeriod) {
            SubscriptionBillingPeriodEnum::MONTHLY => 0,
            SubscriptionBillingPeriodEnum::ANNUAL => 1,
        };

        return (max(0, $plan->getSortOrder()) * 10) + $periodRank;
    }
}
