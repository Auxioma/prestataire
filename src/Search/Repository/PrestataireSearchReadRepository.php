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

namespace App\Search\Repository;

use App\Prestataire\Entity\PrestataireProfile;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

final class PrestataireSearchReadRepository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function findFresh(string $id): ?PrestataireProfile
    {
        return $this->findFreshBatch([$id])[$id] ?? null;
    }

    /** @return array<string, PrestataireProfile> */
    public function findFreshBatch(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }
        // A separate identity map avoids indexing stale collections after removals.
        $reader = new EntityManager($this->entityManager->getConnection(), $this->entityManager->getConfiguration());
        $profiles = $reader->createQueryBuilder()
            ->select('p', 'a', 'ps', 's', 'c', 'parent')
            ->from(PrestataireProfile::class, 'p')
            ->leftJoin('p.account', 'a')
            ->leftJoin('p.prestataireServices', 'ps')
            ->leftJoin('ps.service', 's')
            ->leftJoin('s.category', 'c')
            ->leftJoin('c.parent', 'parent')
            ->where('p.id IN (:ids)')->setParameter('ids', $ids)
            ->getQuery()->getResult();

        if ([] !== $profiles) {
            $reader->createQueryBuilder()->select('p', 'z')
                ->from(PrestataireProfile::class, 'p')
                ->leftJoin('p.prestataireInterventionZones', 'z')
                ->where('p.id IN (:ids)')->setParameter('ids', $ids)
                ->getQuery()->getResult();
        }

        $byId = [];
        foreach ($profiles as $profile) {
            $byId[$profile->getId()] = $profile;
        }

        return $byId;
    }
}
