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

namespace App\Prestataire\Repository;

use App\Account\Enum\UserStatusEnum;
use App\Catalog\Entity\Service;
use App\Prestataire\Entity\PrestataireProfile;
use App\Prestataire\Enum\PrestataireProfileStatusEnum;
use App\Search\Enum\SearchVisibilityEnum;
use App\Search\Service\PrestataireSearchEligibility;
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
     * Retourne uniquement les profils pouvant être exposés aux moteurs de recherche.
     *
     * @return list<PrestataireProfile>
     */
    public function findIndexableForSitemap(): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.account', 'a')
            ->addSelect('a')
            ->andWhere('p.profileStatus = :activeProfileStatus')
            ->andWhere('p.verificationStatus IN (:verifiedStatuses)')
            ->andWhere('p.searchVisibility != :hiddenVisibility')
            ->andWhere('p.deletedAt IS NULL')
            ->andWhere('a.status NOT IN (:excludedAccountStatuses)')
            ->andWhere('a.deletedAt IS NULL')
            ->andWhere('p.companyName IS NOT NULL')
            ->andWhere("TRIM(p.companyName) != ''")
            ->andWhere('p.slug IS NOT NULL')
            ->andWhere("TRIM(p.slug) != ''")
            ->setParameter('activeProfileStatus', PrestataireProfileStatusEnum::ACTIVE)
            ->setParameter('verifiedStatuses', PrestataireSearchEligibility::VERIFIED_STATUSES)
            ->setParameter('hiddenVisibility', SearchVisibilityEnum::HIDDEN)
            ->setParameter('excludedAccountStatuses', [
                UserStatusEnum::SUSPENDED,
                UserStatusEnum::BANNED,
                UserStatusEnum::DELETED,
            ])
            ->orderBy('p.slug', 'ASC')
            ->getQuery()
            ->getResult();
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
                ->setParameter('query', '%'.$query.'%');
        }

        if ('' !== $location && null === $searchedLocation) {
            $qb
                ->andWhere('
                LOWER(p.city) LIKE LOWER(:location)
                OR p.postalCode LIKE :locationExact
                OR LOWER(z.city) LIKE LOWER(:location)
                OR z.postalCode LIKE :locationExact
            ')
                ->setParameter('location', '%'.$location.'%')
                ->setParameter('locationExact', '%'.$location.'%');
        }

        if (null !== $subCategory) {
            $qb
                ->andWhere('c = :subCategory OR parent = :subCategory')
                ->setParameter('subCategory', $subCategory);
        }

        return $qb;
    }
}
