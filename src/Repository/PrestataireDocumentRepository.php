<?php

namespace App\Repository;

use App\Entity\PrestataireDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrestataireDocument>
 */
class PrestataireDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrestataireDocument::class);
    }

    /**
     * @return PrestataireDocument[]
     */
    public function findByPrestataire(int $prestataireProfileId): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.prestataireProfile = :prestataireProfileId')
            ->setParameter('prestataireProfileId', $prestataireProfileId)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PrestataireDocument[]
     */
    public function findVisibleToClientByPrestataire(int $prestataireProfileId): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.prestataireProfile = :prestataireProfileId')
            ->andWhere('d.isVisibleToClient = :visible')
            ->setParameter('prestataireProfileId', $prestataireProfileId)
            ->setParameter('visible', true)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}