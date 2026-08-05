<?php

namespace App\Service\Subscription;

use App\Entity\PrestataireProfile;
use App\Entity\Subscription\PrestataireSubscription;
use App\Entity\Subscription\SubscriptionCustomer;
use App\Entity\Subscription\SubscriptionPlan;
use App\Enum\SubscriptionBillingPeriodEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

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

    /**
     * @return array{clientSecret: string, customerId: string}
     */
    public function createEmbeddedSetupIntent(PrestataireProfile $prestataireProfile): array
    {
        $customer = $this->stripeCustomerManager->findOrCreateForPrestataire($prestataireProfile);
        $setupIntent = $this->stripeApiClient->createSetupIntent($prestataireProfile, $customer);
        $clientSecret = trim((string) ($setupIntent['client_secret'] ?? ''));

        if ('' === $clientSecret) {
            throw new \RuntimeException('Stripe n’a pas retourné de client_secret pour le SetupIntent.');
        }

        return [
            'clientSecret' => $clientSecret,
            'customerId' => (string) $customer->getStripeCustomerId(),
        ];
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
    ): array {
        if (
            !$this->isManagedStripeSubscription($currentSubscription)
            || null === $currentSubscription->getStripeSubscriptionId()
            || null === $currentSubscription->getStripeSubscriptionItemId()
        ) {
            throw new \RuntimeException('Impossible de déterminer votre abonnement Stripe actuel pour appliquer cette montée en gamme.');
        }

        $updatedSubscription = $this->stripeApiClient->updateSubscriptionPlan($currentSubscription, $plan, $billingPeriod);
        $updatedSubscription = $this->attemptInvoiceCollection(
            $updatedSubscription,
            $currentSubscription->getCustomer()?->getStripeDefaultPaymentMethodId()
        );

        return $this->synchronizeAndBuildResult($updatedSubscription, $currentSubscription->getPrestataireProfile());
    }

    public function createSubscriptionFromSetupIntent(
        PrestataireProfile $prestataireProfile,
        SubscriptionPlan $plan,
        SubscriptionBillingPeriodEnum $billingPeriod,
        string $setupIntentId,
    ): array {
        $customer = $this->applySetupIntentPaymentMethod($prestataireProfile, $setupIntentId);
        $paymentMethodId = trim((string) $customer->getStripeDefaultPaymentMethodId());

        if ('' === $paymentMethodId) {
            throw new \RuntimeException('Aucun moyen de paiement Stripe par défaut n’a pu être enregistré.');
        }

        $subscription = $this->stripeApiClient->createSubscription(
            $prestataireProfile,
            $plan,
            $billingPeriod,
            $customer,
            $paymentMethodId,
        );
        $subscription = $this->attemptInvoiceCollection($subscription, $paymentMethodId);

        return $this->synchronizeAndBuildResult($subscription, $prestataireProfile);
    }

    public function isManagedStripeSubscription(?PrestataireSubscription $subscription): bool
    {
        return $this->stripeReferenceHelper->isManagedSubscription($subscription);
    }

    public function scheduleCancellation(PrestataireSubscription $subscription): PrestataireSubscription
    {
        if (!$this->isManagedStripeSubscription($subscription)) {
            throw new \RuntimeException('Aucun abonnement Stripe actif ne peut être résilié depuis cet écran.');
        }

        $payload = $this->stripeApiClient->scheduleSubscriptionCancellation($subscription);

        return $this->stripeWebhookManager->syncSubscriptionPayloadAndReturn($payload);
    }

    public function resumeScheduledCancellation(PrestataireSubscription $subscription): PrestataireSubscription
    {
        if (!$this->isManagedStripeSubscription($subscription)) {
            throw new \RuntimeException('Aucun abonnement Stripe actif ne peut être réactivé depuis cet écran.');
        }

        $payload = $this->stripeApiClient->resumeSubscription($subscription);

        return $this->stripeWebhookManager->syncSubscriptionPayloadAndReturn($payload);
    }

    public function applySetupIntentPaymentMethod(
        PrestataireProfile $prestataireProfile,
        string $setupIntentId,
    ): SubscriptionCustomer {
        $customer = $this->stripeCustomerManager->findOrCreateForPrestataire($prestataireProfile);
        $setupIntent = $this->stripeApiClient->retrieveSetupIntent($setupIntentId);
        $paymentMethod = $this->validateConfirmedSetupIntent($setupIntent, $customer);
        $paymentMethodId = $this->resolveReusablePaymentMethodId($customer, $paymentMethod);
        $paymentMethodType = $this->extractPaymentMethodType($paymentMethod);

        $this->stripeApiClient->updateCustomerDefaultPaymentMethod($customer, $paymentMethodId);
        $this->stripeCustomerManager->syncDefaultPaymentMethod($customer, $paymentMethodId, $paymentMethodType, false);

        return $customer;
    }

    /**
     * @param array<string, mixed> $setupIntent
     */
    private function validateConfirmedSetupIntent(array $setupIntent, SubscriptionCustomer $customer): array
    {
        $status = (string) ($setupIntent['status'] ?? '');
        if ('succeeded' !== $status) {
            throw new \RuntimeException('Le moyen de paiement n’a pas été confirmé côté Stripe.');
        }

        $setupIntentCustomerId = $this->stripeReferenceHelper->extractExpandableId($setupIntent['customer'] ?? null);
        $customerId = trim((string) $customer->getStripeCustomerId());
        if ('' === $setupIntentCustomerId || $setupIntentCustomerId !== $customerId) {
            throw new \RuntimeException('Le SetupIntent ne correspond pas au client Stripe attendu.');
        }

        $paymentMethod = $setupIntent['payment_method'] ?? null;
        if (!\is_array($paymentMethod)) {
            throw new \RuntimeException('Stripe n’a pas retourné de moyen de paiement exploitable.');
        }

        $paymentMethodId = trim((string) ($paymentMethod['id'] ?? ''));
        if ('' === $paymentMethodId) {
            throw new \RuntimeException('Stripe n’a pas retourné d’identifiant de moyen de paiement.');
        }

        return $paymentMethod;
    }

    /**
     * @param array<string, mixed> $paymentMethod
     */
    private function resolveReusablePaymentMethodId(SubscriptionCustomer $customer, array $paymentMethod): string
    {
        $paymentMethodId = trim((string) ($paymentMethod['id'] ?? ''));
        $paymentMethodType = $this->extractPaymentMethodType($paymentMethod);
        $stripeCustomerId = trim((string) $customer->getStripeCustomerId());

        if ('card' !== $paymentMethodType || '' === $stripeCustomerId) {
            return $paymentMethodId;
        }

        $fingerprint = trim((string) ($paymentMethod['card']['fingerprint'] ?? ''));
        if ('' === $fingerprint) {
            return $paymentMethodId;
        }

        $expirationMonth = trim((string) ($paymentMethod['card']['exp_month'] ?? ''));
        $expirationYear = trim((string) ($paymentMethod['card']['exp_year'] ?? ''));

        foreach ($this->stripeApiClient->listCustomerPaymentMethods($stripeCustomerId, 'card') as $candidate) {
            $candidateId = trim((string) ($candidate['id'] ?? ''));
            $candidateFingerprint = trim((string) ($candidate['card']['fingerprint'] ?? ''));
            $candidateExpMonth = trim((string) ($candidate['card']['exp_month'] ?? ''));
            $candidateExpYear = trim((string) ($candidate['card']['exp_year'] ?? ''));

            if (
                '' === $candidateId
                || $candidateId === $paymentMethodId
                || $candidateFingerprint !== $fingerprint
                || $candidateExpMonth !== $expirationMonth
                || $candidateExpYear !== $expirationYear
            ) {
                continue;
            }

            try {
                $this->stripeApiClient->detachPaymentMethod($paymentMethodId);
            } catch (\Throwable) {
            }

            return $candidateId;
        }

        return $paymentMethodId;
    }

    /**
     * @param array<string, mixed> $paymentMethod
     */
    private function extractPaymentMethodType(array $paymentMethod): ?string
    {
        $type = trim((string) ($paymentMethod['type'] ?? ''));

        return '' !== $type ? $type : null;
    }

    /**
     * @param array<string, mixed> $subscriptionPayload
     *
     * @return array{
     *     requiresAction: bool,
     *     paymentIntentClientSecret: ?string,
     *     paymentIntentStatus: ?string,
     *     stripeSubscriptionId: ?string,
     *     redirectUrl: string,
     *     message: string
     * }
     */
    private function synchronizeAndBuildResult(array $subscriptionPayload, PrestataireProfile $prestataireProfile): array
    {
        $latestInvoice = $subscriptionPayload['latest_invoice'] ?? null;
        $paymentIntent = \is_array($latestInvoice ?? null) ? ($latestInvoice['payment_intent'] ?? null) : null;
        $paymentIntentStatus = \is_array($paymentIntent) ? trim((string) ($paymentIntent['status'] ?? '')) : '';
        $stripeSubscriptionId = trim((string) ($subscriptionPayload['id'] ?? ''));
        $latestInvoiceStatus = \is_array($latestInvoice) ? trim((string) ($latestInvoice['status'] ?? 'draft')) : '';
        $stripeInvoiceId = \is_array($latestInvoice) ? trim((string) ($latestInvoice['id'] ?? '')) : '';

        if (\in_array($paymentIntentStatus, ['requires_payment_method', 'canceled'], true)) {
            throw new \RuntimeException('Stripe demande un nouveau moyen de paiement pour finaliser l’abonnement.');
        }

        $requiresAction = \in_array($paymentIntentStatus, ['requires_action', 'requires_confirmation'], true);
        $clientSecret = \is_array($paymentIntent) ? (($paymentIntent['client_secret'] ?? null) ?: null) : null;

        if (!$requiresAction && (null === $latestInvoice || 'paid' === $latestInvoiceStatus)) {
            $this->stripeWebhookManager->syncSubscriptionPayload($subscriptionPayload, false);

            if (\is_array($latestInvoice)) {
                $eventType = match ($latestInvoiceStatus) {
                    'paid' => 'invoice.paid',
                    'open' => 'invoice.finalized',
                    default => 'invoice.created',
                };

                $this->stripeWebhookManager->syncInvoicePayload($eventType, $latestInvoice, false);
            }

            $this->stripeWebhookManager->cleanupDemoSubscriptionsForPrestataire($prestataireProfile, false);
            try {
                $this->entityManager->flush();
            } catch (UniqueConstraintViolationException $e) {
                // Ignore duplicate movement created by concurrent process.
            }
        }

        return [
            'requiresAction' => $requiresAction && \is_string($clientSecret) && '' !== $clientSecret,
            'paymentIntentClientSecret' => \is_string($clientSecret) && '' !== $clientSecret ? $clientSecret : null,
            'paymentIntentStatus' => '' !== $paymentIntentStatus ? $paymentIntentStatus : null,
            'stripeSubscriptionId' => '' !== $stripeSubscriptionId ? $stripeSubscriptionId : null,
            'redirectUrl' => '/prestataire/abonnements',
            'message' => $requiresAction
                ? 'Une authentification bancaire supplémentaire est requise pour finaliser l’abonnement.'
                : (
                    null !== $latestInvoice && 'paid' !== $latestInvoiceStatus
                    ? 'Le paiement Stripe reste en attente de confirmation. L’abonnement local ne sera mis à jour qu’après règlement effectif.'
                    : 'L’abonnement a été activé et synchronisé avec succès.'
                ),
        ];
    }

    /**
     * @param array<string, mixed> $subscriptionPayload
     *
     * @return array<string, mixed>
     */
    private function attemptInvoiceCollection(array $subscriptionPayload, ?string $paymentMethodId): array
    {
        $latestInvoice = $subscriptionPayload['latest_invoice'] ?? null;
        if (!\is_array($latestInvoice)) {
            return $subscriptionPayload;
        }

        $stripeInvoiceId = trim((string) ($latestInvoice['id'] ?? ''));
        $invoiceStatus = trim((string) ($latestInvoice['status'] ?? ''));
        $amountRemaining = (int) ($latestInvoice['amount_remaining'] ?? 0);

        if ('' === $stripeInvoiceId || 'paid' === $invoiceStatus || $amountRemaining <= 0) {
            return $subscriptionPayload;
        }

        $paidInvoice = $this->stripeApiClient->payInvoice($stripeInvoiceId, $paymentMethodId);
        $subscriptionPayload['latest_invoice'] = $paidInvoice;

        $stripeSubscriptionId = trim((string) ($subscriptionPayload['id'] ?? ''));
        if ('' === $stripeSubscriptionId) {
            return $subscriptionPayload;
        }

        return $this->stripeApiClient->retrieveSubscription($stripeSubscriptionId);
    }
}
