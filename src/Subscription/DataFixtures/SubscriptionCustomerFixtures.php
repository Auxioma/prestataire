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

namespace App\Subscription\DataFixtures;

use App\Account\DataFixtures\UserFixtures;
use App\Core\DataFixtures\BaseFixture;
use App\Prestataire\DataFixtures\PrestataireProfileFixtures;
use App\Prestataire\Entity\PrestataireProfile;
use App\Subscription\Entity\SubscriptionCustomer;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SubscriptionCustomerFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= UserFixtures::PRESTATAIRE_COUNT; ++$i) {
            /** @var PrestataireProfile $prestataire */
            $prestataire = $this->getReference(\sprintf('prestataire_profile_%d', $i), PrestataireProfile::class);

            $customer = (new SubscriptionCustomer())
                ->setPrestataireProfile($prestataire)
                ->setStripeCustomerId(\sprintf('cus_demo_%04d', $i))
                ->setStripeDefaultPaymentMethodId(\sprintf('pm_demo_%04d', $i))
                ->setDefaultPaymentMethodType('card')
                ->setBillingEmail($prestataire->getAccount()?->getEmail())
                ->setCreatedAt($this->randomDateTimeImmutable('-10 months', '-2 months'))
                ->setUpdatedAt($this->randomDateTimeImmutable('-20 days'));

            $manager->persist($customer);
            $this->addReference(\sprintf('subscription_customer_%d', $i), $customer);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [PrestataireProfileFixtures::class];
    }
}
