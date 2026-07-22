<?php

namespace App\Service\Subscription;

use App\Entity\PrestataireProfile;
use App\Entity\Subscription\PrestataireSubscription;
use App\Entity\Subscription\SubscriptionCustomer;
use App\Entity\Subscription\SubscriptionPlan;
use App\Entity\Subscription\SubscriptionPlanPrice;
use App\Enum\SubscriptionBillingPeriodEnum;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class StripeApiClient
{
    private const API_BASE_URI = 'https://api.stripe.com/v1';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $stripeSecretKey,
    ) {
    }

    public function isConfigured(): bool
    {
        return '' !== trim($this->stripeSecretKey);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function createCustomer(PrestataireProfile $prestataireProfile): array
    {
        $account = $prestataireProfile->getAccount();
        $name = trim(($account?->getFirstName() ?? '') . ' ' . ($account?->getLastName() ?? ''));

        return $this->request('POST', '/customers', [
            'email' => $account?->getEmail(),
            'name' => '' !== $name ? $name : ($prestataireProfile->getCompanyName() ?? 'Prestataire TrouveMoi'),
            'metadata[prestataire_profile_id]' => (string) $prestataireProfile->getId(),
            'metadata[company_name]' => $prestataireProfile->getCompanyName(),
        ]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function createSetupIntent(
        PrestataireProfile $prestataireProfile,
        SubscriptionCustomer $customer,
    ): array {
        $stripeCustomerId = trim((string) $customer->getStripeCustomerId());
        if ('' === $stripeCustomerId) {
            throw new \InvalidArgumentException('Aucun client Stripe n’est disponible pour créer le SetupIntent.');
        }

        return $this->request('POST', '/setup_intents', [
            'customer' => $stripeCustomerId,
            'usage' => 'off_session',
            'payment_method_types[0]' => 'card',
            'metadata[prestataire_profile_id]' => (string) $prestataireProfile->getId(),
            'metadata[customer_id]' => $stripeCustomerId,
        ]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function createCheckoutSession(
        PrestataireProfile $prestataireProfile,
        SubscriptionPlan $plan,
        SubscriptionBillingPeriodEnum $billingPeriod,
        string $successUrl,
        string $cancelUrl,
        ?SubscriptionCustomer $customer = null,
    ): array {
        $priceId = $plan->getStripePriceIdForPeriod($billingPeriod);
        if (null === $priceId) {
            throw new \InvalidArgumentException('Aucun prix Stripe n’est configuré pour cette période de facturation.');
        }

        $payload = [
            'mode' => 'subscription',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'payment_method_types[0]' => 'card',
            'payment_method_collection' => 'always',
            'billing_address_collection' => 'auto',
            'line_items[0][price]' => $priceId,
            'line_items[0][quantity]' => 1,
            'allow_promotion_codes' => 'false',
            'client_reference_id' => (string) $prestataireProfile->getId(),
            'metadata[prestataire_profile_id]' => (string) $prestataireProfile->getId(),
            'metadata[plan_code]' => $plan->getCode(),
            'metadata[billing_period]' => $billingPeriod->value,
            'subscription_data[metadata][prestataire_profile_id]' => (string) $prestataireProfile->getId(),
            'subscription_data[metadata][plan_code]' => $plan->getCode(),
            'subscription_data[metadata][billing_period]' => $billingPeriod->value,
        ];

        if ($customer instanceof SubscriptionCustomer && '' !== trim((string) $customer->getStripeCustomerId())) {
            $payload['customer'] = $customer->getStripeCustomerId();
        } else {
            $payload['customer_email'] = $prestataireProfile->getAccount()?->getEmail();
        }

        return $this->request('POST', '/checkout/sessions', $payload);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function createBillingPortalSession(SubscriptionCustomer $customer, string $returnUrl): array
    {
        return $this->request('POST', '/billing_portal/sessions', [
            'customer' => trim((string) $customer->getStripeCustomerId()),
            'return_url' => $returnUrl,
        ]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function retrieveSetupIntent(string $setupIntentId): array
    {
        return $this->request('GET', sprintf('/setup_intents/%s', $setupIntentId), [
            'expand[0]' => 'payment_method',
            'expand[1]' => 'customer',
        ]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function updateCustomerDefaultPaymentMethod(SubscriptionCustomer $customer, string $paymentMethodId): array
    {
        $stripeCustomerId = trim((string) $customer->getStripeCustomerId());
        if ('' === $stripeCustomerId) {
            throw new \InvalidArgumentException('Le client Stripe est introuvable.');
        }

        return $this->request('POST', sprintf('/customers/%s', $stripeCustomerId), [
            'invoice_settings[default_payment_method]' => $paymentMethodId,
        ]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function createSubscription(
        PrestataireProfile $prestataireProfile,
        SubscriptionPlan $plan,
        SubscriptionBillingPeriodEnum $billingPeriod,
        SubscriptionCustomer $customer,
        string $paymentMethodId,
    ): array {
        $priceId = $plan->getStripePriceIdForPeriod($billingPeriod);
        $stripeCustomerId = trim((string) $customer->getStripeCustomerId());

        if (null === $priceId || '' === $stripeCustomerId) {
            throw new \InvalidArgumentException('Impossible de créer l’abonnement Stripe sans client et prix valides.');
        }

        return $this->request('POST', '/subscriptions', [
            'customer' => $stripeCustomerId,
            'items[0][price]' => $priceId,
            'default_payment_method' => $paymentMethodId,
            'collection_method' => 'charge_automatically',
            'payment_behavior' => 'default_incomplete',
            'payment_settings[save_default_payment_method]' => 'on_subscription',
            'metadata[prestataire_profile_id]' => (string) $prestataireProfile->getId(),
            'metadata[plan_code]' => $plan->getCode(),
            'metadata[billing_period]' => $billingPeriod->value,
            'expand[0]' => 'items.data.price',
            'expand[1]' => 'latest_invoice',
            'expand[2]' => 'latest_invoice.payment_intent',
        ]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function updateSubscriptionPlan(
        PrestataireSubscription $subscription,
        SubscriptionPlan $plan,
        SubscriptionBillingPeriodEnum $billingPeriod,
    ): array {
        $stripeSubscriptionId = $subscription->getStripeSubscriptionId();
        $stripeSubscriptionItemId = $subscription->getStripeSubscriptionItemId();
        $priceId = $plan->getStripePriceIdForPeriod($billingPeriod);
        $defaultPaymentMethodId = trim((string) $subscription->getCustomer()?->getStripeDefaultPaymentMethodId());

        if (null === $stripeSubscriptionId || null === $stripeSubscriptionItemId || null === $priceId) {
            throw new \InvalidArgumentException('Impossible de modifier l’abonnement Stripe sans identifiants complets.');
        }

        $payload = [
            'items[0][id]' => $stripeSubscriptionItemId,
            'items[0][price]' => $priceId,
            'billing_cycle_anchor' => 'now',
            'proration_behavior' => 'none',
            'payment_behavior' => 'default_incomplete',
            'cancel_at_period_end' => 'false',
            'metadata[plan_code]' => $plan->getCode(),
            'metadata[billing_period]' => $billingPeriod->value,
            'expand[0]' => 'items.data.price',
            'expand[1]' => 'latest_invoice',
            'expand[2]' => 'latest_invoice.payment_intent',
        ];

        if ('' !== $defaultPaymentMethodId) {
            $payload['default_payment_method'] = $defaultPaymentMethodId;
        }

        return $this->request('POST', sprintf('/subscriptions/%s', $stripeSubscriptionId), $payload);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function scheduleSubscriptionCancellation(PrestataireSubscription $subscription): array
    {
        $stripeSubscriptionId = trim((string) $subscription->getStripeSubscriptionId());
        if ('' === $stripeSubscriptionId) {
            throw new \InvalidArgumentException('Impossible de résilier un abonnement Stripe sans identifiant.');
        }

        return $this->request('POST', sprintf('/subscriptions/%s', $stripeSubscriptionId), [
            'cancel_at_period_end' => 'true',
            'expand[0]' => 'items.data.price',
            'expand[1]' => 'latest_invoice',
            'expand[2]' => 'latest_invoice.payment_intent',
        ]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function resumeSubscription(PrestataireSubscription $subscription): array
    {
        $stripeSubscriptionId = trim((string) $subscription->getStripeSubscriptionId());
        if ('' === $stripeSubscriptionId) {
            throw new \InvalidArgumentException('Impossible de réactiver un abonnement Stripe sans identifiant.');
        }

        return $this->request('POST', sprintf('/subscriptions/%s', $stripeSubscriptionId), [
            'cancel_at_period_end' => 'false',
            'expand[0]' => 'items.data.price',
            'expand[1]' => 'latest_invoice',
            'expand[2]' => 'latest_invoice.payment_intent',
        ]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function retrieveSubscription(string $stripeSubscriptionId): array
    {
        return $this->request('GET', sprintf('/subscriptions/%s', $stripeSubscriptionId), [
            'expand[0]' => 'items.data.price',
            'expand[1]' => 'latest_invoice',
            'expand[2]' => 'latest_invoice.payment_intent',
        ]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function retrieveInvoice(string $stripeInvoiceId): array
    {
        return $this->request('GET', sprintf('/invoices/%s', $stripeInvoiceId), [
            'expand[0]' => 'payment_intent',
        ]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function retrieveCheckoutSession(string $checkoutSessionId): array
    {
        return $this->request('GET', sprintf('/checkout/sessions/%s', $checkoutSessionId), [
            'expand[0]' => 'subscription',
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function listSubscriptionsForCustomer(string $stripeCustomerId, int $limit = 5): array
    {
        $response = $this->request('GET', '/subscriptions', [
            'customer' => $stripeCustomerId,
            'status' => 'all',
            'limit' => (string) max(1, min(100, $limit)),
            'expand[0]' => 'data.latest_invoice',
            'expand[1]' => 'data.items.data.price',
        ]);

        $data = $response['data'] ?? [];

        return is_array($data) ? array_values(array_filter($data, 'is_array')) : [];
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function listCustomerPaymentMethods(string $stripeCustomerId, string $type = 'card', int $limit = 20): array
    {
        $response = $this->request('GET', '/payment_methods', [
            'customer' => $stripeCustomerId,
            'type' => $type,
            'limit' => (string) max(1, min(100, $limit)),
        ]);

        $data = $response['data'] ?? [];

        return is_array($data) ? array_values(array_filter($data, 'is_array')) : [];
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function detachPaymentMethod(string $paymentMethodId): array
    {
        return $this->request('POST', sprintf('/payment_methods/%s/detach', $paymentMethodId));
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function payInvoice(string $stripeInvoiceId, ?string $paymentMethodId = null): array
    {
        $payload = [
            'expand[0]' => 'payment_intent',
        ];

        $paymentMethodId = trim((string) $paymentMethodId);
        if ('' !== $paymentMethodId) {
            $payload['payment_method'] = $paymentMethodId;
        }

        return $this->request('POST', sprintf('/invoices/%s/pay', $stripeInvoiceId), $payload);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function createProduct(SubscriptionPlan $plan): array
    {
        return $this->request('POST', '/products', [
            'name' => (string) $plan->getName(),
            'description' => $plan->getDescription(),
            'metadata[plan_code]' => $plan->getCode(),
        ]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function createPrice(SubscriptionPlan $plan, SubscriptionPlanPrice $planPrice, string $productId): array
    {
        $unitAmount = number_format((float) $planPrice->getAmount() * 100, 0, '.', '');
        $interval = match ($planPrice->getBillingPeriod()) {
            SubscriptionBillingPeriodEnum::MONTHLY => 'month',
            SubscriptionBillingPeriodEnum::ANNUAL => 'year',
        };

        return $this->request('POST', '/prices', [
            'product' => $productId,
            'currency' => 'eur',
            'unit_amount' => $unitAmount,
            'recurring[interval]' => $interval,
            'nickname' => $planPrice->getLabel() ?: sprintf('%s %s', $plan->getName(), $planPrice->getBillingPeriod()->getLabel()),
            'metadata[plan_code]' => $plan->getCode(),
            'metadata[billing_period]' => $planPrice->getBillingPeriod()->value,
            'metadata[promotional]' => $planPrice->isPromotional() ? '1' : '0',
        ]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function retrievePrice(string $stripePriceId): array
    {
        return $this->request('GET', sprintf('/prices/%s', $stripePriceId));
    }

    /**
     * @param array<string, scalar|null> $payload
     *
     * @return array<string, mixed>
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function request(string $method, string $path, array $payload = []): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Stripe n’est pas configuré.');
        }

        $options = [
            'auth_bearer' => $this->stripeSecretKey,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ];

        if ([] !== $payload) {
            $options['body'] = http_build_query(array_filter(
                $payload,
                static fn(mixed $value): bool => null !== $value
            ));
        }

        $response = $this->httpClient->request($method, self::API_BASE_URI . $path, $options);

        $data = $response->toArray(false);

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException($this->extractErrorMessage($data));
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractErrorMessage(array $data): string
    {
        $error = $data['error'] ?? null;
        if (!is_array($error)) {
            return 'Stripe a refusé la requête.';
        }

        $message = trim((string) ($error['message'] ?? ''));
        $code = trim((string) ($error['code'] ?? ''));
        $param = trim((string) ($error['param'] ?? ''));

        $parts = [];

        if ('' !== $message) {
            $parts[] = $message;
        }

        if ('' !== $code) {
            $parts[] = sprintf('code Stripe : %s', $code);
        }

        if ('' !== $param) {
            $parts[] = sprintf('champ : %s', $param);
        }

        if ([] === $parts) {
            return 'Stripe a refusé la requête.';
        }

        return implode(' | ', $parts);
    }
}
