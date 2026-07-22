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
        $this->syncLatestInvoiceFromSubscriptionPayload($subscription);

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

        $subscriptions = $this->stripeApiClient->listSubscriptionsForCustomer($stripeCustomerId, 20);
        usort($subscriptions, fn (array $left, array $right): int => $this->compareSubscriptions($left, $right));
        $hasSynchronizedAtLeastOneSubscription = false;

        foreach ($subscriptions as $subscription) {
            $status = (string) ($subscription['status'] ?? '');
            if (!in_array($status, ['trialing', 'active', 'past_due', 'unpaid', 'canceled', 'paused', 'incomplete_expired'], true)) {
                continue;
            }

            $stripeSubscriptionId = trim((string) ($subscription['id'] ?? ''));
            if ('' === $stripeSubscriptionId) {
                continue;
            }

            $fullSubscription = $this->stripeApiClient->retrieveSubscription($stripeSubscriptionId);
            $this->stripeWebhookManager->syncSubscriptionPayload($fullSubscription, false);
            $this->syncLatestInvoiceFromSubscriptionPayload($fullSubscription);
            $hasSynchronizedAtLeastOneSubscription = true;
        }

        if (!$hasSynchronizedAtLeastOneSubscription) {
            return false;
        }

        $this->stripeWebhookManager->cleanupDemoSubscriptionsForPrestataire($prestataireProfile, true);

        return true;
    }

    public function syncSubscriptionForPrestataire(string $stripeSubscriptionId, PrestataireProfile $prestataireProfile): bool
    {
        $stripeSubscriptionId = trim($stripeSubscriptionId);
        if ('' === $stripeSubscriptionId) {
            return false;
        }

        $subscription = $this->stripeApiClient->retrieveSubscription($stripeSubscriptionId);
        $this->stripeWebhookManager->syncSubscriptionPayload($subscription, false);
        $this->syncLatestInvoiceFromSubscriptionPayload($subscription);

        $this->stripeWebhookManager->cleanupDemoSubscriptionsForPrestataire($prestataireProfile, true);

        return true;
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     */
    private function compareSubscriptions(array $left, array $right): int
    {
        $statusComparison = $this->getSubscriptionStatusRank((string) ($left['status'] ?? ''))
            <=> $this->getSubscriptionStatusRank((string) ($right['status'] ?? ''));
        if (0 !== $statusComparison) {
            return $statusComparison;
        }

        $periodEndComparison = $this->extractSubscriptionSortTimestamp($right, 'current_period_end')
            <=> $this->extractSubscriptionSortTimestamp($left, 'current_period_end');
        if (0 !== $periodEndComparison) {
            return $periodEndComparison;
        }

        $periodStartComparison = $this->extractSubscriptionSortTimestamp($right, 'current_period_start')
            <=> $this->extractSubscriptionSortTimestamp($left, 'current_period_start');
        if (0 !== $periodStartComparison) {
            return $periodStartComparison;
        }

        $createdComparison = ((int) ($right['created'] ?? 0)) <=> ((int) ($left['created'] ?? 0));
        if (0 !== $createdComparison) {
            return $createdComparison;
        }

        return strcmp((string) ($right['id'] ?? ''), (string) ($left['id'] ?? ''));
    }

    private function getSubscriptionStatusRank(string $status): int
    {
        return match ($status) {
            'active' => 0,
            'trialing' => 1,
            'past_due' => 2,
            'unpaid' => 3,
            'paused' => 4,
            'canceled' => 5,
            'incomplete_expired' => 6,
            default => 7,
        };
    }

    /**
     * @param array<string, mixed> $subscription
     */
    private function extractSubscriptionSortTimestamp(array $subscription, string $key): int
    {
        $value = $subscription[$key] ?? null;
        if (is_numeric($value)) {
            return (int) $value;
        }

        $latestInvoice = $subscription['latest_invoice'] ?? null;
        if (!is_array($latestInvoice)) {
            return 0;
        }

        $invoiceKey = 'current_period_end' === $key ? 'period_end' : 'period_start';
        $invoiceValue = $latestInvoice[$invoiceKey] ?? null;
        if (is_numeric($invoiceValue)) {
            return (int) $invoiceValue;
        }

        $firstLine = $latestInvoice['lines']['data'][0] ?? null;
        if (!is_array($firstLine) || !is_array($firstLine['period'] ?? null)) {
            return 0;
        }

        $lineValue = $firstLine['period']['current_period_end' === $key ? 'end' : 'start'] ?? null;

        return is_numeric($lineValue) ? (int) $lineValue : 0;
    }

    /**
     * @param array<string, mixed> $subscription
     */
    private function syncLatestInvoiceFromSubscriptionPayload(array $subscription): void
    {
        $latestInvoice = $subscription['latest_invoice'] ?? null;
        if (is_string($latestInvoice) && '' !== trim($latestInvoice)) {
            $latestInvoice = $this->stripeApiClient->retrieveInvoice($latestInvoice);
        }

        if (!is_array($latestInvoice)) {
            return;
        }

        $eventType = match ((string) ($latestInvoice['status'] ?? 'draft')) {
            'paid' => 'invoice.paid',
            'open' => 'invoice.finalized',
            default => 'invoice.created',
        };

        $this->stripeWebhookManager->syncInvoicePayload($eventType, $latestInvoice, false);
    }
}
