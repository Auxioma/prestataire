<?php

namespace App\Repository;

use App\Entity\ClientProfile;
use App\Entity\QuoteRequest;
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
}
