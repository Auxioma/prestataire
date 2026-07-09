<?php

namespace App\DataFixtures;

use App\Entity\Subscription\SubscriptionPlan;
use App\Enum\SubscriptionPlanStatusEnum;
use Doctrine\Persistence\ObjectManager;

class SubscriptionPlanFixtures extends BaseFixture
{
    /**
     * @var list<array{code: string, name: string, monthly: string, annual: string, monthly_credits: int, annual_credits: int}>
     */
    private const PLANS = [
        ['code' => 'essentiel', 'name' => 'Essentiel', 'monthly' => '19.90', 'annual' => '199.00', 'monthly_credits' => 8, 'annual_credits' => 120],
        ['code' => 'starter', 'name' => 'Starter', 'monthly' => '29.90', 'annual' => '299.00', 'monthly_credits' => 12, 'annual_credits' => 180],
        ['code' => 'croissance', 'name' => 'Croissance', 'monthly' => '39.90', 'annual' => '399.00', 'monthly_credits' => 18, 'annual_credits' => 260],
        ['code' => 'pro', 'name' => 'Pro', 'monthly' => '49.90', 'annual' => '499.00', 'monthly_credits' => 25, 'annual_credits' => 360],
        ['code' => 'expert', 'name' => 'Expert', 'monthly' => '69.90', 'annual' => '699.00', 'monthly_credits' => 35, 'annual_credits' => 500],
        ['code' => 'premium', 'name' => 'Premium', 'monthly' => '89.90', 'annual' => '899.00', 'monthly_credits' => 50, 'annual_credits' => 720],
        ['code' => 'agence', 'name' => 'Agence', 'monthly' => '119.90', 'annual' => '1199.00', 'monthly_credits' => 70, 'annual_credits' => 980],
        ['code' => 'reseau', 'name' => 'Réseau', 'monthly' => '149.90', 'annual' => '1499.00', 'monthly_credits' => 90, 'annual_credits' => 1260],
        ['code' => 'elite', 'name' => 'Élite', 'monthly' => '199.90', 'annual' => '1999.00', 'monthly_credits' => 120, 'annual_credits' => 1680],
        ['code' => 'sur-mesure', 'name' => 'Sur mesure', 'monthly' => '299.90', 'annual' => '2999.00', 'monthly_credits' => 200, 'annual_credits' => 2800],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::PLANS as $index => $data) {
            $plan = (new SubscriptionPlan())
                ->setCode($data['code'])
                ->setName($data['name'])
                ->setDescription(sprintf('Abonnement %s avec reconduction tacite, accès aux réponses aux devis et à la messagerie instantanée.', $data['name']))
                ->setMonthlyAmount($data['monthly'])
                ->setAnnualAmount($data['annual'])
                ->setMonthlyStripePriceId(sprintf('price_monthly_%02d_demo', $index + 1))
                ->setAnnualStripePriceId(sprintf('price_annual_%02d_demo', $index + 1))
                ->setMonthlyCredits($data['monthly_credits'])
                ->setAnnualCredits($data['annual_credits'])
                ->setQuoteResponsesEnabled(true)
                ->setInstantMessagingEnabled(true)
                ->setSortOrder($index + 1)
                ->setStatus(SubscriptionPlanStatusEnum::ACTIVE)
                ->setCreatedAt($this->randomDateTimeImmutable('-12 months', '-5 months'))
                ->setUpdatedAt($this->randomDateTimeImmutable('-30 days'));

            $manager->persist($plan);
            $this->addReference(sprintf('subscription_plan_%d', $index + 1), $plan);
        }

        $manager->flush();
    }
}
