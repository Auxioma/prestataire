<?php

namespace App\Service\Subscription;

use App\Entity\PrestataireProfile;
use App\Entity\Subscription\PrestataireSubscription;
use App\Entity\Subscription\SubscriptionCreditMovement;
use App\Entity\Subscription\SubscriptionCustomer;
use App\Entity\Subscription\SubscriptionInvoice;
use App\Entity\Subscription\SubscriptionPlanPrice;
use App\Enum\SubscriptionBillingPeriodEnum;
use App\Enum\SubscriptionCreditMovementTypeEnum;
use App\Enum\SubscriptionInvoiceStatusEnum;
use App\Enum\SubscriptionStatusEnum;
use App\Repository\PrestataireProfileRepository;
use App\Repository\Subscription\PrestataireSubscriptionRepository;
use App\Repository\Subscription\SubscriptionCreditMovementRepository;
use App\Repository\Subscription\SubscriptionCustomerRepository;
use App\Repository\Subscription\SubscriptionInvoiceRepository;
use App\Repository\Subscription\SubscriptionPlanRepository;
use App\Repository\Subscription\SubscriptionPlanPriceRepository;
use Doctrine\ORM\EntityManagerInterface;

class StripeWebhookManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PrestataireProfileRepository $prestataireProfileRepository,
        private readonly SubscriptionPlanRepository $subscriptionPlanRepository,
        private readonly SubscriptionCustomerRepository $subscriptionCustomerRepository,
        private readonly PrestataireSubscriptionRepository $prestataireSubscriptionRepository,
        private readonly SubscriptionInvoiceRepository $subscriptionInvoiceRepository,
        private readonly SubscriptionCreditMovementRepository $subscriptionCreditMovementRepository,
        private readonly SubscriptionPlanPriceRepository $subscriptionPlanPriceRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $event
     */
    public function handle(array $event): void
    {
        $type = (string) ($event['type'] ?? '');
        $object = $event['data']['object'] ?? null;

        if (!is_array($object) || '' === $type) {
            return;
        }

        match ($type) {
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted' => $this->syncSubscriptionFromStripePayload($object),
            'invoice.created',
            'invoice.finalized',
            'invoice.paid',
            'invoice.payment_failed',
            'invoice.voided' => $this->syncInvoiceFromStripePayload($type, $object),
            default => null,
        };

        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function syncSubscriptionPayload(array $payload, bool $flush = true): void
    {
        $this->syncSubscriptionFromStripePayload($payload);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function syncInvoicePayload(string $eventType, array $payload, bool $flush = true): void
    {
        $this->syncInvoiceFromStripePayload($eventType, $payload);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function cleanupDemoSubscriptionsForPrestataire(PrestataireProfile $prestataireProfile, bool $flush = true): void
    {
        $now = new \DateTimeImmutable();

        foreach ($this->prestataireSubscriptionRepository->findBy(['prestataireProfile' => $prestataireProfile]) as $subscription) {
            $stripeSubscriptionId = trim((string) ($subscription->getStripeSubscriptionId() ?? ''));

            if (!str_starts_with($stripeSubscriptionId, 'sub_demo_')) {
                continue;
            }

            $subscription
                ->setStatus(SubscriptionStatusEnum::CANCELED)
                ->setCancelAtPeriodEnd(false)
                ->setCancellationRequestedAt($subscription->getCancellationRequestedAt() ?? $now)
                ->setCanceledAt($subscription->getCanceledAt() ?? $now)
                ->setEndedAt($subscription->getEndedAt() ?? $now)
                ->setUpdatedAt($now);

            $this->entityManager->persist($subscription);
        }

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function syncSubscriptionFromStripePayload(array $payload): void
    {
        $stripeSubscriptionId = (string) ($payload['id'] ?? '');
        if ('' === $stripeSubscriptionId) {
            return;
        }

        $customer = $this->resolveCustomerFromStripePayload($payload);
        if (!$customer instanceof SubscriptionCustomer) {
            return;
        }

        $prestataireProfile = $customer->getPrestataireProfile();
        if (!$prestataireProfile instanceof PrestataireProfile) {
            return;
        }

        $subscription = $this->prestataireSubscriptionRepository->findOneByStripeSubscriptionId($stripeSubscriptionId)
            ?? new PrestataireSubscription();

        $item = $payload['items']['data'][0] ?? [];
        $priceId = is_array($item) ? (string) ($item['price']['id'] ?? '') : '';
        $stripeItemId = is_array($item) ? (string) ($item['id'] ?? '') : '';
        $plan = '' !== $priceId ? $this->subscriptionPlanRepository->findOneByStripePriceId($priceId) : null;
        $planPrice = '' !== $priceId ? $this->subscriptionPlanPriceRepository->findOneByStripePriceId($priceId) : null;

        $subscription
            ->setPrestataireProfile($prestataireProfile)
            ->setCustomer($customer)
            ->setPlan($plan)
            ->setPlanPrice($planPrice instanceof SubscriptionPlanPrice ? $planPrice : null)
            ->setStripeSubscriptionId($stripeSubscriptionId)
            ->setStripePriceId('' !== $priceId ? $priceId : null)
            ->setStripeSubscriptionItemId('' !== $stripeItemId ? $stripeItemId : null)
            ->setStatus($this->mapStripeSubscriptionStatus((string) ($payload['status'] ?? 'incomplete')))
            ->setBillingPeriod($this->resolveBillingPeriodFromPayload($payload, $plan))
            ->setStartedAt($this->createDateTimeFromTimestamp($payload['start_date'] ?? null))
            ->setCurrentPeriodStart($this->createDateTimeFromTimestamp($payload['current_period_start'] ?? null))
            ->setCurrentPeriodEnd($this->createDateTimeFromTimestamp($payload['current_period_end'] ?? null))
            ->setTrialStartsAt($this->createDateTimeFromTimestamp($payload['trial_start'] ?? null))
            ->setTrialEndsAt($this->createDateTimeFromTimestamp($payload['trial_end'] ?? null))
            ->setCancelAtPeriodEnd((bool) ($payload['cancel_at_period_end'] ?? false))
            ->setCancellationRequestedAt($this->createDateTimeFromTimestamp($payload['canceled_at'] ?? null))
            ->setCanceledAt($this->createDateTimeFromTimestamp($payload['canceled_at'] ?? null))
            ->setEndedAt($this->createDateTimeFromTimestamp($payload['ended_at'] ?? null))
            ->setUpdatedAt(new \DateTimeImmutable());

        if (
            $subscription->getPlan() instanceof \App\Entity\Subscription\SubscriptionPlan
            && $subscription->getStatus()->isUsable()
            && 0 === $subscription->getCreditsGrantedCurrentPeriod()
        ) {
            $subscription->syncCreditsWithPlan();
        }

        $this->entityManager->persist($subscription);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function syncInvoiceFromStripePayload(string $eventType, array $payload): void
    {
        $stripeInvoiceId = (string) ($payload['id'] ?? '');
        if ('' === $stripeInvoiceId) {
            return;
        }

        $subscription = null;
        $stripeSubscriptionId = (string) ($payload['subscription'] ?? '');
        if ('' !== $stripeSubscriptionId) {
            $subscription = $this->prestataireSubscriptionRepository->findOneByStripeSubscriptionId($stripeSubscriptionId);
        }

        $invoice = $this->subscriptionInvoiceRepository->findOneByStripeInvoiceId($stripeInvoiceId)
            ?? new SubscriptionInvoice();

        $invoice
            ->setSubscription($subscription)
            ->setStripeInvoiceId($stripeInvoiceId)
            ->setStripePaymentIntentId(($payload['payment_intent'] ?? null) ?: null)
            ->setInvoiceNumber(($payload['number'] ?? null) ?: null)
            ->setHostedInvoiceUrl(($payload['hosted_invoice_url'] ?? null) ?: null)
            ->setInvoicePdfUrl(($payload['invoice_pdf'] ?? null) ?: null)
            ->setCurrency((string) ($payload['currency'] ?? 'eur'))
            ->setSubtotalAmount($this->normalizeStripeAmount($payload['subtotal'] ?? null))
            ->setTaxAmount($this->normalizeStripeAmount($payload['tax'] ?? null))
            ->setTotalAmount($this->normalizeStripeAmount($payload['total'] ?? null))
            ->setAmountPaid($this->normalizeStripeAmount($payload['amount_paid'] ?? null))
            ->setAmountRemaining($this->normalizeStripeAmount($payload['amount_remaining'] ?? null))
            ->setStatus($this->mapStripeInvoiceStatus((string) ($payload['status'] ?? 'draft')))
            ->setBillingReason(($payload['billing_reason'] ?? null) ?: null)
            ->setPeriodStart($this->createDateTimeFromTimestamp($payload['period_start'] ?? null))
            ->setPeriodEnd($this->createDateTimeFromTimestamp($payload['period_end'] ?? null))
            ->setDueAt($this->createDateTimeFromTimestamp($payload['due_date'] ?? null))
            ->setPaidAt($this->createDateTimeFromTimestamp($payload['status_transitions']['paid_at'] ?? null))
            ->setStripePayload($payload)
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($invoice);

        if ('invoice.paid' !== $eventType || !$subscription instanceof PrestataireSubscription || !$subscription->getPlan()) {
            return;
        }

        $existingMovement = $this->subscriptionCreditMovementRepository->findOneByInvoice($invoice);
        if ($existingMovement instanceof SubscriptionCreditMovement) {
            return;
        }

        $planCredits = $subscription->getPlan()->getCreditsForPeriod($subscription->getBillingPeriod());
        $billingReason = (string) ($payload['billing_reason'] ?? '');

        if (\in_array($billingReason, ['subscription_create', 'subscription_cycle'], true)) {
            $subscription->syncCreditsWithPlan()->setUpdatedAt(new \DateTimeImmutable());

            $movement = (new SubscriptionCreditMovement())
                ->setPrestataireProfile($subscription->getPrestataireProfile())
                ->setSubscription($subscription)
                ->setInvoice($invoice)
                ->setType(SubscriptionCreditMovementTypeEnum::RENEWAL_GRANT)
                ->setCreditsDelta($planCredits)
                ->setBalanceAfter($subscription->getRemainingCredits())
                ->setDescription('Attribution automatique des crédits à la validation du paiement Stripe.');

            $this->entityManager->persist($movement);

            return;
        }

        if ('subscription_update' === $billingReason) {
            $delta = max(0, $planCredits - $subscription->getCreditsGrantedCurrentPeriod());
            if ($delta <= 0) {
                return;
            }

            $subscription->grantCredits($delta)->setUpdatedAt(new \DateTimeImmutable());

            $movement = (new SubscriptionCreditMovement())
                ->setPrestataireProfile($subscription->getPrestataireProfile())
                ->setSubscription($subscription)
                ->setInvoice($invoice)
                ->setType(SubscriptionCreditMovementTypeEnum::UPGRADE_GRANT)
                ->setCreditsDelta($delta)
                ->setBalanceAfter($subscription->getRemainingCredits())
                ->setDescription('Attribution complémentaire des crédits après montée en gamme.');

            $this->entityManager->persist($movement);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveCustomerFromStripePayload(array $payload): ?SubscriptionCustomer
    {
        $stripeCustomerId = (string) ($payload['customer'] ?? '');
        if ('' === $stripeCustomerId) {
            return null;
        }

        $customer = $this->subscriptionCustomerRepository->findOneByStripeCustomerId($stripeCustomerId);
        if ($customer instanceof SubscriptionCustomer) {
            return $customer;
        }

        $prestataireProfileId = $payload['metadata']['prestataire_profile_id'] ?? null;
        if (null === $prestataireProfileId || '' === (string) $prestataireProfileId) {
            return null;
        }

        $prestataireProfile = $this->prestataireProfileRepository->find((string) $prestataireProfileId);
        if (!$prestataireProfile instanceof PrestataireProfile) {
            return null;
        }

        $customer = (new SubscriptionCustomer())
            ->setPrestataireProfile($prestataireProfile)
            ->setStripeCustomerId($stripeCustomerId)
            ->setBillingEmail($prestataireProfile->getAccount()?->getEmail());

        $this->entityManager->persist($customer);

        return $customer;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveBillingPeriodFromPayload(array $payload, mixed $plan): SubscriptionBillingPeriodEnum
    {
        $interval = $payload['items']['data'][0]['price']['recurring']['interval'] ?? null;
        if ('year' === $interval) {
            return SubscriptionBillingPeriodEnum::ANNUAL;
        }

        if ('month' === $interval) {
            return SubscriptionBillingPeriodEnum::MONTHLY;
        }

        $metadataBillingPeriod = $payload['metadata']['billing_period'] ?? null;
        if (is_string($metadataBillingPeriod) && in_array($metadataBillingPeriod, ['monthly', 'annual'], true)) {
            return SubscriptionBillingPeriodEnum::from($metadataBillingPeriod);
        }

        return SubscriptionBillingPeriodEnum::MONTHLY;
    }

    private function mapStripeSubscriptionStatus(string $status): SubscriptionStatusEnum
    {
        return match ($status) {
            'trialing' => SubscriptionStatusEnum::TRIALING,
            'active' => SubscriptionStatusEnum::ACTIVE,
            'past_due' => SubscriptionStatusEnum::PAST_DUE,
            'unpaid' => SubscriptionStatusEnum::UNPAID,
            'canceled' => SubscriptionStatusEnum::CANCELED,
            'paused' => SubscriptionStatusEnum::PAUSED,
            'incomplete_expired' => SubscriptionStatusEnum::INCOMPLETE_EXPIRED,
            default => SubscriptionStatusEnum::INCOMPLETE,
        };
    }

    private function mapStripeInvoiceStatus(string $status): SubscriptionInvoiceStatusEnum
    {
        return match ($status) {
            'open' => SubscriptionInvoiceStatusEnum::OPEN,
            'paid' => SubscriptionInvoiceStatusEnum::PAID,
            'uncollectible' => SubscriptionInvoiceStatusEnum::UNCOLLECTIBLE,
            'void' => SubscriptionInvoiceStatusEnum::VOID,
            default => SubscriptionInvoiceStatusEnum::DRAFT,
        };
    }

    private function createDateTimeFromTimestamp(mixed $timestamp): ?\DateTimeImmutable
    {
        if (!is_numeric($timestamp) || (int) $timestamp <= 0) {
            return null;
        }

        return (new \DateTimeImmutable())->setTimestamp((int) $timestamp);
    }

    private function normalizeStripeAmount(mixed $amount): ?string
    {
        if (!is_numeric($amount)) {
            return null;
        }

        return number_format(((int) $amount) / 100, 2, '.', '');
    }
}
