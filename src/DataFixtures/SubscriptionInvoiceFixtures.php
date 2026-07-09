<?php

namespace App\DataFixtures;

use App\Entity\Subscription\PrestataireSubscription;
use App\Entity\Subscription\SubscriptionInvoice;
use App\Enum\SubscriptionInvoiceStatusEnum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SubscriptionInvoiceFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 20; ++$i) {
            /** @var PrestataireSubscription $subscription */
            $subscription = $this->getReference(sprintf('prestataire_subscription_%d', (($i - 1) % UserFixtures::PRESTATAIRE_COUNT) + 1), PrestataireSubscription::class);
            $periodStart = $subscription->getCurrentPeriodStart() ?? $this->randomDateTimeImmutable('-1 month', '-10 days');
            $periodEnd = $subscription->getCurrentPeriodEnd() ?? $periodStart->modify('+1 month');
            $total = $subscription->getBillingPeriod()->value === 'annual'
                ? $subscription->getPlan()?->getAnnualAmount()
                : $subscription->getPlan()?->getMonthlyAmount();

            $invoice = (new SubscriptionInvoice())
                ->setSubscription($subscription)
                ->setStripeInvoiceId(sprintf('in_demo_%04d', $i))
                ->setStripePaymentIntentId(sprintf('pi_demo_%04d', $i))
                ->setInvoiceNumber(sprintf('FA-2026-%04d', $i))
                ->setHostedInvoiceUrl(sprintf('https://billing.stripe.local/invoices/%04d', $i))
                ->setInvoicePdfUrl(sprintf('https://billing.stripe.local/invoices/%04d.pdf', $i))
                ->setCurrency('eur')
                ->setSubtotalAmount($total)
                ->setTaxAmount('0.00')
                ->setTotalAmount($total)
                ->setAmountPaid($total)
                ->setAmountRemaining('0.00')
                ->setStatus(SubscriptionInvoiceStatusEnum::PAID)
                ->setBillingReason('subscription_cycle')
                ->setPeriodStart($periodStart)
                ->setPeriodEnd($periodEnd)
                ->setDueAt($periodStart->modify('+2 days'))
                ->setPaidAt($periodStart->modify('+1 day'))
                ->setStripePayload(['source' => 'fixtures', 'mode' => 'demo'])
                ->setCreatedAt($this->randomDateTimeImmutable('-9 months', '-1 month'))
                ->setUpdatedAt($this->randomDateTimeImmutable('-15 days'));

            $manager->persist($invoice);
            $this->addReference(sprintf('subscription_invoice_%d', $i), $invoice);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [PrestataireSubscriptionFixtures::class];
    }
}
