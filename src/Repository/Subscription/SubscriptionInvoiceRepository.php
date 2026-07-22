<?php

namespace App\Repository\Subscription;

use App\Entity\PrestataireProfile;
use App\Entity\Subscription\PrestataireSubscription;
use App\Entity\Subscription\SubscriptionInvoice;
use App\Enum\SubscriptionInvoiceStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubscriptionInvoice>
 */
class SubscriptionInvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionInvoice::class);
    }

    public function findOneByStripeInvoiceId(string $stripeInvoiceId): ?SubscriptionInvoice
    {
        return $this->findOneBy(['stripeInvoiceId' => $stripeInvoiceId]);
    }

    public function findOneForPrestataireById(PrestataireProfile $prestataireProfile, string $invoiceId): ?SubscriptionInvoice
    {
        return $this->createQueryBuilder('si')
            ->innerJoin('si.subscription', 'subscription')
            ->andWhere('si.id = :invoiceId')
            ->andWhere('subscription.prestataireProfile = :prestataireProfile')
            ->setParameter('invoiceId', $invoiceId)
            ->setParameter('prestataireProfile', $prestataireProfile)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLatestSettledForSubscription(PrestataireSubscription $subscription): ?SubscriptionInvoice
    {
        return $this->createQueryBuilder('si')
            ->andWhere('si.subscription = :subscription')
            ->andWhere('si.status = :status')
            ->setParameter('subscription', $subscription)
            ->setParameter('status', SubscriptionInvoiceStatusEnum::PAID)
            ->orderBy('si.periodEnd', 'DESC')
            ->addOrderBy('si.paidAt', 'DESC')
            ->addOrderBy('si.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<SubscriptionInvoice>
     */
    public function findRecentForPrestataire(PrestataireProfile $prestataireProfile, int $limit = 12): array
    {
        return $this->createQueryBuilder('si')
            ->innerJoin('si.subscription', 'subscription')
            ->leftJoin('subscription.plan', 'plan')
            ->addSelect('subscription', 'plan')
            ->andWhere('subscription.prestataireProfile = :prestataireProfile')
            ->setParameter('prestataireProfile', $prestataireProfile)
            ->orderBy('si.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<SubscriptionInvoice>
     */
    public function findLatestForSubscription(PrestataireSubscription $subscription, int $limit = 20): array
    {
        return $this->createQueryBuilder('si')
            ->andWhere('si.subscription = :subscription')
            ->setParameter('subscription', $subscription)
            ->orderBy('si.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<SubscriptionInvoice>
     */
    public function findOpenInvoicesForPrestataire(PrestataireProfile $prestataireProfile): array
    {
        return $this->createQueryBuilder('si')
            ->innerJoin('si.subscription', 'subscription')
            ->andWhere('subscription.prestataireProfile = :prestataireProfile')
            ->andWhere('si.status IN (:statuses)')
            ->setParameter('prestataireProfile', $prestataireProfile)
            ->setParameter('statuses', [
                SubscriptionInvoiceStatusEnum::DRAFT,
                SubscriptionInvoiceStatusEnum::OPEN,
            ])
            ->orderBy('si.dueAt', 'ASC')
            ->addOrderBy('si.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
