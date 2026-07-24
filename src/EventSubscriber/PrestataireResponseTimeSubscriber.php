<?php

namespace App\EventSubscriber;

use App\Entity\Message;
use App\Entity\PrestataireProfile;
use App\Enum\MessageTypeEnum;
use App\Service\PrestataireResponseTimeManager;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

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

        if (!$entity instanceof Message || $entity->getType() !== MessageTypeEnum::USER) {
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
