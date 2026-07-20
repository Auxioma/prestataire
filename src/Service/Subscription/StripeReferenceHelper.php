<?php

namespace App\Service\Subscription;

use App\Entity\Subscription\PrestataireSubscription;
use App\Entity\Subscription\SubscriptionCustomer;

final class StripeReferenceHelper
{
    public function extractExpandableId(mixed $value): string
    {
        if (\is_string($value)) {
            return trim($value);
        }

        if (\is_array($value) && \is_string($value['id'] ?? null)) {
            return trim($value['id']);
        }

        return '';
    }

    public function extractNullableExpandableId(mixed $value): ?string
    {
        $id = $this->extractExpandableId($value);

        return '' !== $id ? $id : null;
    }

    public function isManagedCustomer(?SubscriptionCustomer $customer): bool
    {
        return $customer instanceof SubscriptionCustomer
            && $this->isManagedCustomerId($customer->getStripeCustomerId());
    }

    public function isManagedSubscription(?PrestataireSubscription $subscription): bool
    {
        return $subscription instanceof PrestataireSubscription
            && $this->isManagedSubscriptionId($subscription->getStripeSubscriptionId())
            && $this->isManagedSubscriptionItemId($subscription->getStripeSubscriptionItemId());
    }

    public function isManagedCustomerId(?string $stripeCustomerId): bool
    {
        $stripeCustomerId = trim((string) $stripeCustomerId);

        return '' !== $stripeCustomerId && !str_starts_with($stripeCustomerId, 'cus_demo_');
    }

    public function isManagedSubscriptionId(?string $stripeSubscriptionId): bool
    {
        $stripeSubscriptionId = trim((string) $stripeSubscriptionId);

        return '' !== $stripeSubscriptionId && !str_starts_with($stripeSubscriptionId, 'sub_demo_');
    }

    public function isManagedSubscriptionItemId(?string $stripeSubscriptionItemId): bool
    {
        $stripeSubscriptionItemId = trim((string) $stripeSubscriptionItemId);

        return '' !== $stripeSubscriptionItemId && !str_starts_with($stripeSubscriptionItemId, 'si_demo_');
    }
}
