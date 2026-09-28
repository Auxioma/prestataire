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

namespace App\Messaging\DataFixtures;

use App\Account\Entity\ClientProfile;
use App\Core\DataFixtures\BaseFixture;
use App\Messaging\Entity\Conversation;
use App\Prestataire\Entity\PrestataireProfile;
use App\Quote\DataFixtures\QuoteRequestFixtures;
use App\Quote\Entity\QuoteRequest;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ConversationFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 18; ++$i) {
            /** @var QuoteRequest $quoteRequest */
            $quoteRequest = $this->getReference(\sprintf('quote_request_%d', $i), QuoteRequest::class);
            /** @var ClientProfile $client */
            $client = $quoteRequest->getClient();
            /** @var PrestataireProfile $prestataire */
            $prestataire = $quoteRequest->getPrestataire();

            $conversation = new Conversation()
                ->setQuoteRequest($quoteRequest)
                ->setClient($client)
                ->setPrestataire($prestataire)
                ->setCreatedAt($this->randomDateTimeImmutable('-5 months', '-5 days'))
                ->setUpdatedAt($this->randomDateTimeImmutable('-10 days'))
                ->setLastMessageAt($this->randomDateTimeImmutable('-8 days'))
                ->setIsClosed(0 === $i % 6);

            $manager->persist($conversation);
            $this->addReference(\sprintf('conversation_%d', $i), $conversation);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [QuoteRequestFixtures::class];
    }
}
