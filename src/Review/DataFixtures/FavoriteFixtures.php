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

namespace App\Review\DataFixtures;

use App\Account\DataFixtures\UserFixtures;
use App\Account\Entity\User;
use App\Core\DataFixtures\BaseFixture;
use App\Prestataire\DataFixtures\PrestataireProfileFixtures;
use App\Prestataire\DataFixtures\PrestataireServiceFixtures;
use App\Prestataire\Entity\PrestataireProfile;
use App\Prestataire\Entity\PrestataireService;
use App\Review\Entity\Favorite;
use App\Review\Enum\FavoriteTypeEnum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class FavoriteFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 30; ++$i) {
            /** @var User $user */
            $user = $this->getReference(\sprintf('user_client_%d', (($i - 1) % UserFixtures::CLIENT_COUNT) + 1), User::class);
            $type = 0 === $i % 3 ? FavoriteTypeEnum::BON_PLAN : (0 === $i % 2 ? FavoriteTypeEnum::PRESTATION : FavoriteTypeEnum::PRESTATAIRE);

            $favorite = (new Favorite())
                ->setUser($user)
                ->setType($type)
                ->setCreatedAt($this->faker->dateTimeBetween('-5 months', '-1 day'));

            if (FavoriteTypeEnum::PRESTATAIRE === $type) {
                /** @var PrestataireProfile $prestataire */
                $prestataire = $this->getReference(\sprintf('prestataire_profile_%d', (($i - 1) % UserFixtures::PRESTATAIRE_COUNT) + 1), PrestataireProfile::class);
                $favorite->setTargetId($prestataire->getId());
            } else {
                /** @var PrestataireService $prestation */
                $prestation = $this->getReference(\sprintf('prestataire_service_%d', (($i - 1) % 42) + 1), PrestataireService::class);
                $favorite->setTargetId($prestation->getId());
            }

            $manager->persist($favorite);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class, PrestataireProfileFixtures::class, PrestataireServiceFixtures::class];
    }
}
