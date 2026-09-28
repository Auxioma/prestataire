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

use App\Prestataire\Entity\PrestataireProfile;
use App\Subscription\Entity\PrestataireSubscription;
use App\Subscription\Enum\SubscriptionBillingPeriodEnum;
use App\Subscription\Enum\SubscriptionCreditMovementTypeEnum;
use App\Subscription\Enum\SubscriptionStatusEnum;
use App\Subscription\Repository\PrestataireSubscriptionRepository;
use App\Subscription\Repository\SubscriptionPlanRepository;
use Doctrine\ORM\EntityManagerInterface;

final class PrestataireSubscriptionOnboardingManager
{
    private const FREE_PLAN_CODE = 'free';

    public function __construct(
        private readonly SubscriptionPlanRepository $subscriptionPlanRepository,
        private readonly PrestataireSubscriptionRepository $prestataireSubscriptionRepository,
        private readonly SubscriptionCreditManager $subscriptionCreditManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function assignFreePlanToNewPrestataire(PrestataireProfile $prestataireProfile): PrestataireSubscription
    {
        if (null !== $prestataireProfile->getId()) {
            $existingSubscription = $this->prestataireSubscriptionRepository->findLatestForPrestataire($prestataireProfile);
            if ($existingSubscription instanceof PrestataireSubscription) {
                return $existingSubscription;
            }
        }

        $freePlan = $this->subscriptionPlanRepository->findOneActiveByCode(self::FREE_PLAN_CODE);
        if (null === $freePlan) {
            throw new \RuntimeException(\sprintf('Le plan gratuit "%s" est introuvable. Exécutez la commande "app:subscription:install-default-plans" ou chargez les fixtures de plans avant de créer un prestataire.', self::FREE_PLAN_CODE));
        }

        $now = new \DateTimeImmutable();
        $subscription = new PrestataireSubscription()
            ->setPrestataireProfile($prestataireProfile)
            ->setPlan($freePlan)
            ->setPlanPrice($freePlan->getCurrentPriceForPeriod(SubscriptionBillingPeriodEnum::MONTHLY))
            ->setBillingPeriod(SubscriptionBillingPeriodEnum::MONTHLY)
            ->setStatus(SubscriptionStatusEnum::ACTIVE)
            ->setStartedAt($now)
            ->setCurrentPeriodStart($now)
            ->setCurrentPeriodEnd(null)
            ->setCancelAtPeriodEnd(false)
            ->setCancellationRequestedAt(null)
            ->setCanceledAt(null)
            ->setEndedAt(null)
            ->setUpdatedAt($now)
            ->syncCreditsWithPlan();

        $this->entityManager->persist($subscription);

        if ($freePlan->getWelcomeCredits() > 0) {
            $this->subscriptionCreditManager->grantCredits(
                $subscription,
                $freePlan->getWelcomeCredits(),
                SubscriptionCreditMovementTypeEnum::WELCOME_GRANT,
                'Bonus de bienvenue attribué automatiquement à l’inscription du prestataire.',
                ['source' => 'prestataire_registration']
            );
        }

        return $subscription;
    }
}
