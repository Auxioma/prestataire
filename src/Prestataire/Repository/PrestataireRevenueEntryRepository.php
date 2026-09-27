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

use App\Prestataire\Entity\PrestataireProfile;
use App\Prestataire\Entity\PrestataireRevenueEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class PrestataireRevenueEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrestataireRevenueEntry::class);
    }

    /**
     * @return list<PrestataireRevenueEntry>
     */
    public function findForPrestataireRevenue(PrestataireProfile $prestataire): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.prestataireService', 'ps')
            ->addSelect('ps')
            ->leftJoin('ps.service', 's')
            ->addSelect('s')
            ->andWhere('re.prestataire = :prestataire')
            ->setParameter('prestataire', $prestataire)
            ->orderBy('re.issuedAt', 'DESC')
            ->addOrderBy('re.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
