<?php

namespace App\DataFixtures;

use App\Entity\PrestataireProfile;
use App\Entity\Subscription\SubscriptionCustomer;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SubscriptionCustomerFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= UserFixtures::PRESTATAIRE_COUNT; ++$i) {
            /** @var PrestataireProfile $prestataire */
            $prestataire = $this->getReference(sprintf('prestataire_profile_%d', $i), PrestataireProfile::class);

            $customer = (new SubscriptionCustomer())
                ->setPrestataireProfile($prestataire)
                ->setStripeCustomerId(sprintf('cus_demo_%04d', $i))
                ->setStripeDefaultPaymentMethodId(sprintf('pm_demo_%04d', $i))
                ->setDefaultPaymentMethodType('card')
                ->setBillingEmail($prestataire->getAccount()?->getEmail())
                ->setCreatedAt($this->randomDateTimeImmutable('-10 months', '-2 months'))
                ->setUpdatedAt($this->randomDateTimeImmutable('-20 days'));

            $manager->persist($customer);
            $this->addReference(sprintf('subscription_customer_%d', $i), $customer);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [PrestataireProfileFixtures::class];
    }
}
