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

use App\Entity\PrestataireProfile;
use App\Entity\Service;
use App\Enum\PrestataireProfileStatusEnum;
// use App\Enum\SearchVisibilityEnum;
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
     * avec un lien PrestataireService actif.
     */
    public function findByService(Service $service): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.prestataireServices', 'ps')
            ->innerJoin('ps.service', 's')
            ->leftJoin('p.account', 'a')
            ->andWhere('p.profileStatus = :status')
            ->andWhere('ps.isActive = :isActive')
            ->andWhere('s = :service')
            ->setParameter('status', PrestataireProfileStatusEnum::ACTIVE)
            ->setParameter('isActive', true)
            ->setParameter('service', $service)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le QueryBuilder de recherche globale (le tri est géré par le Paginator).
     */
    public function getBrowseQueryBuilder(string $sortBy): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.account', 'a')
            ->addSelect('a')
            ->andWhere('p.profileStatus = :status')
            ->setParameter('status', PrestataireProfileStatusEnum::ACTIVE);
    }

    /**
     * Retourne le QueryBuilder de la barre de recherche d'accueil (le tri est géré par le Paginator).
     */
    public function getHomepageSearchQueryBuilder(array $criteria): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.account', 'a')->addSelect('a')
            ->innerJoin('p.prestataireServices', 'ps')
            ->innerJoin('ps.service', 's')
            ->leftJoin('s.category', 'c')
            ->leftJoin('c.parent', 'parent')
            ->leftJoin('p.prestataireInterventionZones', 'z')->addSelect('z')
            ->andWhere('p.profileStatus = :status')
            ->andWhere('ps.isActive = :psActive')
            ->andWhere('s.isActive = :serviceActive')
            ->setParameter('status', PrestataireProfileStatusEnum::ACTIVE)
            ->setParameter('psActive', true)
            ->setParameter('serviceActive', true)
            ->distinct();

        $query = mb_trim((string) ($criteria['query'] ?? ''));
        $location = mb_trim((string) ($criteria['location'] ?? ''));
        $subCategory = $criteria['subCategory'] ?? null;
        $searchedLocation = $criteria['searchedLocation'] ?? null;

        if ('' !== $query) {
            $qb
                ->andWhere('
                LOWER(p.companyName) LIKE LOWER(:query)
                OR LOWER(p.metier) LIKE LOWER(:query)
                OR LOWER(p.shortDescription) LIKE LOWER(:query)
                OR LOWER(s.name) LIKE LOWER(:query)
            ')
                ->setParameter('query', '%' . $query . '%');
        }

        if ('' !== $location && null === $searchedLocation) {
            $qb
                ->andWhere('
                LOWER(p.city) LIKE LOWER(:location)
                OR p.postalCode LIKE :locationExact
                OR LOWER(z.city) LIKE LOWER(:location)
                OR z.postalCode LIKE :locationExact
            ')
                ->setParameter('location', '%' . $location . '%')
                ->setParameter('locationExact', '%' . $location . '%');
        }

        if (null !== $subCategory) {
            $qb
                ->andWhere('c = :subCategory OR parent = :subCategory')
                ->setParameter('subCategory', $subCategory);
        }

        return $qb;
    }
}
