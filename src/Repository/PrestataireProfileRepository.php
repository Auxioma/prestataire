<?php

namespace App\Repository;

use App\Entity\PrestataireProfile;
use App\Entity\Service;
use App\Enum\PrestataireProfileStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class PrestataireProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrestataireProfile::class);
    }

    /**
     * Récupère tous les prestataires actifs qui proposent un service spécifique
     */
    public function findByService(Service $service): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.prestataireServices', 'ps')
            ->join('ps.service', 's')
            ->andWhere('s.id = :serviceId')
            ->setParameter('serviceId', $service->getId())
            ->andWhere('p.profileStatus = :status')
            ->setParameter('status', PrestataireProfileStatusEnum::ACTIVE)
            ->leftJoin('p.account', 'a')
            ->addSelect('a')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le QueryBuilder de recherche globale (le tri est géré par le Paginator)
     */
    public function getBrowseQueryBuilder(string $sortBy): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.account', 'a')
            ->addSelect('a')
            ->andWhere('p.profileStatus = :status')
            ->setParameter('status', PrestataireProfileStatusEnum::ACTIVE);
    }

    public function findWithActivePromotions(int $limit = 4): array
{
    return $this->createQueryBuilder('p')
        ->innerJoin('p.prestataireServices', 'ps')
        ->where('ps.tauxReduction > 0')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}
}
