<?php

namespace App\Service\Subscription;

use App\Entity\PrestataireProfile;
use App\Entity\Subscription\PrestataireSubscription;
use App\Entity\Subscription\SubscriptionCustomer;
use App\Entity\Subscription\SubscriptionPlan;
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
    public function updateSubscriptionPlan(
        PrestataireSubscription $subscription,
        SubscriptionPlan $plan,
        SubscriptionBillingPeriodEnum $billingPeriod,
    ): array {
        $stripeSubscriptionId = $subscription->getStripeSubscriptionId();
        $stripeSubscriptionItemId = $subscription->getStripeSubscriptionItemId();
        $priceId = $plan->getStripePriceIdForPeriod($billingPeriod);

        if (null === $stripeSubscriptionId || null === $stripeSubscriptionItemId || null === $priceId) {
            throw new \InvalidArgumentException('Impossible de modifier l’abonnement Stripe sans identifiants complets.');
        }

        return $this->request('POST', sprintf('/subscriptions/%s', $stripeSubscriptionId), [
            'items[0][id]' => $stripeSubscriptionItemId,
            'items[0][price]' => $priceId,
            'proration_behavior' => 'always_invoice',
            'cancel_at_period_end' => 'false',
            'metadata[plan_code]' => $plan->getCode(),
            'metadata[billing_period]' => $billingPeriod->value,
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
        ]);
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
