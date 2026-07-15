<?php

namespace App\Repository\Subscription;

use App\Entity\Subscription\SubscriptionPlan;
use App\Entity\Subscription\SubscriptionPlanPrice;
use App\Enum\SubscriptionBillingPeriodEnum;
use App\Enum\SubscriptionPlanStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubscriptionPlanPrice>
 */
class SubscriptionPlanPriceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionPlanPrice::class);
    }

    public function findCurrentForPlanAndPeriod(
        SubscriptionPlan $plan,
        SubscriptionBillingPeriodEnum $billingPeriod,
        ?\DateTimeImmutable $at = null,
    ): ?SubscriptionPlanPrice {
        $at ??= new \DateTimeImmutable();

        return $this->createQueryBuilder('price')
            ->andWhere('price.plan = :plan')
            ->andWhere('price.billingPeriod = :billingPeriod')
            ->andWhere('price.isActive = true')
            ->andWhere('price.validFrom IS NULL OR price.validFrom <= :at')
            ->andWhere('price.validUntil IS NULL OR price.validUntil >= :at')
            ->setParameter('plan', $plan)
            ->setParameter('billingPeriod', $billingPeriod)
            ->setParameter('at', $at)
            ->orderBy('price.isPromotional', 'DESC')
            ->addOrderBy('price.validFrom', 'DESC')
            ->addOrderBy('price.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByStripePriceId(string $stripePriceId): ?SubscriptionPlanPrice
    {
        return $this->findOneBy(['stripePriceId' => $stripePriceId]);
    }

    /**
     * @return list<SubscriptionPlanPrice>
     */
    public function findActiveOrderedForAdmin(): array
    {
        return $this->createQueryBuilder('price')
            ->leftJoin('price.plan', 'plan')
            ->addSelect('plan')
            ->orderBy('plan.sortOrder', 'ASC')
            ->addOrderBy('plan.name', 'ASC')
            ->addOrderBy('price.billingPeriod', 'ASC')
            ->addOrderBy('price.isPromotional', 'DESC')
            ->addOrderBy('price.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<SubscriptionPlanPrice>
     */
    public function findStripeSyncCandidates(): array
    {
        return $this->createQueryBuilder('price')
            ->leftJoin('price.plan', 'plan')
            ->addSelect('plan')
            ->andWhere('price.isActive = true')
            ->andWhere('plan.status = :status')
            ->setParameter('status', SubscriptionPlanStatusEnum::ACTIVE)
            ->orderBy('plan.sortOrder', 'ASC')
            ->addOrderBy('price.billingPeriod', 'ASC')
            ->addOrderBy('price.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
