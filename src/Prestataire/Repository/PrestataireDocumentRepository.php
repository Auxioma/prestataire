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

namespace App\Prestataire\Repository;

use App\Prestataire\Entity\PrestataireDocument;
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
