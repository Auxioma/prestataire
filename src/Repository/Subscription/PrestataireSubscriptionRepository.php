<?php

namespace App\Repository\Subscription;

use App\Entity\PrestataireProfile;
use App\Entity\Subscription\PrestataireSubscription;
use App\Enum\SubscriptionStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrestataireSubscription>
 */
class PrestataireSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrestataireSubscription::class);
    }

    public function findLatestForPrestataire(PrestataireProfile $prestataireProfile): ?PrestataireSubscription
    {
        return $this->createQueryBuilder('ps')
            ->andWhere('ps.prestataireProfile = :prestataireProfile')
            ->setParameter('prestataireProfile', $prestataireProfile)
            ->orderBy('ps.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLatestForPrestataireAndPlanCode(
        PrestataireProfile $prestataireProfile,
        string $planCode,
    ): ?PrestataireSubscription {
        return $this->createQueryBuilder('ps')
            ->leftJoin('ps.plan', 'plan')
            ->addSelect('plan')
            ->andWhere('ps.prestataireProfile = :prestataireProfile')
            ->andWhere('plan.code = :planCode')
            ->setParameter('prestataireProfile', $prestataireProfile)
            ->setParameter('planCode', $planCode)
            ->orderBy('ps.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findCurrentUsableForPrestataire(
        PrestataireProfile $prestataireProfile,
        ?\DateTimeImmutable $at = null
    ): ?PrestataireSubscription {
        $at ??= new \DateTimeImmutable();

        return $this->createQueryBuilder('ps')
            ->leftJoin('ps.plan', 'plan')
            ->addSelect('plan')
            ->leftJoin('ps.planPrice', 'planPrice')
            ->addSelect('planPrice')
            ->addSelect('CASE WHEN ps.currentPeriodEnd IS NULL THEN 1 ELSE 0 END AS HIDDEN currentPeriodEndNullRank')
            ->andWhere('ps.prestataireProfile = :prestataireProfile')
            ->andWhere('ps.status IN (:statuses)')
            ->andWhere('ps.endedAt IS NULL OR ps.endedAt > :at')
            ->andWhere('ps.currentPeriodEnd IS NULL OR ps.currentPeriodEnd >= :at')
            ->setParameter('prestataireProfile', $prestataireProfile)
            ->setParameter('statuses', [
                SubscriptionStatusEnum::TRIALING,
                SubscriptionStatusEnum::ACTIVE,
            ])
            ->setParameter('at', $at)
            ->orderBy('currentPeriodEndNullRank', 'ASC')
            ->addOrderBy('ps.currentPeriodEnd', 'DESC')
            ->addOrderBy('ps.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByStripeSubscriptionId(string $stripeSubscriptionId): ?PrestataireSubscription
    {
        return $this->findOneBy(['stripeSubscriptionId' => $stripeSubscriptionId]);
    }

    /**
     * @return list<PrestataireSubscription>
     */
    public function findUsableForPrestataire(
        PrestataireProfile $prestataireProfile,
        ?\DateTimeImmutable $at = null
    ): array {
        $at ??= new \DateTimeImmutable();

        return $this->createQueryBuilder('ps')
            ->leftJoin('ps.plan', 'plan')
            ->addSelect('plan')
            ->leftJoin('ps.planPrice', 'planPrice')
            ->addSelect('planPrice')
            ->addSelect('CASE WHEN ps.currentPeriodEnd IS NULL THEN 1 ELSE 0 END AS HIDDEN currentPeriodEndNullRank')
            ->andWhere('ps.prestataireProfile = :prestataireProfile')
            ->andWhere('ps.status IN (:statuses)')
            ->andWhere('ps.endedAt IS NULL OR ps.endedAt > :at')
            ->andWhere('ps.currentPeriodEnd IS NULL OR ps.currentPeriodEnd >= :at')
            ->setParameter('prestataireProfile', $prestataireProfile)
            ->setParameter('statuses', [
                SubscriptionStatusEnum::TRIALING,
                SubscriptionStatusEnum::ACTIVE,
            ])
            ->setParameter('at', $at)
            ->orderBy('currentPeriodEndNullRank', 'ASC')
            ->addOrderBy('ps.currentPeriodEnd', 'DESC')
            ->addOrderBy('ps.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<PrestataireSubscription>
     */
    public function findEndingSoon(\DateTimeImmutable $until): array
    {
        return $this->createQueryBuilder('ps')
            ->andWhere('ps.cancelAtPeriodEnd = true')
            ->andWhere('ps.currentPeriodEnd IS NOT NULL')
            ->andWhere('ps.currentPeriodEnd <= :until')
            ->andWhere('ps.status IN (:statuses)')
            ->setParameter('until', $until)
            ->setParameter('statuses', [
                SubscriptionStatusEnum::TRIALING,
                SubscriptionStatusEnum::ACTIVE,
            ])
            ->orderBy('ps.currentPeriodEnd', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<PrestataireSubscription>
     */
    public function findSubscriptionsNeedingFreeFallback(?\DateTimeImmutable $at = null): array
    {
        $at ??= new \DateTimeImmutable();

        return $this->createQueryBuilder('ps')
            ->leftJoin('ps.plan', 'plan')
            ->addSelect('plan')
            ->andWhere('plan.code IS NOT NULL')
            ->andWhere('plan.code <> :freeCode')
            ->andWhere('ps.status IN (:statuses)')
            ->andWhere('(ps.endedAt IS NOT NULL AND ps.endedAt <= :at) OR (ps.currentPeriodEnd IS NOT NULL AND ps.currentPeriodEnd <= :at)')
            ->setParameter('freeCode', 'free')
            ->setParameter('statuses', [
                SubscriptionStatusEnum::CANCELED,
                SubscriptionStatusEnum::UNPAID,
                SubscriptionStatusEnum::INCOMPLETE_EXPIRED,
                SubscriptionStatusEnum::PAUSED,
            ])
            ->setParameter('at', $at)
            ->orderBy('ps.currentPeriodEnd', 'ASC')
            ->addOrderBy('ps.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
