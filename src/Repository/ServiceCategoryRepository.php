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

namespace App\Repository;

use App\Entity\ServiceCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServiceCategory>
 */
class ServiceCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceCategory::class);
    }

    public function findWithSubCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect('sub')
            ->leftJoin('c.subCategories', 'sub')
            ->where('c.parent IS NULL')
            ->andWhere('c.isActive = :active')
            ->andWhere('sub.id IS NULL OR sub.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('sub.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNavbarCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent IS NULL')
            ->andWhere('c.isActive = true')
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Catégories principales affichées sur la home.
     *
     * @return ServiceCategory[]
     */
    public function findPopularForHome(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.services', 's', 'WITH', 's.isActive = :active')
            ->addSelect('COUNT(s.id) AS HIDDEN servicesCount')
            ->andWhere('c.parent IS NULL')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('c.id')
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('servicesCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countActiveRootCategories(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.parent IS NULL')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
