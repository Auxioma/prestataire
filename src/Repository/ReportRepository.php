<?php

namespace App\Repository;

use App\Entity\Report;
use App\Enum\ReportStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Report>
 */
class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    /**
     * @return list<Report>
     */
    public function findLatestOpen(int $limit = 50): array
    {
        return $this->createQueryBuilder('report')
            ->andWhere('report.status IN (:statuses)')
            ->setParameter('statuses', [ReportStatusEnum::NEW, ReportStatusEnum::IN_REVIEW])
            ->orderBy('report.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
