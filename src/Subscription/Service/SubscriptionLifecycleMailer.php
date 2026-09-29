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

use App\Account\Entity\User;
use App\Subscription\Entity\PrestataireSubscription;
use App\Subscription\Entity\SubscriptionInvoice;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SubscriptionLifecycleMailer
{
    /**
     * @var array<string, array{subject: string, headline: string, message: string, eventKey: string}>
     */
    private const PAID_INVOICE_MESSAGES = [
        'subscription_create' => [
            'subject' => 'Confirmation de votre abonnement TrouveMoi',
            'headline' => 'Votre forfait est actif',
            'message' => 'Votre achat a bien été enregistré et votre paiement confirmé.',
            'eventKey' => 'purchase',
        ],
        'subscription_update' => [
            'subject' => 'Confirmation de la modification de votre forfait TrouveMoi',
            'headline' => 'Votre forfait a été modifié',
            'message' => 'La modification de votre forfait a bien été prise en compte et le paiement associé est confirmé.',
            'eventKey' => 'change',
        ],
        'subscription_cycle' => [
            'subject' => 'Confirmation du renouvellement de votre forfait TrouveMoi',
            'headline' => 'Votre forfait a été renouvelé',
            'message' => 'Le renouvellement automatique de votre forfait et son paiement ont bien été confirmés.',
            'eventKey' => 'renewal',
        ],
    ];

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function sendPaidInvoiceConfirmation(SubscriptionInvoice $invoice): bool
    {
        $billingReason = mb_trim((string) $invoice->getBillingReason());
        $message = self::PAID_INVOICE_MESSAGES[$billingReason] ?? null;
        $subscription = $invoice->getSubscription();

        if (!\is_array($message) || !$subscription instanceof PrestataireSubscription) {
            return false;
        }

        $user = $subscription->getPrestataireProfile()?->getAccount();
        $stripePayload = $invoice->getStripePayload() ?? [];
        $recipient = mb_trim((string) (
            $user?->getEmail()
            ?? $subscription->getCustomer()?->getBillingEmail()
            ?? $stripePayload['customer_email']
            ?? ''
        ));
        if ('' === $recipient) {
            return false;
        }

        $accountUrl = $this->urlGenerator->generate(
            'app_subscription_index',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@trouvemoi.com', 'TrouveMoi'))
            ->to($recipient)
            ->subject($message['subject'])
            ->htmlTemplate('subscription/emails/paid_invoice.html.twig')
            ->textTemplate('subscription/emails/paid_invoice.txt.twig')
            ->context([
                'accountUrl' => $accountUrl,
                'amountPaid' => $invoice->getAmountPaid() ?? $invoice->getTotalAmount(),
                'companyName' => $subscription->getPrestataireProfile()?->getCompanyName(),
                'currency' => $invoice->getCurrencyCode(),
                'eventKey' => $message['eventKey'],
                'firstName' => $user?->getFirstName(),
                'headline' => $message['headline'],
                'invoiceNumber' => $invoice->getInvoiceNumber(),
                'message' => $message['message'],
                'periodEnd' => $invoice->getPeriodEnd() ?? $subscription->getCurrentPeriodEnd(),
                'periodLabel' => $subscription->getBillingPeriod()->getLabel(),
                'periodStart' => $invoice->getPeriodStart() ?? $subscription->getCurrentPeriodStart(),
                'planName' => $subscription->getPlan()?->getName() ?? 'Forfait TrouveMoi',
            ]);

        $this->mailer->send($email);

        return true;
    }

    public function sendCancellationConfirmation(PrestataireSubscription $subscription): void
    {
        $user = $subscription->getPrestataireProfile()?->getAccount();
        if (!$user instanceof User) {
            return;
        }

        $recipient = mb_trim((string) $user->getEmail());
        if ('' === $recipient) {
            return;
        }

        $accountUrl = $this->urlGenerator->generate(
            'app_subscription_index',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@trouvemoi.com', 'TrouveMoi'))
            ->to($recipient)
            ->subject('Confirmation de la résiliation de votre forfait TrouveMoi')
            ->htmlTemplate('subscription/emails/cancellation.html.twig')
            ->textTemplate('subscription/emails/cancellation.txt.twig')
            ->context([
                'accountUrl' => $accountUrl,
                'companyName' => $subscription->getPrestataireProfile()?->getCompanyName(),
                'effectiveAt' => $subscription->getCurrentPeriodEnd(),
                'firstName' => $user->getFirstName(),
                'periodLabel' => $subscription->getBillingPeriod()->getLabel(),
                'planName' => $subscription->getPlan()?->getName() ?? 'Forfait TrouveMoi',
                'requestedAt' => $subscription->getCancellationRequestedAt() ?? new \DateTimeImmutable(),
            ]);

        $this->mailer->send($email);
    }
}
