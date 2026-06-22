<?php

namespace App\Repository;

use App\Entity\Favorite;
use App\Entity\User;
use App\Enum\FavoriteTypeEnum;
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
}