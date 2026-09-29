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

namespace App\Prestataire\DataFixtures;

use App\Core\DataFixtures\BaseFixture;
use App\Prestataire\Entity\PrestataireService;
use App\Prestataire\Entity\PrestationMedia;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PrestationMediaFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 24; ++$i) {
            /** @var PrestataireService $prestation */
            $prestation = $this->getReference(\sprintf('prestataire_service_%d', (($i - 1) % 42) + 1), PrestataireService::class);

            $media = (new PrestationMedia())
                ->setPrestation($prestation)
                ->setPosition(($i - 1) % 3)
                ->setTitle(\sprintf('Visuel %d - %s', $i, $prestation->getTitle()))
                ->setAltText(\sprintf('Illustration de la prestation %s', $prestation->getTitle()))
                ->setCreatedAt($this->randomDateTimeImmutable('-6 months', '-10 days'));

            $this->attachRemoteImage(
                $media,
                'setImageFile',
                \sprintf('https://picsum.photos/1200/800?random=prestation-%d', $i)
            );
            $manager->persist($media);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [PrestataireServiceFixtures::class];
    }
}
