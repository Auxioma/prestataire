<?php

namespace App\Prestataire\Repository;

use App\Prestataire\Entity\PrestataireAvailability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PrestataireAvailabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrestataireAvailability::class);
    }
}