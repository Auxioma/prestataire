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

namespace App\Tests\Service;

use App\Account\Entity\User;
use App\Prestataire\Entity\PrestataireProfile;
use App\Subscription\Entity\PrestataireSubscription;
use App\Subscription\Entity\SubscriptionCustomer;
use App\Subscription\Entity\SubscriptionInvoice;
use App\Subscription\Entity\SubscriptionPlan;
use App\Subscription\Service\SubscriptionLifecycleMailer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SubscriptionLifecycleMailerTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function paidInvoiceReasons(): iterable
    {
        yield 'purchase' => ['subscription_create', 'Confirmation de votre abonnement TrouveMoi', 'purchase'];
        yield 'change' => ['subscription_update', 'Confirmation de la modification de votre forfait TrouveMoi', 'change'];
        yield 'renewal' => ['subscription_cycle', 'Confirmation du renouvellement de votre forfait TrouveMoi', 'renewal'];
    }

    #[DataProvider('paidInvoiceReasons')]
    public function testSendsExpectedPaidInvoiceEmail(string $billingReason, string $subject, string $eventKey): void
    {
        $sentEmail = null;
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->willReturnCallback(static function (RawMessage $message) use (&$sentEmail): void {
                $sentEmail = $message;
            });

        $lifecycleMailer = $this->createLifecycleMailer($mailer);
        $invoice = $this->createInvoice($billingReason);

        $sent = $lifecycleMailer->sendPaidInvoiceConfirmation($invoice);

        self::assertTrue($sent);
        self::assertInstanceOf(TemplatedEmail::class, $sentEmail);
        self::assertSame($subject, $sentEmail->getSubject());
        self::assertSame('subscription/emails/paid_invoice.html.twig', $sentEmail->getHtmlTemplate());
        self::assertSame('subscription/emails/paid_invoice.txt.twig', $sentEmail->getTextTemplate());
        self::assertSame($eventKey, $sentEmail->getContext()['eventKey']);
        self::assertSame('Pro', $sentEmail->getContext()['planName']);
        self::assertSame('49.90', $sentEmail->getContext()['amountPaid']);
    }

    public function testIgnoresPaidInvoiceUnrelatedToSubscriptionLifecycle(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $sent = $this->createLifecycleMailer($mailer)
            ->sendPaidInvoiceConfirmation($this->createInvoice('manual'));

        self::assertFalse($sent);
    }

    public function testUsesBillingEmailWhenAccountIsNotLoaded(): void
    {
        $sentEmail = null;
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->willReturnCallback(static function (RawMessage $message) use (&$sentEmail): void {
                $sentEmail = $message;
            });

        $profile = (new PrestataireProfile())->setCompanyName('Entreprise Test');
        $customer = (new SubscriptionCustomer())
            ->setPrestataireProfile($profile)
            ->setStripeCustomerId('cus_test')
            ->setBillingEmail('facturation@example.test');
        $subscription = (new PrestataireSubscription())
            ->setPrestataireProfile($profile)
            ->setCustomer($customer)
            ->setPlan((new SubscriptionPlan())->setCode('pro')->setName('Pro'));
        $invoice = (new SubscriptionInvoice())
            ->setSubscription($subscription)
            ->setBillingReason('subscription_update')
            ->setCurrency('eur');

        $sent = $this->createLifecycleMailer($mailer)->sendPaidInvoiceConfirmation($invoice);

        self::assertTrue($sent);
        self::assertInstanceOf(TemplatedEmail::class, $sentEmail);
        self::assertSame('facturation@example.test', $sentEmail->getTo()[0]->getAddress());
    }

    public function testSendsCancellationConfirmation(): void
    {
        $sentEmail = null;
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->willReturnCallback(static function (RawMessage $message) use (&$sentEmail): void {
                $sentEmail = $message;
            });

        $subscription = $this->createSubscription()
            ->setCancellationRequestedAt(new \DateTimeImmutable('2026-09-29 10:00:00'))
            ->setCurrentPeriodEnd(new \DateTimeImmutable('2026-10-29 10:00:00'));

        $this->createLifecycleMailer($mailer)->sendCancellationConfirmation($subscription);

        self::assertInstanceOf(TemplatedEmail::class, $sentEmail);
        self::assertSame('Confirmation de la résiliation de votre forfait TrouveMoi', $sentEmail->getSubject());
        self::assertSame('subscription/emails/cancellation.html.twig', $sentEmail->getHtmlTemplate());
        self::assertSame('subscription/emails/cancellation.txt.twig', $sentEmail->getTextTemplate());
        self::assertSame($subscription->getCurrentPeriodEnd(), $sentEmail->getContext()['effectiveAt']);
    }

    private function createLifecycleMailer(MailerInterface $mailer): SubscriptionLifecycleMailer
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturnCallback(static fn (string $route): string => 'https://trouvemoi.test/'.$route);

        return new SubscriptionLifecycleMailer(
            $mailer,
            $urlGenerator,
        );
    }

    private function createInvoice(string $billingReason): SubscriptionInvoice
    {
        return (new SubscriptionInvoice())
            ->setSubscription($this->createSubscription())
            ->setStripeInvoiceId('in_test')
            ->setInvoiceNumber('TM-2026-001')
            ->setInvoicePdfUrl('https://stripe.test/invoice.pdf')
            ->setAmountPaid('49.90')
            ->setTotalAmount('49.90')
            ->setCurrency('eur')
            ->setBillingReason($billingReason)
            ->setPeriodStart(new \DateTimeImmutable('2026-09-29'))
            ->setPeriodEnd(new \DateTimeImmutable('2026-10-29'));
    }

    private function createSubscription(): PrestataireSubscription
    {
        $user = (new User())
            ->setEmail('prestataire@example.test')
            ->setFirstName('Camille');

        $profile = (new PrestataireProfile())
            ->setAccount($user)
            ->setCompanyName('Entreprise Test');

        $plan = (new SubscriptionPlan())
            ->setCode('pro')
            ->setName('Pro');

        return (new PrestataireSubscription())
            ->setPrestataireProfile($profile)
            ->setPlan($plan);
    }
}
