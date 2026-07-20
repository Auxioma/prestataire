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
            throw new \RuntimeException(sprintf(
                'Le plan gratuit "%s" est introuvable. Exécutez la commande "app:subscription:install-default-plans" ou chargez les fixtures de plans avant de créer un prestataire.',
                self::FREE_PLAN_CODE
            ));
        }

        $now = new \DateTimeImmutable();
        $subscription = (new PrestataireSubscription())
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
