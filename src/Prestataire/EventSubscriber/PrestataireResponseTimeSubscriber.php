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

namespace App\Prestataire\EventSubscriber;

use App\Messaging\Entity\Message;
use App\Messaging\Enum\MessageTypeEnum;
use App\Prestataire\Entity\PrestataireProfile;
use App\Prestataire\Service\PrestataireResponseTimeManager;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postFlush)]
final class PrestataireResponseTimeSubscriber
{
    /**
     * @var array<string, PrestataireProfile>
     */
    private array $pendingPrestataires = [];

    private bool $isFlushing = false;

    public function __construct(
        private readonly PrestataireResponseTimeManager $prestataireResponseTimeManager,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Message || MessageTypeEnum::USER !== $entity->getType()) {
            return;
        }

        $author = $entity->getAuthor();
        $prestataireProfile = $author?->getPrestataireProfile();

        if (
            !$prestataireProfile instanceof PrestataireProfile
            || null === $prestataireProfile->getId()
            || $entity->getConversation()?->getPrestataire()?->getId() !== $prestataireProfile->getId()
        ) {
            return;
        }

        $this->pendingPrestataires[$prestataireProfile->getId()] = $prestataireProfile;
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ([] === $this->pendingPrestataires || $this->isFlushing) {
            return;
        }

        $this->isFlushing = true;

        try {
            foreach ($this->pendingPrestataires as $prestataireProfile) {
                $this->prestataireResponseTimeManager->refreshForPrestataire($prestataireProfile);
            }

            $this->pendingPrestataires = [];
            $args->getObjectManager()->flush();
        } finally {
            $this->isFlushing = false;
        }
    }
}
