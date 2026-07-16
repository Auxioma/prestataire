<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\PrestataireProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * @return Message[]
     */
    public function findByConversation(Conversation $conversation): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByConversationOrderedByCreatedAt(Conversation $conversation): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createdAt', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Message>
     */
    public function findLatestForPrestataire(PrestataireProfile $prestataireProfile, int $limit = 5): array
    {
        return $this->createQueryBuilder('m')
            ->addSelect('conversation', 'quoteRequest', 'author', 'authorPrestataire', 'authorClient')
            ->leftJoin('m.conversation', 'conversation')
            ->leftJoin('conversation.quoteRequest', 'quoteRequest')
            ->leftJoin('m.author', 'author')
            ->leftJoin('author.prestataireProfile', 'authorPrestataire')
            ->leftJoin('author.clientProfile', 'authorClient')
            ->andWhere('conversation.prestataire = :prestataire')
            ->setParameter('prestataire', $prestataireProfile)
            ->orderBy('m.createdAt', 'DESC')
            ->addOrderBy('m.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
