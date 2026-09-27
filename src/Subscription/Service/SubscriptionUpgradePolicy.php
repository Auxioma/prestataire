<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 AUXIOMA Web Agency.
 *
 * Projet : TrouveMoi
 *
 * Tous droits réservés.
 *
 * Ce fichier fait partie du projet TrouveMoi,
 * développé par AUXIOMA Web Agency.
 *
 * Toute reproduction, modification, distribution ou utilisation,
 * totale ou partielle, sans autorisation écrite préalable,
 * est strictement interdite.
 */

namespace App\Subscription\Service;

use App\Subscription\Entity\PrestataireSubscription;
use App\Subscription\Entity\SubscriptionPlan;
use App\Subscription\Enum\SubscriptionBillingPeriodEnum;

final class SubscriptionUpgradePolicy
{
    /**
     * Ordre métier attendu :
     * free < pro monthly < pro annual < premium monthly < premium annual
     */
    private const PLAN_TIER_RANKS = [
        'free' => 0,
        'pro' => 1,
        'premium' => 2,
    ];

    public function assertCanPurchasePlan(
        ?PrestataireSubscription $currentSubscription,
        SubscriptionPlan $targetPlan,
        SubscriptionBillingPeriodEnum $targetBillingPeriod,
    ): void {
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
    ): bool {
        return $this->getPlanRank($targetPlan, $targetBillingPeriod) > $this->getPlanRank($currentPlan, $currentBillingPeriod);
    }

    public function calculateCappedRemainingCredits(int $currentRemainingCredits, int $includedCredits): int
    {
        $includedCredits = max(0, $includedCredits);
        $currentRemainingCredits = max(0, $currentRemainingCredits);

        if (0 === $includedCredits) {
            return 0;
        }

        return min(max($currentRemainingCredits, $includedCredits), $includedCredits * 2);
    }

    public function calculateCappedTransferableRemainingCredits(int $sourceRemainingCredits, int $includedCredits): int
    {
        $includedCredits = max(0, $includedCredits);
        $sourceRemainingCredits = max(0, $sourceRemainingCredits);

        if (0 === $includedCredits) {
            return 0;
        }

        if ($sourceRemainingCredits > $includedCredits) {
            return $sourceRemainingCredits;
        }

        return min($sourceRemainingCredits + $includedCredits, $includedCredits * 2);
    }

    private function getPlanRank(SubscriptionPlan $plan, SubscriptionBillingPeriodEnum $billingPeriod): int
    {
        $tierRank = self::PLAN_TIER_RANKS[$plan->getCode() ?? ''] ?? max(0, $plan->getSortOrder());

        $periodRank = match ($billingPeriod) {
            SubscriptionBillingPeriodEnum::MONTHLY => 0,
            SubscriptionBillingPeriodEnum::ANNUAL => 1,
        };

        return ($tierRank * 10) + $periodRank;
    }
}
