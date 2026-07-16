<?php

namespace App\Repository;

use App\Entity\ClientProfile;
use App\Entity\PrestataireProfile;
use App\Entity\QuoteProposal;
use App\Entity\QuoteRequest;
use App\Entity\Review;
use App\Enum\QuoteProposalStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function findOneByQuoteRequest(QuoteRequest $quoteRequest): ?Review
    {
        return $this->createQueryBuilder('r')
            ->addSelect('prestataire', 'quoteRequest')
            ->leftJoin('r.prestataireProfile', 'prestataire')
            ->leftJoin('r.quoteRequest', 'quoteRequest')
            ->andWhere('r.quoteRequest = :quoteRequest')
            ->setParameter('quoteRequest', $quoteRequest)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<int, Review>
     */
    public function findByClientOrderedByDate(ClientProfile $client): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('prestataire', 'quoteRequest', 'prestation', 'service')
            ->leftJoin('r.prestataireProfile', 'prestataire')
            ->leftJoin('r.quoteRequest', 'quoteRequest')
            ->leftJoin('quoteRequest.prestation', 'prestation')
            ->leftJoin('prestation.service', 'service')
            ->andWhere('r.clientProfile = :client')
            ->setParameter('client', $client)
            ->orderBy('r.createdAt', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Review>
     */
    public function findByPrestataireOrderedByDate(PrestataireProfile $prestataire): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('client', 'account', 'quoteRequest', 'prestation', 'service')
            ->leftJoin('r.clientProfile', 'client')
            ->leftJoin('client.account', 'account')
            ->leftJoin('r.quoteRequest', 'quoteRequest')
            ->leftJoin('quoteRequest.prestation', 'prestation')
            ->leftJoin('prestation.service', 'service')
            ->andWhere('r.prestataireProfile = :prestataire')
            ->setParameter('prestataire', $prestataire)
            ->orderBy('r.createdAt', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function computeAverageRating(PrestataireProfile $prestataire): ?float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) AS averageRating')
            ->andWhere('r.prestataireProfile = :prestataire')
            ->setParameter('prestataire', $prestataire)
            ->getQuery()
            ->getSingleScalarResult();

        return null !== $result ? (float) $result : null;
    }

    public function countByPrestataire(PrestataireProfile $prestataire): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.prestataireProfile = :prestataire')
            ->setParameter('prestataire', $prestataire)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Review>
     */
    public function findRecentForPrestataireDashboard(PrestataireProfile $prestataire, int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('client', 'account', 'quoteRequest')
            ->leftJoin('r.clientProfile', 'client')
            ->leftJoin('client.account', 'account')
            ->leftJoin('r.quoteRequest', 'quoteRequest')
            ->andWhere('r.prestataireProfile = :prestataire')
            ->setParameter('prestataire', $prestataire)
            ->orderBy('r.createdAt', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function hasAcceptedProposalForQuoteRequest(QuoteRequest $quoteRequest): bool
    {
        $count = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(qp.id)')
            ->from(QuoteProposal::class, 'qp')
            ->andWhere('qp.quoteRequest = :quoteRequest')
            ->andWhere('qp.deletedAt IS NULL')
            ->andWhere('(qp.acceptedAt IS NOT NULL OR qp.status = :acceptedStatus)')
            ->setParameter('quoteRequest', $quoteRequest)
            ->setParameter('acceptedStatus', QuoteProposalStatusEnum::ACCEPTED)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    /**
     * @return array<int, QuoteRequest>
     */
    public function findEligibleQuoteRequestsForClient(ClientProfile $client): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT qr, prestataire, prestation, service')
            ->from(QuoteRequest::class, 'qr')
            ->leftJoin('qr.prestataire', 'prestataire')
            ->leftJoin('qr.prestation', 'prestation')
            ->leftJoin('prestation.service', 'service')
            ->leftJoin(Review::class, 'r', 'WITH', 'r.quoteRequest = qr')
            ->innerJoin(
                QuoteProposal::class,
                'qp',
                'WITH',
                'qp.quoteRequest = qr AND qp.deletedAt IS NULL AND (qp.acceptedAt IS NOT NULL OR qp.status = :acceptedStatus)'
            )
            ->andWhere('qr.client = :client')
            ->andWhere('qr.deletedAt IS NULL')
            ->andWhere('r.id IS NULL')
            ->setParameter('client', $client)
            ->setParameter('acceptedStatus', QuoteProposalStatusEnum::ACCEPTED)
            ->orderBy('qr.updatedAt', 'DESC')
            ->addOrderBy('qr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
