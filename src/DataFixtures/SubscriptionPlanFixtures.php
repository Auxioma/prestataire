<?php

namespace App\DataFixtures;

use App\Entity\Subscription\SubscriptionPlan;
use App\Entity\Subscription\SubscriptionPlanPrice;
use App\Enum\SubscriptionBillingPeriodEnum;
use App\Enum\SubscriptionPlanStatusEnum;
use Doctrine\Persistence\ObjectManager;

class SubscriptionPlanFixtures extends BaseFixture
{
    public const PLAN_FREE = 'plan_free';
    public const PLAN_PRO = 'plan_pro';
    public const PLAN_PREMIUM = 'plan_premium';

    /**
     * @var list<array{
     *     reference: string,
     *     code: string,
     *     name: string,
     *     description: string,
     *     monthly: string,
     *     annual: string,
     *     monthly_credits: int,
     *     annual_credits: int,
     *     welcome_credits: int,
     *     quote_responses_enabled: bool,
     *     instant_messaging_enabled: bool
     * }>
     */
    private const PLANS = [
        [
            'reference' => self::PLAN_FREE,
            'code' => 'free',
            'name' => 'Gratuit',
            'description' => 'Réponse aux demandes de devis selon les crédits disponibles, sans messagerie instantanée ni accès premium.',
            'monthly' => '0.00',
            'annual' => '0.00',
            'monthly_credits' => 0,
            'annual_credits' => 0,
            'welcome_credits' => 10,
            'quote_responses_enabled' => true,
            'instant_messaging_enabled' => false,
        ],
        [
            'reference' => self::PLAN_PRO,
            'code' => 'pro',
            'name' => 'Pro',
            'description' => 'Réponse aux demandes de devis, messagerie active et accès aux coordonnées visiteurs après réponse.',
            'monthly' => '25.00',
            'annual' => '299.00',
            'monthly_credits' => 30,
            'annual_credits' => 360,
            'welcome_credits' => 0,
            'quote_responses_enabled' => true,
            'instant_messaging_enabled' => true,
        ],
        [
            'reference' => self::PLAN_PREMIUM,
            'code' => 'premium',
            'name' => 'Premium',
            'description' => 'Réponses illimitées, accès complet aux coordonnées visiteurs, visibilité prioritaire et support prioritaire.',
            'monthly' => '59.90',
            'annual' => '599.00',
            'monthly_credits' => 9999,
            'annual_credits' => 99999,
            'welcome_credits' => 0,
            'quote_responses_enabled' => true,
            'instant_messaging_enabled' => true,
        ],
    ];

    public static function getReferenceNames(): array
    {
        return array_column(self::PLANS, 'reference');
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::PLANS as $index => $data) {
            $plan = (new SubscriptionPlan())
                ->setCode($data['code'])
                ->setName($data['name'])
                ->setDescription($data['description'])
                ->setMonthlyAmount($data['monthly'])
                ->setAnnualAmount($data['annual'])
                ->setMonthlyStripePriceId(null)
                ->setAnnualStripePriceId(null)
                ->setStripeProductId(null)
                ->setMonthlyCredits($data['monthly_credits'])
                ->setAnnualCredits($data['annual_credits'])
                ->setWelcomeCredits($data['welcome_credits'])
                ->setQuoteResponsesEnabled($data['quote_responses_enabled'])
                ->setInstantMessagingEnabled($data['instant_messaging_enabled'])
                ->setSortOrder($index + 1)
                ->setStatus(SubscriptionPlanStatusEnum::ACTIVE)
                ->setCreatedAt($this->randomDateTimeImmutable('-12 months', '-5 months'))
                ->setUpdatedAt($this->randomDateTimeImmutable('-30 days'));

            $monthlyPrice = (new SubscriptionPlanPrice())
                ->setPlan($plan)
                ->setBillingPeriod(SubscriptionBillingPeriodEnum::MONTHLY)
                ->setLabel('Tarif standard')
                ->setAmount($data['monthly'])
                ->setStripePriceId(null)
                ->setIsActive(true)
                ->setIsPromotional(false)
                ->setCreatedAt($plan->getCreatedAt())
                ->setUpdatedAt($plan->getUpdatedAt());

            $annualPrice = (new SubscriptionPlanPrice())
                ->setPlan($plan)
                ->setBillingPeriod(SubscriptionBillingPeriodEnum::ANNUAL)
                ->setLabel('Tarif standard')
                ->setAmount($data['annual'])
                ->setStripePriceId(null)
                ->setIsActive(true)
                ->setIsPromotional(false)
                ->setCreatedAt($plan->getCreatedAt())
                ->setUpdatedAt($plan->getUpdatedAt());

            $plan
                ->addPrice($monthlyPrice)
                ->addPrice($annualPrice);

            $manager->persist($plan);
            $manager->persist($monthlyPrice);
            $manager->persist($annualPrice);
            $this->addReference($data['reference'], $plan);
        }

        $manager->flush();
    }
}
