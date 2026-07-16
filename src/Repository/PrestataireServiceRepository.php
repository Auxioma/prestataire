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

use App\Entity\PrestataireService;
use App\Enum\PrestataireProfileStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class PrestataireServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrestataireService::class);
    }

    private function createBaseBonsPlansQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('ps')
            ->innerJoin('ps.prestataire', 'p')
            ->addSelect('p')
            ->innerJoin('ps.service', 's')
            ->addSelect('s')
            ->leftJoin('p.account', 'a')
            ->addSelect('a')
            ->leftJoin('s.category', 'c')
            ->addSelect('c')
            ->leftJoin('c.parent', 'parent')
            ->addSelect('parent')
            ->andWhere('p.profileStatus = :status')
            ->andWhere('ps.isActive = :psActive')
            ->andWhere('ps.tauxReduction IS NOT NULL')
            ->andWhere('ps.tauxReduction > 0')
            ->setParameter('status', PrestataireProfileStatusEnum::ACTIVE)
            ->setParameter('psActive', true);
    }

    public function findLatestBonsPlansForHome(int $limit = 4): array
    {
        return $this->createBaseBonsPlansQueryBuilder()
            ->orderBy('ps.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getBonsPlansQueryBuilder(?string $categorySlug = null, ?string $subCategorySlug = null): QueryBuilder
    {
        $qb = $this->createBaseBonsPlansQueryBuilder()
            ->orderBy('ps.tauxReduction', 'DESC')
            ->addOrderBy('ps.id', 'DESC');

        if ($subCategorySlug) {
            $qb->andWhere('c.slug = :subCategorySlug')
                ->setParameter('subCategorySlug', $subCategorySlug);
        } elseif ($categorySlug) {
            $qb->andWhere('(parent.slug = :categorySlug OR c.slug = :categorySlug)')
                ->setParameter('categorySlug', $categorySlug);
        }

        return $qb;
    }

    public function isActivePrestationFavoriteable(int|string $id): bool
    {
        return null !== $this->createQueryBuilder('ps')
            ->select('ps.id')
            ->andWhere('ps.id = :id')
            ->andWhere('ps.isActive = :isActive')
            ->setParameter('id', $id)
            ->setParameter('isActive', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function isActiveBonPlanFavoriteable(int|string $id): bool
    {
        return null !== $this->createQueryBuilder('ps')
            ->select('ps.id')
            ->andWhere('ps.id = :id')
            ->andWhere('ps.isActive = :isActive')
            ->andWhere('ps.tauxReduction IS NOT NULL')
            ->andWhere('ps.tauxReduction > 0')
            ->setParameter('id', $id)
            ->setParameter('isActive', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
