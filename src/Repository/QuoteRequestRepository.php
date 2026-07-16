<?php

namespace App\Repository;

use App\Entity\ClientProfile;
use App\Entity\PrestataireProfile;
use App\Entity\QuoteRequest;
use App\Enum\QuoteRequestStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

final class QuoteRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteRequest::class);
    }

    public function createActiveForClientQueryBuilder(ClientProfile $client): QueryBuilder
    {
        return $this->createQueryBuilder('qr')
            ->andWhere('qr.client = :client')
            ->andWhere('qr.deletedAt IS NULL')
            ->andWhere('qr.archivedByClientAt IS NULL')
            ->setParameter('client', $client)
            ->orderBy('qr.updatedAt', 'DESC')
            ->addOrderBy('qr.createdAt', 'DESC');
    }

    public function findOneActiveForClientBySlug(string $slug, ClientProfile $client): ?QuoteRequest
    {
        return $this->createActiveForClientQueryBuilder($client)
            ->andWhere('qr.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function createArchivedForClientQueryBuilder(ClientProfile $client): QueryBuilder
    {
        return $this->createQueryBuilder('qr')
            ->andWhere('qr.client = :client')
            ->andWhere('qr.deletedAt IS NULL')
            ->andWhere('qr.archivedByClientAt IS NOT NULL')
            ->setParameter('client', $client)
            ->orderBy('qr.archivedByClientAt', 'DESC')
            ->addOrderBy('qr.updatedAt', 'DESC');
    }

    public function findOneArchivedForClientBySlug(string $slug, ClientProfile $client): ?QuoteRequest
    {
        return $this->createArchivedForClientQueryBuilder($client)
            ->andWhere('qr.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneForClientBySlug(string $slug, ClientProfile $client): ?QuoteRequest
    {
        return $this->createQueryBuilder('qr')
            ->andWhere('qr.slug = :slug')
            ->andWhere('qr.client = :client')
            ->andWhere('qr.deletedAt IS NULL')
            ->setParameter('slug', $slug)
            ->setParameter('client', $client)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<QuoteRequest>
     */
    public function findRecentForPrestataireDashboard(PrestataireProfile $prestataireProfile, int $limit = 5): array
    {
        return $this->createQueryBuilder('qr')
            ->addSelect('client', 'account', 'prestation', 'service')
            ->leftJoin('qr.client', 'client')
            ->leftJoin('client.account', 'account')
            ->leftJoin('qr.prestation', 'prestation')
            ->leftJoin('prestation.service', 'service')
            ->andWhere('qr.prestataire = :prestataire')
            ->andWhere('qr.deletedAt IS NULL')
            ->andWhere('qr.archivedByPrestataireAt IS NULL')
            ->andWhere('qr.status IN (:activeStatuses)')
            ->setParameter('prestataire', $prestataireProfile)
            ->setParameter('activeStatuses', [
                QuoteRequestStatusEnum::SUBMITTED,
                QuoteRequestStatusEnum::ACCEPTED,
                QuoteRequestStatusEnum::ANSWERED,
                QuoteRequestStatusEnum::CLOSED,
            ])
            ->orderBy('qr.createdAt', 'DESC')
            ->addOrderBy('qr.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<QuoteRequestStatusEnum> $statuses
     */
    public function countForPrestataireByStatuses(
        PrestataireProfile $prestataireProfile,
        array $statuses,
    ): int {
        return (int) $this->createQueryBuilder('qr')
            ->select('COUNT(qr.id)')
            ->andWhere('qr.prestataire = :prestataire')
            ->andWhere('qr.deletedAt IS NULL')
            ->andWhere('qr.archivedByPrestataireAt IS NULL')
            ->andWhere('qr.status IN (:statuses)')
            ->setParameter('prestataire', $prestataireProfile)
            ->setParameter('statuses', $statuses)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
