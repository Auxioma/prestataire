<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 AUXIOMA Web Agency.
 *
 * Projet : TrouveMoi
 *
 * Tous droits réservés.
 *
 * Ce fichier fait partie du projet TrouveMoi,
 * développé par AUXIOMA Web Agency.
 *
 * Toute reproduction, modification, distribution ou utilisation,
 * totale ou partielle, sans autorisation écrite préalable,
 * est strictement interdite.
 */

namespace App\Review\Repository;

use App\Account\Entity\ClientProfile;
use App\Prestataire\Entity\PrestataireProfile;
use App\Quote\Entity\QuoteProposal;
use App\Quote\Entity\QuoteRequest;
use App\Quote\Enum\QuoteProposalStatusEnum;
use App\Review\Entity\Review;
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

    /**
     * @return array<int, Review>
     */
    public function findPublicByPrestataireOrderedByDate(PrestataireProfile $prestataire): array
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

    /**
     * Moyenne et nombre d'avis en un seul passage sur l'index du prestataire.
     *
     * @return array{averageRating: ?float, reviewsCount: int}
     */
    public function computeRatingStats(PrestataireProfile $prestataire): array
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) AS averageRating', 'COUNT(r.id) AS reviewsCount')
            ->andWhere('r.prestataireProfile = :prestataire')
            ->setParameter('prestataire', $prestataire)
            ->getQuery()
            ->getSingleResult();

        return [
            'averageRating' => null !== $result['averageRating'] ? (float) $result['averageRating'] : null,
            'reviewsCount' => (int) $result['reviewsCount'],
        ];
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
