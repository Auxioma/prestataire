<?php

namespace App\Service\Subscription;

use App\Entity\PrestataireProfile;
use App\Entity\Subscription\SubscriptionCustomer;
use App\Repository\Subscription\SubscriptionCustomerRepository;

class StripeCheckoutSessionSynchronizer
{
    public function __construct(
        private readonly StripeApiClient $stripeApiClient,
        private readonly StripeWebhookManager $stripeWebhookManager,
        private readonly SubscriptionCustomerRepository $subscriptionCustomerRepository,
    ) {
    }

    public function syncCompletedSession(string $checkoutSessionId, PrestataireProfile $prestataireProfile): bool
    {
        $checkoutSession = $this->stripeApiClient->retrieveCheckoutSession($checkoutSessionId);
        $stripeSubscriptionId = trim((string) ($checkoutSession['subscription'] ?? ''));

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

        $stripeCustomerId = trim((string) ($customer->getStripeCustomerId() ?? ''));
        if ('' === $stripeCustomerId || str_starts_with($stripeCustomerId, 'cus_demo_')) {
            return false;
        }

        $subscriptions = $this->stripeApiClient->listSubscriptionsForCustomer($stripeCustomerId);

        foreach ($subscriptions as $subscription) {
            $status = (string) ($subscription['status'] ?? '');
            if (!in_array($status, ['trialing', 'active', 'past_due', 'unpaid'], true)) {
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
