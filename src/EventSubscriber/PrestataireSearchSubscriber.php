<?php

/**
 * Copyright(c) 2026 Trouve moi
 *
 * Ce fichier fait partie d’un projet développé par Auxioma Web Agency.
 * Tous droits réservés.
 *
 * Ce code source est la propriété exclusive de Auxioma Web Agency.
 * Toute reproduction, modification, distribution ou utilisation sans autorisation préalable est interdite.
 */

namespace App\EventSubscriber;

use App\Entity\PrestataireInterventionZone;
use App\Entity\PrestataireProfile;
use App\Entity\PrestataireService;
use App\Entity\Service;
use App\Entity\ServiceCategory;
use App\Entity\User;
use App\Enum\VerificationStatusEnum;
use App\Service\PrestataireSearchQueue;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
#[AsDoctrineListener(event: Events::postFlush)]
final class PrestataireSearchSubscriber
{
    private array $pendingIds = [];
    private array $removedIds = [];
    private array $catalogueIds = [];

    public function __construct(
        private readonly PrestataireSearchQueue $queue,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $manager = $args->getObjectManager();
        $uow = $manager->getUnitOfWork();
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof PrestataireProfile) {
                continue;
            }
            $changes = $uow->getEntityChangeSet($entity);
            if (isset($changes['siret']) && !isset($changes['verificationStatus']) && !isset($changes['verifiedAt'])) {
                // A previous company's verification cannot certify a replacement SIRET.
                $entity->setVerificationStatus(VerificationStatusEnum::NOT_VERIFIED);
                $entity->setVerifiedAt(null);
                $uow->recomputeSingleEntityChangeSet($manager->getClassMetadata(PrestataireProfile::class), $entity);
            }
        }
        // Doctrine clears a removed entity's generated ID before postRemove.
        $this->removedIds = [];
        $this->catalogueIds = [];
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof PrestataireProfile && null !== $entity->getId()) {
                $this->removedIds[spl_object_id($entity)] = $entity->getId();
            }
            if ($entity instanceof Service || $entity instanceof ServiceCategory) {
                $this->catalogueIds[spl_object_id($entity)] = $this->findCatalogueProfileIds($entity, $manager);
            }
        }
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->schedule($args->getObject(), $args->getObjectManager());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        $manager = $args->getObjectManager();
        // Reassignment must also refresh the previous owner's document.
        foreach (['prestataire', 'prestataireProfile', 'account'] as $field) {
            $old = $manager->getUnitOfWork()->getEntityChangeSet($entity)[$field][0] ?? null;
            if ($old instanceof PrestataireProfile && null !== $old->getId()) {
                $this->enqueue($old->getId());
            }
        }
        $this->schedule($entity, $manager);
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        if (isset($this->removedIds[spl_object_id($entity)])) {
            $this->enqueue($this->removedIds[spl_object_id($entity)]);
        } elseif (isset($this->catalogueIds[spl_object_id($entity)])) {
            foreach ($this->catalogueIds[spl_object_id($entity)] as $id) {
                $this->enqueue((string) $id);
            }
        } else {
            $this->schedule($entity, $args->getObjectManager());
        }
    }

    private function schedule(object $entity, \Doctrine\ORM\EntityManagerInterface $manager): void
    {
        $profile = match (true) {
            $entity instanceof PrestataireProfile => $entity,
            $entity instanceof PrestataireService => $entity->getPrestataire(),
            $entity instanceof PrestataireInterventionZone => $entity->getPrestataireProfile(),
            $entity instanceof User => $entity->getPrestataireProfile(),
            default => null,
        };
        if (null !== $profile?->getId()) {
            $this->enqueue($profile->getId());
        }
        if ($entity instanceof Service || $entity instanceof ServiceCategory) {
            foreach ($this->findCatalogueProfileIds($entity, $manager) as $id) {
                $this->enqueue((string) $id);
            }
        }
    }

    private function findCatalogueProfileIds(Service|ServiceCategory $entity, \Doctrine\ORM\EntityManagerInterface $manager): array
    {
        $qb = $manager->createQueryBuilder()->select('DISTINCT p.id')->from(PrestataireProfile::class, 'p')
                ->join('p.prestataireServices', 'ps')->join('ps.service', 's');
        if ($entity instanceof Service) {
            $qb->where('s = :entity');
        } else {
            $qb->join('s.category', 'c')->where('c = :entity OR c.parent = :entity');
        }

        return $qb->setParameter('entity', $entity)->getQuery()->getSingleColumnResult();
    }

    private function enqueue(string $id): void
    {
        // postPersist/postUpdate/postRemove run inside the same DB transaction as the change.
        $this->queue->enqueue($id);
        $this->pendingIds[$id] = $id;
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $ids = array_values($this->pendingIds);
        $this->pendingIds = [];
        $this->removedIds = [];
        $this->catalogueIds = [];
        // Large batches are handled by the worker to keep admin imports responsive.
        try {
            $this->queue->process(min(10, \count($ids)), $ids);
        } catch (\Throwable $exception) {
            // The business transaction has committed. Keep the durable job for the worker.
            $this->logger->error('Traitement immédiat Elasticsearch interrompu ; tâches conservées.', ['error_type' => $exception::class]);
        }
    }
}
