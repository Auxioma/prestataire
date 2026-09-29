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

namespace App\Subscription\DataFixtures;

use App\Account\DataFixtures\UserFixtures;
use App\Core\DataFixtures\BaseFixture;
use App\Prestataire\Entity\PrestataireProfile;
use App\Subscription\Entity\PrestataireSubscription;
use App\Subscription\Entity\SubscriptionCustomer;
use App\Subscription\Entity\SubscriptionPlan;
use App\Subscription\Enum\SubscriptionBillingPeriodEnum;
use App\Subscription\Enum\SubscriptionStatusEnum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PrestataireSubscriptionFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $planReferences = SubscriptionPlanFixtures::getReferenceNames();
        $planCount = \count($planReferences);

        for ($i = 1; $i <= UserFixtures::PRESTATAIRE_COUNT; ++$i) {
            /** @var PrestataireProfile $prestataire */
            $prestataire = $this->getReference(\sprintf('prestataire_profile_%d', $i), PrestataireProfile::class);
            /** @var SubscriptionCustomer $customer */
            $customer = $this->getReference(\sprintf('subscription_customer_%d', $i), SubscriptionCustomer::class);
            /** @var SubscriptionPlan $plan */
            $plan = $this->getReference($planReferences[($i - 1) % $planCount], SubscriptionPlan::class);

            $billingPeriod = 0 === $i % 3 ? SubscriptionBillingPeriodEnum::ANNUAL : SubscriptionBillingPeriodEnum::MONTHLY;
            $periodStart = $this->randomDateTimeImmutable('-30 days', '-5 days');
            $periodEnd = SubscriptionBillingPeriodEnum::ANNUAL === $billingPeriod ? $periodStart->modify('+1 year') : $periodStart->modify('+1 month');
            $granted = SubscriptionBillingPeriodEnum::ANNUAL === $billingPeriod ? $plan->getAnnualCredits() : $plan->getMonthlyCredits();
            $consumed = $granted > 0
                ? min($granted - 1, $this->faker->numberBetween(0, max(1, (int) floor($granted / 2))))
                : 0;

            $subscription = (new PrestataireSubscription())
                ->setPrestataireProfile($prestataire)
                ->setCustomer($customer)
                ->setPlan($plan)
                ->setPlanPrice($plan->getCurrentPriceForPeriod($billingPeriod))
                ->setBillingPeriod($billingPeriod)
                ->setStatus(SubscriptionStatusEnum::ACTIVE)
                ->setStripeSubscriptionId(\sprintf('sub_demo_%04d', $i))
                ->setStripePriceId(SubscriptionBillingPeriodEnum::ANNUAL === $billingPeriod ? $plan->getAnnualStripePriceId() : $plan->getMonthlyStripePriceId())
                ->setStripeSubscriptionItemId(\sprintf('si_demo_%04d', $i))
                ->setStartedAt($periodStart)
                ->setCurrentPeriodStart($periodStart)
                ->setCurrentPeriodEnd($periodEnd)
                ->setCancelAtPeriodEnd(0 === $i % 5)
                ->setCancellationRequestedAt(0 === $i % 5 ? $this->randomDateTimeImmutable('-10 days', 'now') : null)
                ->setCreditsGrantedCurrentPeriod($granted)
                ->setCreditsConsumedCurrentPeriod($consumed)
                ->setCreatedAt($this->randomDateTimeImmutable('-10 months', '-2 months'))
                ->setUpdatedAt($this->randomDateTimeImmutable('-7 days'));

            $manager->persist($subscription);
            $this->addReference(\sprintf('prestataire_subscription_%d', $i), $subscription);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [SubscriptionCustomerFixtures::class, SubscriptionPlanFixtures::class];
    }
}
