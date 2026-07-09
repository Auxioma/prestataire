<?php

namespace App\DataFixtures;

use App\Entity\ClientProfile;
use App\Entity\Conversation;
use App\Entity\PrestataireProfile;
use App\Entity\QuoteRequest;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ConversationFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 18; ++$i) {
            /** @var QuoteRequest $quoteRequest */
            $quoteRequest = $this->getReference(sprintf('quote_request_%d', $i), QuoteRequest::class);
            /** @var ClientProfile $client */
            $client = $quoteRequest->getClient();
            /** @var PrestataireProfile $prestataire */
            $prestataire = $quoteRequest->getPrestataire();

            $conversation = (new Conversation())
                ->setQuoteRequest($quoteRequest)
                ->setClient($client)
                ->setPrestataire($prestataire)
                ->setCreatedAt($this->randomDateTimeImmutable('-5 months', '-5 days'))
                ->setUpdatedAt($this->randomDateTimeImmutable('-10 days'))
                ->setLastMessageAt($this->randomDateTimeImmutable('-8 days'))
                ->setIsClosed($i % 6 === 0);

            $manager->persist($conversation);
            $this->addReference(sprintf('conversation_%d', $i), $conversation);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [QuoteRequestFixtures::class];
    }
}
