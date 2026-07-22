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

final class StripeWebhookManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StripeApiClient $stripeApiClient,
        private readonly StripeReferenceHelper $stripeReferenceHelper,
        private readonly PrestataireProfileRepository $prestataireProfileRepository,
        private readonly SubscriptionPlanRepository $subscriptionPlanRepository,
        private readonly SubscriptionCustomerRepository $subscriptionCustomerRepository,
        private readonly PrestataireSubscriptionRepository $prestataireSubscriptionRepository,
        private readonly SubscriptionInvoiceRepository $subscriptionInvoiceRepository,
        private readonly SubscriptionCreditMovementRepository $subscriptionCreditMovementRepository,
        private readonly SubscriptionPlanPriceRepository $subscriptionPlanPriceRepository,
        private readonly SubscriptionCreditManager $subscriptionCreditManager,
        private readonly SubscriptionUpgradePolicy $subscriptionUpgradePolicy,
        private readonly SubscriptionFallbackManager $subscriptionFallbackManager,
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
            'checkout.session.completed' => $this->syncCheckoutSessionCompleted($object),
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

            if ($this->stripeReferenceHelper->isManagedSubscriptionId($stripeSubscriptionId)) {
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
    private function syncSubscriptionFromStripePayload(array $payload): ?PrestataireSubscription
    {
        $stripeSubscriptionId = (string) ($payload['id'] ?? '');
        if ('' === $stripeSubscriptionId) {
            return null;
        }

        $customer = $this->resolveCustomerFromStripePayload($payload);
        if (!$customer instanceof SubscriptionCustomer) {
            return null;
        }

        $prestataireProfile = $customer->getPrestataireProfile();
        if (!$prestataireProfile instanceof PrestataireProfile) {
            return null;
        }

        $subscription = $this->prestataireSubscriptionRepository->findOneByStripeSubscriptionId($stripeSubscriptionId)
            ?? new PrestataireSubscription();
        $previousPlan = $subscription->getPlan();
        $previousBillingPeriod = $subscription->getBillingPeriod();
        $previousCurrentPeriodStart = $subscription->getCurrentPeriodStart();
        $previousRemainingCredits = $subscription->getRemainingCredits();

        $item = $payload['items']['data'][0] ?? [];
        $priceId = is_array($item) ? (string) ($item['price']['id'] ?? '') : '';
        $stripeItemId = is_array($item) ? (string) ($item['id'] ?? '') : '';
        $plan = '' !== $priceId ? $this->subscriptionPlanRepository->findOneByStripePriceId($priceId) : null;
        $planPrice = '' !== $priceId ? $this->subscriptionPlanPriceRepository->findOneByStripePriceId($priceId) : null;
        [$currentPeriodStart, $currentPeriodEnd] = $this->resolveSubscriptionPeriodBounds($payload);

        $subscription
            ->setPrestataireProfile($prestataireProfile)
            ->setCustomer($customer)
            ->setPlan($plan)
            ->setPlanPrice($planPrice instanceof SubscriptionPlanPrice ? $planPrice : null)
            ->setStripeSubscriptionId($stripeSubscriptionId)
            ->setStripePriceId('' !== $priceId ? $priceId : null)
            ->setStripeSubscriptionItemId('' !== $stripeItemId ? $stripeItemId : null)
            ->setStatus($this->mapStripeSubscriptionStatus((string) ($payload['status'] ?? 'incomplete')))
            ->setBillingPeriod($this->resolveBillingPeriodFromPayload($payload))
            ->setStartedAt($this->createDateTimeFromTimestamp($payload['start_date'] ?? null))
            ->setCurrentPeriodStart($currentPeriodStart)
            ->setCurrentPeriodEnd($currentPeriodEnd)
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

        $this->applySubscriptionCycleCreditSnapshot(
            $subscription,
            $previousPlan,
            $previousBillingPeriod,
            $previousCurrentPeriodStart,
            $previousRemainingCredits,
        );

        if ($this->subscriptionFallbackManager->shouldFallbackToFree($subscription)) {
            $this->subscriptionFallbackManager->fallbackToFree(
                $prestataireProfile,
                $subscription,
                'stripe_subscription_status_change'
            );
        }

        $this->entityManager->persist($subscription);

        return $subscription;
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
        $stripeSubscriptionId = $this->stripeReferenceHelper->extractExpandableId($payload['subscription'] ?? null);
        if ('' !== $stripeSubscriptionId) {
            $subscription = $this->prestataireSubscriptionRepository->findOneByStripeSubscriptionId($stripeSubscriptionId);
            if (!$subscription instanceof PrestataireSubscription) {
                $remoteSubscription = $this->stripeApiClient->retrieveSubscription($stripeSubscriptionId);
                $subscription = $this->syncSubscriptionFromStripePayload($remoteSubscription);
            }
        }

        $invoice = $this->subscriptionInvoiceRepository->findOneByStripeInvoiceId($stripeInvoiceId)
            ?? new SubscriptionInvoice();

        $invoice
            ->setSubscription($subscription)
            ->setStripeInvoiceId($stripeInvoiceId)
            ->setStripePaymentIntentId($this->stripeReferenceHelper->extractNullableExpandableId($payload['payment_intent'] ?? null))
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

        if ('subscription_create' === $billingReason) {
            $this->applyCreatedSubscriptionCredits($subscription, $invoice, $planCredits);
            return;
        }

        if ('subscription_cycle' === $billingReason) {
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
            $currentRemainingCredits = $subscription->getRemainingCredits();
            $targetRemainingCredits = $this->subscriptionUpgradePolicy->calculateCappedRemainingCredits(
                $currentRemainingCredits,
                $planCredits
            );
            $delta = max(0, $targetRemainingCredits - $currentRemainingCredits);

            if ($delta <= 0) {
                return;
            }

            $movement = $this->subscriptionCreditManager->grantCredits(
                $subscription,
                $delta,
                SubscriptionCreditMovementTypeEnum::UPGRADE_GRANT,
                'Attribution complémentaire des crédits après montée en gamme.',
                [
                    'billing_reason' => $billingReason,
                    'cap_applied' => true,
                    'included_plan_credits' => $planCredits,
                    'remaining_before_upgrade' => $currentRemainingCredits,
                    'remaining_after_upgrade' => $targetRemainingCredits,
                ]
            );
            $movement->setInvoice($invoice);

            return;
        }

        $this->applyFallbackPaidInvoiceCredits($subscription, $invoice, $planCredits, $billingReason);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveCustomerFromStripePayload(array $payload): ?SubscriptionCustomer
    {
        $stripeCustomerId = $this->stripeReferenceHelper->extractExpandableId($payload['customer'] ?? null);
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
    private function resolveBillingPeriodFromPayload(array $payload): SubscriptionBillingPeriodEnum
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

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{0: ?\DateTimeImmutable, 1: ?\DateTimeImmutable}
     */
    private function resolveSubscriptionPeriodBounds(array $payload): array
    {
        $currentPeriodStart = $this->createDateTimeFromTimestamp($payload['current_period_start'] ?? null);
        $currentPeriodEnd = $this->createDateTimeFromTimestamp($payload['current_period_end'] ?? null);

        if ($currentPeriodStart instanceof \DateTimeImmutable && $currentPeriodEnd instanceof \DateTimeImmutable) {
            return [$currentPeriodStart, $currentPeriodEnd];
        }

        $latestInvoice = $payload['latest_invoice'] ?? null;
        if (!is_array($latestInvoice)) {
            return [$currentPeriodStart, $currentPeriodEnd];
        }

        $invoiceCreatedAt = $this->createDateTimeFromTimestamp($latestInvoice['created'] ?? null);
        $invoicePeriodStart = $this->createDateTimeFromTimestamp($latestInvoice['period_start'] ?? null);
        $invoicePeriodEnd = $this->createDateTimeFromTimestamp($latestInvoice['period_end'] ?? null);
        [$linePeriodStart, $linePeriodEnd] = $this->extractInvoiceLinePeriodBounds($latestInvoice);

        if ($this->isValidSubscriptionPeriod($linePeriodStart, $linePeriodEnd)) {
            return [
                $currentPeriodStart ?? $linePeriodStart,
                $currentPeriodEnd ?? $linePeriodEnd,
            ];
        }

        if (
            $this->isValidSubscriptionPeriod($invoicePeriodStart, $invoicePeriodEnd)
            && !$this->looksLikeImmediateInvoiceSnapshot($invoiceCreatedAt, $invoicePeriodStart, $invoicePeriodEnd)
        ) {
            return [
                $currentPeriodStart ?? $invoicePeriodStart,
                $currentPeriodEnd ?? $invoicePeriodEnd,
            ];
        }

        if ($this->isValidSubscriptionPeriod($invoicePeriodStart, $invoicePeriodEnd)) {
            return [
                $currentPeriodStart ?? $invoicePeriodStart,
                $currentPeriodEnd ?? $invoicePeriodEnd,
            ];
        }

        return [
            $currentPeriodStart ?? $linePeriodStart ?? $invoicePeriodStart,
            $currentPeriodEnd ?? $linePeriodEnd ?? $invoicePeriodEnd,
        ];
    }

    /**
     * @param array<string, mixed> $latestInvoice
     *
     * @return array{0: ?\DateTimeImmutable, 1: ?\DateTimeImmutable}
     */
    private function extractInvoiceLinePeriodBounds(array $latestInvoice): array
    {
        $firstLine = $latestInvoice['lines']['data'][0] ?? null;
        if (!is_array($firstLine) || !is_array($firstLine['period'] ?? null)) {
            return [null, null];
        }

        return [
            $this->createDateTimeFromTimestamp($firstLine['period']['start'] ?? null),
            $this->createDateTimeFromTimestamp($firstLine['period']['end'] ?? null),
        ];
    }

    private function isValidSubscriptionPeriod(
        ?\DateTimeImmutable $periodStart,
        ?\DateTimeImmutable $periodEnd,
    ): bool {
        return $periodStart instanceof \DateTimeImmutable
            && $periodEnd instanceof \DateTimeImmutable
            && $periodEnd > $periodStart;
    }

    private function looksLikeImmediateInvoiceSnapshot(
        ?\DateTimeImmutable $invoiceCreatedAt,
        ?\DateTimeImmutable $periodStart,
        ?\DateTimeImmutable $periodEnd,
    ): bool {
        if (
            !$invoiceCreatedAt instanceof \DateTimeImmutable
            || !$periodStart instanceof \DateTimeImmutable
            || !$periodEnd instanceof \DateTimeImmutable
        ) {
            return false;
        }

        $createdTimestamp = $invoiceCreatedAt->getTimestamp();
        $startTimestamp = $periodStart->getTimestamp();
        $endTimestamp = $periodEnd->getTimestamp();

        return abs($startTimestamp - $createdTimestamp) <= 300
            && abs($endTimestamp - $createdTimestamp) <= 300;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function syncCheckoutSessionCompleted(array $payload): void
    {
        $stripeSubscriptionId = $this->stripeReferenceHelper->extractExpandableId($payload['subscription'] ?? null);
        if ('' === $stripeSubscriptionId) {
            return;
        }

        $subscription = $this->stripeApiClient->retrieveSubscription($stripeSubscriptionId);
        $this->syncSubscriptionFromStripePayload($subscription);

        $latestInvoice = $subscription['latest_invoice'] ?? null;
        if (\is_array($latestInvoice)) {
            $eventType = match ((string) ($latestInvoice['status'] ?? 'draft')) {
                'paid' => 'invoice.paid',
                'open' => 'invoice.finalized',
                default => 'invoice.created',
            };

            $this->syncInvoiceFromStripePayload($eventType, $latestInvoice);
        }
    }

    private function applyCreatedSubscriptionCredits(
        PrestataireSubscription $subscription,
        SubscriptionInvoice $invoice,
        int $planCredits,
    ): void {
        $sourceSubscription = $this->findUpgradeSourceSubscription($subscription);

        $subscription
            ->setCreditsGrantedCurrentPeriod(0)
            ->setCreditsConsumedCurrentPeriod(0)
            ->setUpdatedAt(new \DateTimeImmutable());

        $baseGrantType = $sourceSubscription instanceof PrestataireSubscription
            ? SubscriptionCreditMovementTypeEnum::UPGRADE_GRANT
            : SubscriptionCreditMovementTypeEnum::RENEWAL_GRANT;
        $baseGrantDescription = $sourceSubscription instanceof PrestataireSubscription
            ? 'Attribution des crédits du nouveau plan après montée en gamme.'
            : 'Attribution automatique des crédits à la validation du paiement Stripe.';

        $baseMovement = $this->subscriptionCreditManager->grantCredits(
            $subscription,
            $planCredits,
            $baseGrantType,
            $baseGrantDescription,
            [
                'billing_reason' => 'subscription_create',
                'included_plan_credits' => $planCredits,
            ]
        );
        $baseMovement->setInvoice($invoice);

        if (!$sourceSubscription instanceof PrestataireSubscription) {
            return;
        }

        $sourceRemainingCredits = $sourceSubscription->getRemainingCredits();
        $targetRemainingCredits = $subscription->getRemainingCredits();
        $cappedRemainingCredits = $this->subscriptionUpgradePolicy->calculateCappedRemainingCredits(
            $sourceRemainingCredits,
            $planCredits
        );
        $transferableCredits = max(0, $cappedRemainingCredits - $targetRemainingCredits);

        if ($transferableCredits > 0) {
            $this->subscriptionCreditManager->debitCredits(
                $sourceSubscription,
                $transferableCredits,
                SubscriptionCreditMovementTypeEnum::CORRECTION,
                'Transfert des crédits restants vers la formule supérieure.',
                [
                    'target_subscription_id' => $subscription->getId(),
                    'reason' => 'upgrade_transfer_out',
                ]
            );

            $this->subscriptionCreditManager->grantCredits(
                $subscription,
                $transferableCredits,
                SubscriptionCreditMovementTypeEnum::UPGRADE_GRANT,
                'Report plafonné des crédits restants lors de la montée en gamme.',
                [
                    'source_subscription_id' => $sourceSubscription->getId(),
                    'billing_reason' => 'subscription_create',
                    'included_plan_credits' => $planCredits,
                    'remaining_before_transfer' => $sourceRemainingCredits,
                    'remaining_after_transfer' => $subscription->getRemainingCredits(),
                ]
            );
        }

        $this->closeSupersededSubscription($sourceSubscription);
    }

    private function applyFallbackPaidInvoiceCredits(
        PrestataireSubscription $subscription,
        SubscriptionInvoice $invoice,
        int $planCredits,
        string $billingReason,
    ): void {
        $sourceSubscription = $this->findUpgradeSourceSubscription($subscription);

        if (
            $sourceSubscription instanceof PrestataireSubscription
            && $this->isInitialPaidGrantState($subscription, $planCredits)
        ) {
            $this->applyCreatedSubscriptionCredits($subscription, $invoice, $planCredits);

            return;
        }

        $currentRemainingCredits = $subscription->getRemainingCredits();
        $targetRemainingCredits = $this->subscriptionUpgradePolicy->calculateCappedRemainingCredits(
            $currentRemainingCredits,
            $planCredits
        );
        $delta = max(0, $targetRemainingCredits - $currentRemainingCredits);

        if ($delta <= 0) {
            return;
        }

        $movement = $this->subscriptionCreditManager->grantCredits(
            $subscription,
            $delta,
            SubscriptionCreditMovementTypeEnum::UPGRADE_GRANT,
            'Attribution complémentaire des crédits après synchronisation d’un paiement Stripe confirmé.',
            [
                'billing_reason' => $billingReason,
                'fallback_applied' => true,
                'included_plan_credits' => $planCredits,
                'remaining_before_grant' => $currentRemainingCredits,
                'remaining_after_grant' => $targetRemainingCredits,
            ]
        );
        $movement->setInvoice($invoice);
    }

    private function findUpgradeSourceSubscription(PrestataireSubscription $targetSubscription): ?PrestataireSubscription
    {
        $prestataireProfile = $targetSubscription->getPrestataireProfile();
        if (!$prestataireProfile instanceof PrestataireProfile) {
            return null;
        }

        $targetStripeSubscriptionId = trim((string) ($targetSubscription->getStripeSubscriptionId() ?? ''));

        foreach ($this->prestataireSubscriptionRepository->findUsableForPrestataire($prestataireProfile) as $candidate) {
            if ($candidate === $targetSubscription) {
                continue;
            }

            $candidateStripeSubscriptionId = trim((string) ($candidate->getStripeSubscriptionId() ?? ''));
            if ('' !== $targetStripeSubscriptionId && $candidateStripeSubscriptionId === $targetStripeSubscriptionId) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    private function closeSupersededSubscription(PrestataireSubscription $subscription): void
    {
        $now = new \DateTimeImmutable();

        $subscription
            ->setStatus(SubscriptionStatusEnum::CANCELED)
            ->setCancelAtPeriodEnd(false)
            ->setCancellationRequestedAt($subscription->getCancellationRequestedAt() ?? $now)
            ->setCanceledAt($subscription->getCanceledAt() ?? $now)
            ->setEndedAt($subscription->getEndedAt() ?? $now)
            ->setUpdatedAt($now);

        $this->entityManager->persist($subscription);
    }

    private function isInitialPaidGrantState(PrestataireSubscription $subscription, int $planCredits): bool
    {
        return $subscription->getCreditsGrantedCurrentPeriod() === $planCredits
            && 0 === $subscription->getCreditsConsumedCurrentPeriod();
    }

    private function applySubscriptionCycleCreditSnapshot(
        PrestataireSubscription $subscription,
        mixed $previousPlan,
        SubscriptionBillingPeriodEnum $previousBillingPeriod,
        ?\DateTimeImmutable $previousCurrentPeriodStart,
        int $previousRemainingCredits,
    ): void {
        $currentPlan = $subscription->getPlan();
        if (
            !$currentPlan instanceof \App\Entity\Subscription\SubscriptionPlan
            || !$subscription->getStatus()->isUsable()
        ) {
            return;
        }

        $currentPeriodStart = $subscription->getCurrentPeriodStart();
        $hasNewCycle = $previousCurrentPeriodStart instanceof \DateTimeImmutable
            && $currentPeriodStart instanceof \DateTimeImmutable
            && $currentPeriodStart > $previousCurrentPeriodStart;
        $hasPlanChanged = $previousPlan instanceof \App\Entity\Subscription\SubscriptionPlan
            && $previousPlan->getId() !== $currentPlan->getId();
        $hasBillingPeriodChanged = $previousBillingPeriod !== $subscription->getBillingPeriod();

        if (!$hasNewCycle && !$hasPlanChanged && !$hasBillingPeriodChanged) {
            return;
        }

        $planCredits = $currentPlan->getCreditsForPeriod($subscription->getBillingPeriod());
        $targetRemainingCredits = $this->subscriptionUpgradePolicy->calculateCappedRemainingCredits(
            $previousRemainingCredits,
            $planCredits
        );

        if ($targetRemainingCredits <= $subscription->getRemainingCredits()) {
            return;
        }

        $subscription
            ->setCreditsGrantedCurrentPeriod($targetRemainingCredits)
            ->setCreditsConsumedCurrentPeriod(0)
            ->setUpdatedAt(new \DateTimeImmutable());
    }

}
