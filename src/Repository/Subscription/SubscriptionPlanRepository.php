<?php

namespace App\Repository\Subscription;

use App\Entity\Subscription\SubscriptionPlan;
use App\Enum\SubscriptionPlanStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubscriptionPlan>
 */
class SubscriptionPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionPlan::class);
    }

    /**
     * @return list<SubscriptionPlan>
     */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('sp')
            ->andWhere('sp.status = :status')
            ->setParameter('status', SubscriptionPlanStatusEnum::ACTIVE)
            ->orderBy('sp.sortOrder', 'ASC')
            ->addOrderBy('sp.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneActiveByCode(string $code): ?SubscriptionPlan
    {
        return $this->createQueryBuilder('sp')
            ->andWhere('sp.code = :code')
            ->andWhere('sp.status = :status')
            ->setParameter('code', $code)
            ->setParameter('status', SubscriptionPlanStatusEnum::ACTIVE)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByStripePriceId(string $stripePriceId): ?SubscriptionPlan
    {
        return $this->createQueryBuilder('sp')
            ->andWhere('sp.monthlyStripePriceId = :stripePriceId OR sp.annualStripePriceId = :stripePriceId')
            ->setParameter('stripePriceId', $stripePriceId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
