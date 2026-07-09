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
