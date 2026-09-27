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

namespace App\Invoice\Repository;

use App\Invoice\Entity\Invoice;
use App\Invoice\Entity\InvoiceItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InvoiceItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvoiceItem::class);
    }

    /**
     * @return array<int, InvoiceItem>
     */
    public function findByInvoiceOrdered(Invoice $invoice): array
    {
        return $this->createQueryBuilder('ii')
            ->andWhere('ii.invoice = :invoice')
            ->setParameter('invoice', $invoice)
            ->orderBy('ii.position', 'ASC')
            ->addOrderBy('ii.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
