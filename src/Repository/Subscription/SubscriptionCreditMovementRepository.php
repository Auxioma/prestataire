<?php

namespace App\Repository\Subscription;

use App\Entity\PrestataireProfile;
use App\Entity\QuoteRequest;
use App\Entity\Subscription\PrestataireSubscription;
use App\Entity\Subscription\SubscriptionCreditMovement;
use App\Entity\Subscription\SubscriptionInvoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubscriptionCreditMovement>
 */
class SubscriptionCreditMovementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionCreditMovement::class);
    }

    public function getCreditBalanceForPrestataire(PrestataireProfile $prestataireProfile): int
    {
        $balance = $this->createQueryBuilder('scm')
            ->select('COALESCE(SUM(scm.creditsDelta), 0)')
            ->andWhere('scm.prestataireProfile = :prestataireProfile')
            ->setParameter('prestataireProfile', $prestataireProfile)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $balance;
    }

    /**
     * @return list<SubscriptionCreditMovement>
     */
    public function findLatestForPrestataire(PrestataireProfile $prestataireProfile, int $limit = 50): array
    {
        return $this->createQueryBuilder('scm')
            ->andWhere('scm.prestataireProfile = :prestataireProfile')
            ->setParameter('prestataireProfile', $prestataireProfile)
            ->orderBy('scm.occurredAt', 'DESC')
            ->addOrderBy('scm.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOneConsumptionForQuoteRequest(QuoteRequest $quoteRequest): ?SubscriptionCreditMovement
    {
        return $this->createQueryBuilder('scm')
            ->andWhere('scm.quoteRequest = :quoteRequest')
            ->andWhere('scm.creditsDelta < 0')
            ->setParameter('quoteRequest', $quoteRequest)
            ->orderBy('scm.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByInvoice(SubscriptionInvoice $invoice): ?SubscriptionCreditMovement
    {
        return $this->findOneBy(['invoice' => $invoice]);
    }

    public function findOneByStripeInvoiceId(string $stripeInvoiceId): ?SubscriptionCreditMovement
    {
        return $this->createQueryBuilder('scm')
            ->join('scm.invoice', 'i')
            ->andWhere('i.stripeInvoiceId = :stripeInvoiceId')
            ->setParameter('stripeInvoiceId', $stripeInvoiceId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getConsumedCreditsForSubscription(PrestataireSubscription $subscription): int
    {
        $consumed = $this->createQueryBuilder('scm')
            ->select('COALESCE(SUM(ABS(scm.creditsDelta)), 0)')
            ->andWhere('scm.subscription = :subscription')
            ->andWhere('scm.creditsDelta < 0')
            ->setParameter('subscription', $subscription)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $consumed;
    }
}
