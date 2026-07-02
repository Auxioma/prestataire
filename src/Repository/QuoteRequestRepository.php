<?php

namespace App\Repository;

use App\Entity\ClientProfile;
use App\Entity\QuoteRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class QuoteRequestRepository extends ServiceEntityRepository
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
            ->orderBy('qr.createdAt', 'DESC');
    }

    public function findOneActiveForClientBySlug(string $slug, ClientProfile $client): ?QuoteRequest
    {
        return $this->createQueryBuilder('qr')
            ->andWhere('qr.slug = :slug')
            ->andWhere('qr.client = :client')
            ->andWhere('qr.deletedAt IS NULL')
            ->andWhere('qr.archivedByClientAt IS NULL')
            ->setParameter('slug', $slug)
            ->setParameter('client', $client)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}