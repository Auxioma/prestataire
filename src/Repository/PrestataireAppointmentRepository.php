<?php

namespace App\Repository;

use App\Entity\PrestataireAppointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrestataireAppointment>
 */
class PrestataireAppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrestataireAppointment::class);
    }

    public function findForCalendarRange(
        int $prestataireId,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
    ): array {
        return $this->createQueryBuilder('pa')
            ->andWhere('pa.prestataire = :prestataireId')
            ->andWhere('pa.startsAt < :end')
            ->andWhere('pa.endsAt > :start')
            ->setParameter('prestataireId', $prestataireId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('pa.startsAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<PrestataireAppointment>
     */
    public function findUpcomingForDashboard(int $prestataireId, int $limit = 3): array
    {
        return $this->createQueryBuilder('pa')
            ->addSelect('client', 'clientAccount', 'prestation', 'service')
            ->leftJoin('pa.client', 'client')
            ->leftJoin('client.account', 'clientAccount')
            ->leftJoin('pa.prestation', 'prestation')
            ->leftJoin('prestation.service', 'service')
            ->andWhere('pa.prestataire = :prestataireId')
            ->andWhere('pa.endsAt >= :now')
            ->setParameter('prestataireId', $prestataireId)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('pa.startsAt', 'ASC')
            ->addOrderBy('pa.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
