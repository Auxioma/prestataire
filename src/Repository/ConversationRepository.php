<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\QuoteRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    public function findOneByQuoteRequest(QuoteRequest $quoteRequest): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.quoteRequest = :quoteRequest')
            ->setParameter('quoteRequest', $quoteRequest)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}