<?php

namespace App\Service\Subscription;

use App\Entity\PrestataireProfile;
use App\Entity\Subscription\PrestataireSubscription;
use App\Entity\Subscription\SubscriptionPlan;
use App\Enum\SubscriptionBillingPeriodEnum;
use Doctrine\ORM\EntityManagerInterface;

final class StripeSubscriptionCheckoutManager
{
    public function __construct(
        private readonly StripeApiClient $stripeApiClient,
        private readonly StripeCustomerManager $stripeCustomerManager,
        private readonly StripeReferenceHelper $stripeReferenceHelper,
        private readonly StripeWebhookManager $stripeWebhookManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function startSubscriptionCheckout(
        PrestataireProfile $prestataireProfile,
        SubscriptionPlan $plan,
        SubscriptionBillingPeriodEnum $billingPeriod,
        string $successUrl,
        string $cancelUrl,
    ): string {
        $customer = $this->stripeCustomerManager->findOrCreateForPrestataire($prestataireProfile);
        $checkoutSession = $this->stripeApiClient->createCheckoutSession(
            $prestataireProfile,
            $plan,
            $billingPeriod,
            $successUrl,
            $cancelUrl,
            $customer,
        );

        $checkoutUrl = $checkoutSession['url'] ?? null;
        if (!is_string($checkoutUrl) || '' === $checkoutUrl) {
            throw new \RuntimeException('Impossible de créer la session Stripe.');
        }

        return $checkoutUrl;
    }

    public function requestUpgrade(
        PrestataireSubscription $currentSubscription,
        SubscriptionPlan $plan,
        SubscriptionBillingPeriodEnum $billingPeriod,
    ): void {
        if (
            !$this->isManagedStripeSubscription($currentSubscription)
            || null === $currentSubscription->getStripeSubscriptionId()
            || null === $currentSubscription->getStripeSubscriptionItemId()
        ) {
            throw new \RuntimeException('Impossible de déterminer votre abonnement Stripe actuel pour appliquer cette montée en gamme.');
        }

        $updatedSubscription = $this->stripeApiClient->updateSubscriptionPlan($currentSubscription, $plan, $billingPeriod);

        $latestInvoice = $updatedSubscription['latest_invoice'] ?? null;
        if (\is_array($latestInvoice)) {
            $eventType = match ((string) ($latestInvoice['status'] ?? 'draft')) {
                'paid' => 'invoice.paid',
                'open' => 'invoice.finalized',
                default => 'invoice.created',
            };

            $this->stripeWebhookManager->syncSubscriptionPayload($updatedSubscription, false);
            $this->stripeWebhookManager->syncInvoicePayload($eventType, $latestInvoice, true);

            return;
        }

        $this->stripeWebhookManager->syncSubscriptionPayload($updatedSubscription, true);
        $this->entityManager->flush();
    }

    public function isManagedStripeSubscription(?PrestataireSubscription $subscription): bool
    {
        return $this->stripeReferenceHelper->isManagedSubscription($subscription);
    }
}
