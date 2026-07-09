<?php

namespace App\Service\Subscription;

use App\Entity\PrestataireProfile;
use App\Entity\Subscription\PrestataireSubscription;
use App\Repository\Subscription\PrestataireSubscriptionRepository;

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
