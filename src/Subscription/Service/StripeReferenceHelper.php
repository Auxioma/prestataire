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

use App\Subscription\Entity\PrestataireSubscription;
use App\Subscription\Entity\SubscriptionCustomer;

final class StripeReferenceHelper
{
    public function extractExpandableId(mixed $value): string
    {
        if (\is_string($value)) {
            return mb_trim($value);
        }

        if (\is_array($value) && \is_string($value['id'] ?? null)) {
            return mb_trim($value['id']);
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
        $stripeCustomerId = mb_trim((string) $stripeCustomerId);

        return '' !== $stripeCustomerId && !str_starts_with($stripeCustomerId, 'cus_demo_');
    }

    public function isManagedSubscriptionId(?string $stripeSubscriptionId): bool
    {
        $stripeSubscriptionId = mb_trim((string) $stripeSubscriptionId);

        return '' !== $stripeSubscriptionId && !str_starts_with($stripeSubscriptionId, 'sub_demo_');
    }

    public function isManagedSubscriptionItemId(?string $stripeSubscriptionItemId): bool
    {
        $stripeSubscriptionItemId = mb_trim((string) $stripeSubscriptionItemId);

        return '' !== $stripeSubscriptionItemId && !str_starts_with($stripeSubscriptionItemId, 'si_demo_');
    }
}
