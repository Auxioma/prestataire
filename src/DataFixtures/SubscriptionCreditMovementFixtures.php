<?php

namespace App\DataFixtures;

use App\Entity\PrestataireProfile;
use App\Entity\Subscription\PrestataireSubscription;
use App\Entity\Subscription\SubscriptionCreditMovement;
use App\Entity\Subscription\SubscriptionInvoice;
use App\Enum\SubscriptionCreditMovementTypeEnum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SubscriptionCreditMovementFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $balanceByProfile = [];

        for ($i = 1; $i <= 30; ++$i) {
            $profileIndex = (($i - 1) % UserFixtures::PRESTATAIRE_COUNT) + 1;
            /** @var PrestataireProfile $prestataire */
            $prestataire = $this->getReference(sprintf('prestataire_profile_%d', $profileIndex), PrestataireProfile::class);
            /** @var PrestataireSubscription $subscription */
            $subscription = $this->getReference(sprintf('prestataire_subscription_%d', $profileIndex), PrestataireSubscription::class);
            /** @var SubscriptionInvoice $invoice */
            $invoice = $this->getReference(sprintf('subscription_invoice_%d', (($i - 1) % 20) + 1), SubscriptionInvoice::class);

            $isCredit = $i % 3 !== 0;
            $delta = $isCredit ? $this->faker->numberBetween(4, 12) : -$this->faker->numberBetween(1, 3);
            $balanceByProfile[$profileIndex] = ($balanceByProfile[$profileIndex] ?? 0) + $delta;

            $movement = (new SubscriptionCreditMovement())
                ->setPrestataireProfile($prestataire)
                ->setSubscription($subscription)
                ->setInvoice($isCredit ? $invoice : null)
                ->setType($isCredit ? SubscriptionCreditMovementTypeEnum::RENEWAL_GRANT : SubscriptionCreditMovementTypeEnum::QUOTE_RESPONSE_CONSUMPTION)
                ->setCreditsDelta($delta)
                ->setBalanceAfter($balanceByProfile[$profileIndex])
                ->setDescription($isCredit ? 'Attribution de crédits à la suite du renouvellement d’abonnement.' : 'Consommation d’un crédit pour une réponse à un devis.')
                ->setMetadata(['fixture' => true, 'profile' => $profileIndex])
                ->setOccurredAt($this->randomDateTimeImmutable('-8 months', '-1 day'))
                ->setCreatedAt($this->randomDateTimeImmutable('-8 months', '-1 day'));

            $manager->persist($movement);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [SubscriptionInvoiceFixtures::class, PrestataireSubscriptionFixtures::class];
    }
}
