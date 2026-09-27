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

use App\Prestataire\Entity\PrestataireProfile;
use App\Subscription\Entity\PrestataireSubscription;
use App\Subscription\Repository\PrestataireSubscriptionRepository;

class SubscriptionAccessManager
{
    public function __construct(
        private readonly PrestataireSubscriptionRepository $prestataireSubscriptionRepository,
    ) {
    }

    public function getCurrentUsableSubscription(PrestataireProfile $prestataireProfile): ?PrestataireSubscription
    {
        return $this->prestataireSubscriptionRepository->findCurrentUsableForPrestataire($prestataireProfile);
    }

    public function canRespondToQuoteRequests(PrestataireProfile $prestataireProfile): bool
    {
        $subscription = $this->getCurrentUsableSubscription($prestataireProfile);

        return $subscription?->canRespondToQuoteRequests() ?? false;
    }

    public function canUseInstantMessaging(PrestataireProfile $prestataireProfile): bool
    {
        $subscription = $this->getCurrentUsableSubscription($prestataireProfile);

        return $subscription?->canUseInstantMessaging() ?? false;
    }

    public function getRemainingCredits(PrestataireProfile $prestataireProfile): int
    {
        $subscription = $this->getCurrentUsableSubscription($prestataireProfile);

        return $subscription?->getRemainingCredits() ?? 0;
    }

    public function requireQuoteResponseAccess(PrestataireProfile $prestataireProfile): PrestataireSubscription
    {
        $subscription = $this->getCurrentUsableSubscription($prestataireProfile);

        if (!$subscription instanceof PrestataireSubscription || !$subscription->canRespondToQuoteRequests()) {
            throw new \DomainException('Un abonnement actif avec au moins un crédit est requis pour répondre à un devis.');
        }

        return $subscription;
    }

    public function requireMessagingAccess(PrestataireProfile $prestataireProfile): PrestataireSubscription
    {
        $subscription = $this->getCurrentUsableSubscription($prestataireProfile);

        if (!$subscription instanceof PrestataireSubscription || !$subscription->canUseInstantMessaging()) {
            throw new \DomainException('Un abonnement actif est requis pour utiliser la messagerie instantanée.');
        }

        return $subscription;
    }
}
