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

namespace App\Quote\Repository;

use App\Quote\Entity\QuoteProposal;
use App\Quote\Entity\QuoteProposalItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuoteProposalItem>
 */
class QuoteProposalItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteProposalItem::class);
    }

    /**
     * @return array<int, QuoteProposalItem>
     */
    public function findByQuoteProposalOrdered(QuoteProposal $quoteProposal): array
    {
        return $this->createQueryBuilder('qpi')
            ->andWhere('qpi.quoteProposal = :quoteProposal')
            ->orderBy('qpi.position', 'ASC')
            ->addOrderBy('qpi.id', 'ASC')
            ->setParameter('quoteProposal', $quoteProposal)
            ->getQuery()
            ->getResult();
    }
}
