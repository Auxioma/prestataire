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

use App\Core\DataFixtures\BaseFixture;
use App\Messaging\Entity\Conversation;
use App\Messaging\Entity\Message;
use App\Messaging\Enum\MessageTypeEnum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MessageFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $messageIndex = 1;

        for ($conversationIndex = 1; $conversationIndex <= 18; ++$conversationIndex) {
            /** @var Conversation $conversation */
            $conversation = $this->getReference(\sprintf('conversation_%d', $conversationIndex), Conversation::class);
            $clientUser = $conversation->getClient()?->getAccount();
            $prestataireUser = $conversation->getPrestataire()?->getAccount();

            for ($offset = 0; $offset < 4; ++$offset) {
                $isSystem = 0 === $offset;
                $message = (new Message())
                    ->setConversation($conversation)
                    ->setAuthor($isSystem ? null : (0 === $offset % 2 ? $prestataireUser : $clientUser))
                    ->setType($isSystem ? MessageTypeEnum::SYSTEM : MessageTypeEnum::USER)
                    ->setContent($isSystem ? 'La conversation a été ouverte après acceptation de la demande.' : $this->faker->sentence(16))
                    ->setCreatedAt($this->randomDateTimeImmutable('-3 months', '-2 days'))
                    ->setReadAt($isSystem ? null : $this->randomDateTimeImmutable('-10 days', 'now'));

                $conversation->markLastMessageAt($message->getCreatedAt());
                $manager->persist($message);
                $this->addReference(\sprintf('message_%d', $messageIndex++), $message);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ConversationFixtures::class];
    }
}
