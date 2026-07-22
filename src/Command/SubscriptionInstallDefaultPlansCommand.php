<?php

namespace App\Command;

use App\Entity\Subscription\SubscriptionPlan;
use App\Entity\Subscription\SubscriptionPlanPrice;
use App\Enum\SubscriptionBillingPeriodEnum;
use App\Enum\SubscriptionPlanStatusEnum;
use App\Repository\Subscription\SubscriptionPlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:subscription:install-default-plans')]
final class SubscriptionInstallDefaultPlansCommand extends Command
{
    /**
     * @var list<array{
     *     code: string,
     *     name: string,
     *     description: string,
     *     monthly_amount: string,
     *     annual_amount: string,
     *     monthly_credits: int,
     *     annual_credits: int,
     *     welcome_credits: int,
     *     quote_responses_enabled: bool,
     *     instant_messaging_enabled: bool,
     *     sort_order: int
     * }>
     */
    private const DEFAULT_PLANS = [
        [
            'code' => 'free',
            'name' => 'Gratuit',
            'description' => 'Réponse aux demandes de devis selon les crédits disponibles, sans messagerie instantanée ni accès premium.',
            'monthly_amount' => '0.00',
            'annual_amount' => '0.00',
            'monthly_credits' => 0,
            'annual_credits' => 0,
            'welcome_credits' => 10,
            'quote_responses_enabled' => true,
            'instant_messaging_enabled' => false,
            'sort_order' => 1,
        ],
        [
            'code' => 'pro',
            'name' => 'Pro',
            'description' => 'Réponse aux demandes de devis, messagerie active et accès aux coordonnées visiteurs après réponse.',
            'monthly_amount' => '25.00',
            'annual_amount' => '299.00',
            'monthly_credits' => 30,
            'annual_credits' => 360,
            'welcome_credits' => 0,
            'quote_responses_enabled' => true,
            'instant_messaging_enabled' => true,
            'sort_order' => 2,
        ],
        [
            'code' => 'premium',
            'name' => 'Premium',
            'description' => 'Réponses illimitées, accès complet aux coordonnées visiteurs, visibilité prioritaire et support prioritaire.',
            'monthly_amount' => '59.90',
            'annual_amount' => '599.00',
            'monthly_credits' => 9999,
            'annual_credits' => 99999,
            'welcome_credits' => 0,
            'quote_responses_enabled' => true,
            'instant_messaging_enabled' => true,
            'sort_order' => 3,
        ],
    ];

    public function __construct(
        private readonly SubscriptionPlanRepository $subscriptionPlanRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();
        $defaultCodes = array_column(self::DEFAULT_PLANS, 'code');

        foreach ($this->subscriptionPlanRepository->findAll() as $existingPlan) {
            if (!in_array($existingPlan->getCode(), $defaultCodes, true)) {
                $existingPlan
                    ->setStatus(SubscriptionPlanStatusEnum::ARCHIVED)
                    ->setUpdatedAt($now);

                $this->entityManager->persist($existingPlan);
            }
        }

        foreach (self::DEFAULT_PLANS as $data) {
            $plan = $this->subscriptionPlanRepository->findOneBy(['code' => $data['code']]) ?? new SubscriptionPlan();

            $plan
                ->setCode($data['code'])
                ->setName($data['name'])
                ->setDescription($data['description'])
                ->setMonthlyAmount($data['monthly_amount'])
                ->setAnnualAmount($data['annual_amount'])
                ->setMonthlyCredits($data['monthly_credits'])
                ->setAnnualCredits($data['annual_credits'])
                ->setWelcomeCredits($data['welcome_credits'])
                ->setQuoteResponsesEnabled($data['quote_responses_enabled'])
                ->setInstantMessagingEnabled($data['instant_messaging_enabled'])
                ->setSortOrder($data['sort_order'])
                ->setStatus(SubscriptionPlanStatusEnum::ACTIVE)
                ->setUpdatedAt($now);

            $this->upsertStandardPrice($plan, SubscriptionBillingPeriodEnum::MONTHLY, $data['monthly_amount'], $now);
            $this->upsertStandardPrice($plan, SubscriptionBillingPeriodEnum::ANNUAL, $data['annual_amount'], $now);

            $this->entityManager->persist($plan);
            $io->text(sprintf('Plan %s prêt.', $data['code']));
        }

        $this->entityManager->flush();
        $io->success('Le catalogue réel des plans a été mis à jour: free, pro, premium.');

        return Command::SUCCESS;
    }

    private function upsertStandardPrice(
        SubscriptionPlan $plan,
        SubscriptionBillingPeriodEnum $billingPeriod,
        string $amount,
        \DateTimeImmutable $now,
    ): void {
        $selected = null;

        foreach ($plan->getPrices() as $existingPrice) {
            if ($existingPrice->getBillingPeriod() !== $billingPeriod || $existingPrice->isPromotional()) {
                continue;
            }

            if (null === $selected) {
                $selected = $existingPrice;
                continue;
            }

            $existingPrice
                ->setIsActive(false)
                ->setUpdatedAt($now);
        }

        if (!$selected instanceof SubscriptionPlanPrice) {
            $selected = (new SubscriptionPlanPrice())
                ->setPlan($plan)
                ->setBillingPeriod($billingPeriod)
                ->setCreatedAt($now);

            $plan->addPrice($selected);
        }

        $selected
            ->setLabel('Tarif standard')
            ->setAmount($amount)
            ->setIsActive(true)
            ->setIsPromotional(false)
            ->setValidFrom(null)
            ->setValidUntil(null)
            ->setUpdatedAt($now);

        if ((float) $amount <= 0) {
            $selected->setStripePriceId(null);
        }

        $this->entityManager->persist($selected);
    }
}
