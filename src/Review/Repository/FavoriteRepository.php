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

namespace App\Review\Repository;

use App\Account\Entity\User;
use App\Review\Entity\Favorite;
use App\Review\Enum\FavoriteTypeEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favorite>
 */
class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    public function findOneByUserTypeAndTarget(User $user, FavoriteTypeEnum $type, string|int $targetId): ?Favorite
    {
        return $this->findOneBy([
            'user' => $user,
            'type' => $type,
            'targetId' => (string) $targetId,
        ]);
    }

    public function isFavorite(User $user, FavoriteTypeEnum $type, string|int $targetId): bool
    {
        return null !== $this->findOneByUserTypeAndTarget($user, $type, $targetId);
    }

    /**
     * @return Favorite[]
     */
    public function findByUserAndType(User $user, FavoriteTypeEnum $type): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->andWhere('f.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return list<string>
     */
    public function findTargetIdsByUserAndType(User $user, FavoriteTypeEnum $type): array
    {
        $rows = $this->createQueryBuilder('f')
            ->select('f.targetId')
            ->andWhere('f.user = :user')
            ->andWhere('f.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getScalarResult();

        return array_map(
            static fn (array $row): string => (string) ($row['targetId'] ?? ''),
            $rows
        );
    }
}
