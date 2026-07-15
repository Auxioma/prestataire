<?php

namespace App\DataFixtures;

use App\Entity\PrestataireProfile;
use App\Entity\Subscription\PrestataireSubscription;
use App\Entity\Subscription\SubscriptionCustomer;
use App\Entity\Subscription\SubscriptionPlan;
use App\Enum\SubscriptionBillingPeriodEnum;
use App\Enum\SubscriptionStatusEnum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PrestataireSubscriptionFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $planReferences = SubscriptionPlanFixtures::getReferenceNames();
        $planCount = count($planReferences);

        for ($i = 1; $i <= UserFixtures::PRESTATAIRE_COUNT; ++$i) {
            /** @var PrestataireProfile $prestataire */
            $prestataire = $this->getReference(sprintf('prestataire_profile_%d', $i), PrestataireProfile::class);
            /** @var SubscriptionCustomer $customer */
            $customer = $this->getReference(sprintf('subscription_customer_%d', $i), SubscriptionCustomer::class);
            /** @var SubscriptionPlan $plan */
            $plan = $this->getReference($planReferences[($i - 1) % $planCount], SubscriptionPlan::class);

            $billingPeriod = $i % 3 === 0 ? SubscriptionBillingPeriodEnum::ANNUAL : SubscriptionBillingPeriodEnum::MONTHLY;
            $periodStart = $this->randomDateTimeImmutable('-30 days', '-5 days');
            $periodEnd = $billingPeriod === SubscriptionBillingPeriodEnum::ANNUAL ? $periodStart->modify('+1 year') : $periodStart->modify('+1 month');
            $granted = $billingPeriod === SubscriptionBillingPeriodEnum::ANNUAL ? $plan->getAnnualCredits() : $plan->getMonthlyCredits();
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
                ->setStripeSubscriptionId(sprintf('sub_demo_%04d', $i))
                ->setStripePriceId($billingPeriod === SubscriptionBillingPeriodEnum::ANNUAL ? $plan->getAnnualStripePriceId() : $plan->getMonthlyStripePriceId())
                ->setStripeSubscriptionItemId(sprintf('si_demo_%04d', $i))
                ->setStartedAt($periodStart)
                ->setCurrentPeriodStart($periodStart)
                ->setCurrentPeriodEnd($periodEnd)
                ->setCancelAtPeriodEnd($i % 5 === 0)
                ->setCancellationRequestedAt($i % 5 === 0 ? $this->randomDateTimeImmutable('-10 days', 'now') : null)
                ->setCreditsGrantedCurrentPeriod($granted)
                ->setCreditsConsumedCurrentPeriod($consumed)
                ->setCreatedAt($this->randomDateTimeImmutable('-10 months', '-2 months'))
                ->setUpdatedAt($this->randomDateTimeImmutable('-7 days'));

            $manager->persist($subscription);
            $this->addReference(sprintf('prestataire_subscription_%d', $i), $subscription);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [SubscriptionCustomerFixtures::class, SubscriptionPlanFixtures::class];
    }
}
