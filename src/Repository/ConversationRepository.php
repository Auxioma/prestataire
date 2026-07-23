<?php

namespace App\Repository;

use App\Entity\ClientProfile;
use App\Entity\Conversation;
use App\Entity\PrestataireProfile;
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

    public function findThreadForPrestataire(Conversation $conversation, PrestataireProfile $prestataireProfile): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->addSelect(
                'quoteRequest',
                'client',
                'clientAccount',
                'messages',
                'author',
                'authorPrestataireProfile',
                'attachments'
            )
            ->leftJoin('c.quoteRequest', 'quoteRequest')
            ->leftJoin('c.client', 'client')
            ->leftJoin('client.account', 'clientAccount')
            ->leftJoin('c.messages', 'messages')
            ->leftJoin('messages.author', 'author')
            ->leftJoin('author.prestataireProfile', 'authorPrestataireProfile')
            ->leftJoin('messages.attachments', 'attachments')
            ->andWhere('c.id = :conversationId')
            ->andWhere('c.prestataire = :prestataire')
            ->setParameter('conversationId', $conversation->getId())
            ->setParameter('prestataire', $prestataireProfile)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findThreadForClient(Conversation $conversation, ClientProfile $clientProfile): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->addSelect(
                'quoteRequest',
                'prestataire',
                'prestataireAccount',
                'messages',
                'author',
                'authorPrestataireProfile',
                'attachments'
            )
            ->leftJoin('c.quoteRequest', 'quoteRequest')
            ->leftJoin('c.prestataire', 'prestataire')
            ->leftJoin('prestataire.account', 'prestataireAccount')
            ->leftJoin('c.messages', 'messages')
            ->leftJoin('messages.author', 'author')
            ->leftJoin('author.prestataireProfile', 'authorPrestataireProfile')
            ->leftJoin('messages.attachments', 'attachments')
            ->andWhere('c.id = :conversationId')
            ->andWhere('c.client = :client')
            ->setParameter('conversationId', $conversation->getId())
            ->setParameter('client', $clientProfile)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
