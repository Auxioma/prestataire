<?php

namespace App\Service\Subscription;

use App\Entity\PrestataireProfile;
use App\Entity\Subscription\SubscriptionCustomer;
use App\Repository\Subscription\SubscriptionCustomerRepository;

final class StripeCheckoutSessionSynchronizer
{
    public function __construct(
        private readonly StripeApiClient $stripeApiClient,
        private readonly StripeWebhookManager $stripeWebhookManager,
        private readonly SubscriptionCustomerRepository $subscriptionCustomerRepository,
        private readonly StripeReferenceHelper $stripeReferenceHelper,
    ) {
    }

    public function syncCompletedSession(string $checkoutSessionId, PrestataireProfile $prestataireProfile): bool
    {
        $checkoutSession = $this->stripeApiClient->retrieveCheckoutSession($checkoutSessionId);
        $checkoutStatus = (string) ($checkoutSession['status'] ?? '');
        $paymentStatus = (string) ($checkoutSession['payment_status'] ?? '');

        if ('complete' !== $checkoutStatus) {
            return false;
        }

        if (!\in_array($paymentStatus, ['paid', 'no_payment_required'], true)) {
            return false;
        }

        $stripeSubscriptionId = $this->stripeReferenceHelper->extractExpandableId($checkoutSession['subscription'] ?? null);

        if ('' === $stripeSubscriptionId) {
            return false;
        }

        $subscription = $this->stripeApiClient->retrieveSubscription($stripeSubscriptionId);
        $this->stripeWebhookManager->syncSubscriptionPayload($subscription, false);

        $latestInvoice = $subscription['latest_invoice'] ?? null;
        if (is_array($latestInvoice)) {
            $invoiceStatus = (string) ($latestInvoice['status'] ?? 'paid');
            $eventType = 'paid' === $invoiceStatus ? 'invoice.paid' : 'invoice.created';
            $this->stripeWebhookManager->syncInvoicePayload($eventType, $latestInvoice, false);
        }

        $this->stripeWebhookManager->cleanupDemoSubscriptionsForPrestataire($prestataireProfile, true);

        return true;
    }

    public function syncLatestSubscriptionForPrestataire(PrestataireProfile $prestataireProfile): bool
    {
        $customer = $this->subscriptionCustomerRepository->findOneByPrestataire($prestataireProfile);
        if (!$customer instanceof SubscriptionCustomer) {
            return false;
        }

        $stripeCustomerId = (string) $customer->getStripeCustomerId();
        if (!$this->stripeReferenceHelper->isManagedCustomerId($stripeCustomerId)) {
            return false;
        }

        $subscriptions = $this->stripeApiClient->listSubscriptionsForCustomer($stripeCustomerId);

        foreach ($subscriptions as $subscription) {
            $status = (string) ($subscription['status'] ?? '');
            if (!in_array($status, ['trialing', 'active', 'past_due', 'unpaid', 'canceled', 'paused', 'incomplete_expired'], true)) {
                continue;
            }

            $this->stripeWebhookManager->syncSubscriptionPayload($subscription, false);

            $latestInvoice = $subscription['latest_invoice'] ?? null;
            if (is_array($latestInvoice)) {
                $eventType = match ((string) ($latestInvoice['status'] ?? 'draft')) {
                    'paid' => 'invoice.paid',
                    'open' => 'invoice.finalized',
                    default => 'invoice.created',
                };

                $this->stripeWebhookManager->syncInvoicePayload($eventType, $latestInvoice, false);
            }

            $this->stripeWebhookManager->cleanupDemoSubscriptionsForPrestataire($prestataireProfile, true);

            return true;
        }

        return false;
    }
}
