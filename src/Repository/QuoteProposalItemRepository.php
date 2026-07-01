<?php

namespace App\Repository;

use App\Entity\QuoteProposal;
use App\Entity\QuoteProposalItem;
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