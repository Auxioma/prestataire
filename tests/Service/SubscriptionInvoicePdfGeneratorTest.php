<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\PrestataireProfile;
use App\Entity\Subscription\PrestataireSubscription;
use App\Entity\Subscription\SubscriptionInvoice;
use App\Entity\Subscription\SubscriptionPlan;
use App\Enum\SubscriptionBillingPeriodEnum;
use App\Enum\SubscriptionInvoiceStatusEnum;
use App\Service\Subscription\SubscriptionFacturXXmlBuilder;
use App\Service\Subscription\SubscriptionInvoicePdfGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class SubscriptionInvoicePdfGeneratorTest extends TestCase
{
    public function testGeneratedPdfEmbedsFacturXXml(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel
            ->method('getProjectDir')
            ->willReturn(sys_get_temp_dir());

        $pdfGenerator = new SubscriptionInvoicePdfGenerator(
            new Environment(new FilesystemLoader(\dirname(__DIR__, 2) . '/templates')),
            new SubscriptionFacturXXmlBuilder(),
            $kernel,
        );

        $invoice = $this->createInvoiceFixture();
        $pdfOutput = $pdfGenerator->generatePdfOutput($invoice);

        self::assertStringContainsString('/EmbeddedFiles', $pdfOutput);
        self::assertStringContainsString('factur-x.xml', $pdfOutput);
        self::assertStringContainsString('/AFRelationship /Alternative', $pdfOutput);
        self::assertStringContainsString('/Metadata', $pdfOutput);
        self::assertStringContainsString('/OutputIntents', $pdfOutput);
    }

    private function createInvoiceFixture(): SubscriptionInvoice
    {
        $prestataire = (new PrestataireProfile())
            ->setCompanyName('Acme Services')
            ->setLegalName('Acme Services SARL')
            ->setAddress('10 rue de la Paix')
            ->setPostalCode('75002')
            ->setCity('Paris')
            ->setCountry('France')
            ->setSiret('12345678900012')
            ->setVatNumber('FR00123456789');

        $plan = (new SubscriptionPlan())
            ->setCode('pro')
            ->setName('Abonnement Pro')
            ->setMonthlyAmount('49.00');

        $subscription = (new PrestataireSubscription())
            ->setPrestataireProfile($prestataire)
            ->setPlan($plan)
            ->setBillingPeriod(SubscriptionBillingPeriodEnum::MONTHLY)
            ->setCurrentPeriodStart(new \DateTimeImmutable('2026-07-01 00:00:00'))
            ->setCurrentPeriodEnd(new \DateTimeImmutable('2026-07-31 23:59:59'));

        return (new SubscriptionInvoice())
            ->setSubscription($subscription)
            ->setStripeInvoiceId('in_sub_test_001')
            ->setStripePaymentIntentId('pi_sub_test_001')
            ->setInvoiceNumber('FA-ABO-TEST-001')
            ->setCurrency('eur')
            ->setSubtotalAmount('49.00')
            ->setTaxAmount('0.00')
            ->setTotalAmount('49.00')
            ->setAmountPaid('49.00')
            ->setAmountRemaining('0.00')
            ->setStatus(SubscriptionInvoiceStatusEnum::PAID)
            ->setBillingReason('subscription_cycle')
            ->setPeriodStart(new \DateTimeImmutable('2026-07-01 00:00:00'))
            ->setPeriodEnd(new \DateTimeImmutable('2026-07-31 23:59:59'))
            ->setDueAt(new \DateTimeImmutable('2026-07-03 00:00:00'))
            ->setPaidAt(new \DateTimeImmutable('2026-07-02 12:00:00'))
            ->setCreatedAt(new \DateTimeImmutable('2026-07-01 09:00:00'));
    }
}
