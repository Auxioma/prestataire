<?php

namespace App\Repository;

use App\Entity\PrestataireProfile;
use App\Entity\Service;
use App\Enum\PrestataireProfileStatusEnum; // Importation essentielle
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PrestataireProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrestataireProfile::class);
    }

    /**
     * Récupère tous les prestataires actifs qui proposent un service spécifique
     */
    public function findByService(Service $service): array
    {
        return $this->createQueryBuilder('p')
            // 1. Jointure sur la relation ManyToMany
            ->join('p.services', 's')

            // 2. Filtrage sur l'ID du service sélectionné
            ->andWhere('s.id = :serviceId')
            ->setParameter('serviceId', $service->getId())

            // 3. Sécurité : Uniquement les prestataires avec le statut ACTIVE
            ->andWhere('p.profileStatus = :status')
            ->setParameter('status', PrestataireProfileStatusEnum::ACTIVE)

            // 4. Optimisation : Jointure pour charger le compte User (email, nom, prénom...)
            ->leftJoin('p.account', 'a')
            ->addSelect('a')

            // 5. Tri par date de création (les plus récents en premier)
            ->orderBy('p.createdAt', 'DESC')

            ->getQuery()
            ->getResult();
    }
}
