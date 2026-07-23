<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PrestataireProfile;
use App\Entity\PrestataireRevenueEntry;
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
