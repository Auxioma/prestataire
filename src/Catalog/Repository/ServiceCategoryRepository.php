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

namespace App\Catalog\Repository;

use App\Catalog\Entity\ServiceCategory;
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

    public function findTopLevelWithActiveSubCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect('sub')
            ->leftJoin('c.subCategories', 'sub', 'WITH', 'sub.isActive = :active')
            ->where('c.parent IS NULL')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('sub.position', 'ASC')
            ->addOrderBy('sub.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Charge en une requête les catégories, sous-catégories et services publiés du sitemap.
     *
     * @return list<ServiceCategory>
     */
    public function findIndexableTreeForSitemap(): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect('sub', 'service')
            ->leftJoin('c.subCategories', 'sub', 'WITH', 'sub.isActive = :active')
            ->leftJoin('sub.services', 'service', 'WITH', 'service.isActive = :active')
            ->where('c.parent IS NULL')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('sub.position', 'ASC')
            ->addOrderBy('service.position', 'ASC')
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

    //    /**
    //     * @return ServiceCategory[] Returns an array of ServiceCategory objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ServiceCategory
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
