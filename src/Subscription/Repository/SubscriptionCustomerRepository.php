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

namespace App\Subscription\Repository;

use App\Prestataire\Entity\PrestataireProfile;
use App\Subscription\Entity\SubscriptionCustomer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubscriptionCustomer>
 */
class SubscriptionCustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionCustomer::class);
    }

    public function findOneByPrestataire(PrestataireProfile $prestataireProfile): ?SubscriptionCustomer
    {
        return $this->findOneBy(['prestataireProfile' => $prestataireProfile]);
    }

    public function findOneByStripeCustomerId(string $stripeCustomerId): ?SubscriptionCustomer
    {
        return $this->findOneBy(['stripeCustomerId' => $stripeCustomerId]);
    }

    /**
     * @return list<SubscriptionCustomer>
     */
    public function findManagedStripeCustomers(): array
    {
        return $this->createQueryBuilder('customer')
            ->andWhere('customer.stripeCustomerId IS NOT NULL')
            ->andWhere('customer.stripeCustomerId <> :empty')
            ->andWhere('customer.stripeCustomerId NOT LIKE :demoPrefix')
            ->setParameter('empty', '')
            ->setParameter('demoPrefix', 'cus_demo_%')
            ->orderBy('customer.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
