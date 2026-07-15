<?php

namespace App\Service\Subscription;

use App\Entity\Subscription\SubscriptionPlan;
use App\Entity\Subscription\SubscriptionPlanPrice;
use Doctrine\ORM\EntityManagerInterface;

class StripeCatalogManager
{
    public function __construct(
        private readonly StripeApiClient $stripeApiClient,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{created: bool, product_id: ?string, price_id: ?string, reason: ?string}
     */
    public function syncPlanPrice(SubscriptionPlanPrice $planPrice): array
    {
        $plan = $planPrice->getPlan();
        if (!$plan instanceof SubscriptionPlan) {
            return ['created' => false, 'product_id' => null, 'price_id' => null, 'reason' => 'Aucun plan associé.'];
        }

        if (!$planPrice->isPaid()) {
            return [
                'created' => false,
                'product_id' => $plan->getStripeProductId(),
                'price_id' => $planPrice->getStripePriceId(),
                'reason' => 'Tarif gratuit, aucune création Stripe nécessaire.',
            ];
        }

        $productId = $this->resolveProductId($plan);
        $existingPriceId = $planPrice->getStripePriceId();
        $mustCreateNewPrice = true;

        if (null !== $existingPriceId && '' !== $existingPriceId) {
            try {
                $stripePrice = $this->stripeApiClient->retrievePrice($existingPriceId);
                $mustCreateNewPrice = !$this->matchesStripePrice($planPrice, $productId, $stripePrice);
            } catch (\Throwable) {
                $mustCreateNewPrice = true;
            }
        }

        $created = false;
        if ($mustCreateNewPrice) {
            $stripePrice = $this->stripeApiClient->createPrice($plan, $planPrice, $productId);
            $priceId = trim((string) ($stripePrice['id'] ?? ''));

            if ('' === $priceId) {
                throw new \RuntimeException('Stripe n’a pas retourné de Price ID.');
            }

            $planPrice->setStripePriceId($priceId);
            $created = true;
        }

        $planPrice->setUpdatedAt(new \DateTimeImmutable());
        $plan->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($plan);
        $this->entityManager->persist($planPrice);
        $this->entityManager->flush();

        return [
            'created' => $created,
            'product_id' => $productId,
            'price_id' => $planPrice->getStripePriceId(),
            'reason' => null,
        ];
    }

    private function resolveProductId(SubscriptionPlan $plan): string
    {
        $productId = trim((string) ($plan->getStripeProductId() ?? ''));

        if ('' !== $productId) {
            return $productId;
        }

        $stripeProduct = $this->stripeApiClient->createProduct($plan);
        $productId = trim((string) ($stripeProduct['id'] ?? ''));

        if ('' === $productId) {
            throw new \RuntimeException('Stripe n’a pas retourné de Product ID.');
        }

        $plan->setStripeProductId($productId);

        return $productId;
    }

    /**
     * @param array<string, mixed> $stripePrice
     */
    private function matchesStripePrice(SubscriptionPlanPrice $planPrice, string $productId, array $stripePrice): bool
    {
        $stripeProductId = trim((string) ($stripePrice['product'] ?? ''));
        $unitAmount = (string) ($stripePrice['unit_amount_decimal'] ?? '');
        $recurringInterval = (string) ($stripePrice['recurring']['interval'] ?? '');
        $isActive = (bool) ($stripePrice['active'] ?? false);

        $expectedUnitAmount = number_format((float) $planPrice->getAmount() * 100, 0, '.', '');
        $expectedInterval = match ($planPrice->getBillingPeriod()->value) {
            'monthly' => 'month',
            'annual' => 'year',
        };

        return $stripeProductId === $productId
            && $unitAmount === $expectedUnitAmount
            && $recurringInterval === $expectedInterval
            && $isActive;
    }
}
