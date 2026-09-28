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

namespace App\Account\DataFixtures;

use App\Account\Entity\ClientProfile;
use App\Account\Entity\User;
use App\Account\Enum\ClientTypeEnum;
use App\Core\DataFixtures\BaseFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ClientProfileFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= UserFixtures::CLIENT_COUNT; ++$i) {
            /** @var User $user */
            $user = $this->getReference(\sprintf('user_client_%d', $i), User::class);
            $isPro = 0 === $i % 3;
            $city = $this->faker->randomElement(['Bordeaux', 'Mérignac', 'Pessac', 'Talence', 'Arcachon', 'Libourne']);

            $profile = new ClientProfile()
                ->setAccount($user)
                ->setType($isPro ? ClientTypeEnum::PROFESSIONNEL : ClientTypeEnum::PARTICULIER)
                ->setCreatedAt($this->randomDateTimeImmutable('-18 months', '-2 months'))
                ->setUpdatedAt($this->randomDateTimeImmutable('-30 days'))
                ->setDefaultAddress($this->faker->streetAddress())
                ->setDefaultPostalCode($this->faker->postcode())
                ->setDefaultCity($city)
                ->setLatitude($this->decimal(44.60, 44.95))
                ->setLongitude($this->decimal(-0.80, -0.35));

            if ($isPro) {
                $profile
                    ->setCompanyName(\sprintf('%s Conseil', $this->faker->company()))
                    ->setSiret($this->faker->numerify('#########000##'))
                    ->setBillingAddress($this->faker->streetAddress())
                    ->setBillingPostalCode($this->faker->postcode())
                    ->setBillingCity($city)
                    ->setBillingCountry('France');
            } else {
                $profile
                    ->setBillingAddress($profile->getDefaultAddress())
                    ->setBillingPostalCode($profile->getDefaultPostalCode())
                    ->setBillingCity($city)
                    ->setBillingCountry('France');
            }

            $manager->persist($profile);
            $this->addReference(\sprintf('client_profile_%d', $i), $profile);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
