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

namespace App\Quote\DataFixtures;

use App\Account\DataFixtures\ClientProfileFixtures;
use App\Account\DataFixtures\UserFixtures;
use App\Account\Entity\ClientProfile;
use App\Core\DataFixtures\BaseFixture;
use App\Prestataire\DataFixtures\PrestataireServiceFixtures;
use App\Prestataire\Entity\PrestataireProfile;
use App\Prestataire\Entity\PrestataireService;
use App\Quote\Entity\QuoteRequest;
use App\Quote\Enum\QuoteRequestStatusEnum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class QuoteRequestFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $statuses = QuoteRequestStatusEnum::cases();

        for ($i = 1; $i <= 24; ++$i) {
            /** @var ClientProfile $client */
            $client = $this->getReference(\sprintf('client_profile_%d', (($i - 1) % UserFixtures::CLIENT_COUNT) + 1), ClientProfile::class);
            /** @var PrestataireProfile $prestataire */
            $prestataire = $this->getReference(\sprintf('prestataire_profile_%d', (($i - 1) % UserFixtures::PRESTATAIRE_COUNT) + 1), PrestataireProfile::class);
            /** @var PrestataireService $prestation */
            $prestation = $this->getReference(\sprintf('prestataire_service_%d', (($i - 1) % 42) + 1), PrestataireService::class);
            $title = \sprintf('%s pour %s', $prestation->getTitle(), $client->getDefaultCity() ?? 'mon domicile');

            $request = (new QuoteRequest())
                ->setClient($client)
                ->setPrestataire($prestataire)
                ->setPrestation($prestation)
                ->setTitle($title)
                ->setSlug($this->slugify($title.'-'.$i))
                ->setDescription($this->faker->paragraphs(2, true))
                ->setBudgetAmount($this->decimal(80, 2500))
                ->setDesiredDate($this->faker->dateTimeBetween('+5 days', '+3 months'))
                ->setStatus($statuses[($i - 1) % \count($statuses)])
                ->setCreatedAt($this->randomDateTimeImmutable('-6 months', '-5 days'))
                ->setUpdatedAt($this->randomDateTimeImmutable('-15 days'));

            $manager->persist($request);
            $this->addReference(\sprintf('quote_request_%d', $i), $request);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ClientProfileFixtures::class, PrestataireServiceFixtures::class];
    }
}
