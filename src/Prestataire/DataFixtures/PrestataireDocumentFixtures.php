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

use App\Account\DataFixtures\UserFixtures;
use App\Core\DataFixtures\BaseFixture;
use App\Prestataire\Entity\PrestataireDocument;
use App\Prestataire\Entity\PrestataireProfile;
use App\Prestataire\Enum\PrestataireDocumentStatusEnum;
use App\Prestataire\Enum\PrestataireDocumentTypeEnum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PrestataireDocumentFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $types = PrestataireDocumentTypeEnum::cases();
        $statuses = PrestataireDocumentStatusEnum::cases();

        for ($i = 1; $i <= 20; ++$i) {
            /** @var PrestataireProfile $prestataire */
            $prestataire = $this->getReference(\sprintf('prestataire_profile_%d', (($i - 1) % UserFixtures::PRESTATAIRE_COUNT) + 1), PrestataireProfile::class);
            $type = $types[($i - 1) % \count($types)];

            $document = new PrestataireDocument()
                ->setPrestataireProfile($prestataire)
                ->setType($type)
                ->setStatus($statuses[($i - 1) % \count($statuses)])
                ->setIsVisibleToClient(0 === $i % 2)
                ->setIssuedAt($this->faker->dateTimeBetween('-18 months', '-2 months'))
                ->setExpiresAt($this->faker->dateTimeBetween('+1 month', '+18 months'))
                ->setNotes(\sprintf('Document %s transmis dans le cadre de la validation du profil.', $type->value))
                ->setCreatedAt($this->randomDateTimeImmutable('-10 months', '-2 months'))
                ->setUpdatedAt($this->randomDateTimeImmutable('-20 days'));

            $this->attachRemoteImage(
                $document,
                'setDocumentFile',
                \sprintf('https://picsum.photos/800/1100?random=document-%d', $i)
            );

            $manager->persist($document);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [PrestataireProfileFixtures::class];
    }
}
