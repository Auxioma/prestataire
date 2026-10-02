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

namespace App\Messaging\Repository;

use App\Account\Entity\User;
use App\Messaging\Entity\Conversation;
use App\Messaging\Entity\Message;
use App\Messaging\Enum\MessageTypeEnum;
use App\Prestataire\Entity\PrestataireProfile;
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
            ->andWhere('m.type = :messageType')
            ->andWhere('authorClient IS NOT NULL')
            ->setParameter('prestataire', $prestataireProfile)
            ->setParameter('messageType', MessageTypeEnum::USER)
            ->orderBy('m.createdAt', 'DESC')
            ->addOrderBy('m.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUnreadIncomingForPrestataire(
        PrestataireProfile $prestataireProfile,
        User $prestataireUser,
    ): int {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->leftJoin('m.conversation', 'conversation')
            ->andWhere('conversation.prestataire = :prestataire')
            ->andWhere('m.type = :messageType')
            ->andWhere('m.readAt IS NULL')
            ->andWhere('m.author IS NOT NULL')
            ->andWhere('m.author != :prestataireUser')
            ->setParameter('prestataire', $prestataireProfile)
            ->setParameter('messageType', MessageTypeEnum::USER)
            ->setParameter('prestataireUser', $prestataireUser)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUnreadConversationsForPrestataire(
        PrestataireProfile $prestataireProfile,
        User $prestataireUser,
    ): int {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(DISTINCT conversation.id)')
            ->leftJoin('m.conversation', 'conversation')
            ->andWhere('conversation.prestataire = :prestataire')
            ->andWhere('m.type = :messageType')
            ->andWhere('m.readAt IS NULL')
            ->andWhere('m.author IS NOT NULL')
            ->andWhere('m.author != :prestataireUser')
            ->setParameter('prestataire', $prestataireProfile)
            ->setParameter('messageType', MessageTypeEnum::USER)
            ->setParameter('prestataireUser', $prestataireUser)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Temps moyen entre une première relance client et la réponse du prestataire, calculé côté PostgreSQL.
     *
     * Chaque message du prestataire clôt une « tentative » : les messages client qui le précèdent depuis la
     * réponse précédente forment la question, dont on retient le premier. Le comptage glissant des réponses
     * (fonction de fenêtre) numérote ces tentatives sans charger les messages en PHP.
     */
    public function calculateAverageFirstResponseTimeMinutesForPrestataire(
        PrestataireProfile $prestataireProfile,
    ): ?int {
        $averageSeconds = $this->getEntityManager()->getConnection()->fetchOne(
            <<<'SQL'
                WITH authored AS (
                    SELECT m.conversation_id,
                           m.created_at,
                           m.id,
                           CASE
                               WHEN cp.id = c.client_id THEN 'C'
                               WHEN pp.id = c.prestataire_id THEN 'P'
                           END AS role
                      FROM message m
                      JOIN conversation c ON c.id = m.conversation_id
                      LEFT JOIN client_profile cp ON cp.user_id = m.author_id
                      LEFT JOIN prestataire_profile pp ON pp.user_id = m.author_id
                     WHERE c.prestataire_id = :prestataire
                       AND m.type = :messageType
                       AND m.author_id IS NOT NULL
                ), numbered AS (
                    SELECT conversation_id,
                           created_at,
                           role,
                           COUNT(*) FILTER (WHERE role = 'P') OVER (
                               PARTITION BY conversation_id
                               ORDER BY created_at, id
                               ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING
                           ) AS attempt
                      FROM authored
                     WHERE role IS NOT NULL
                ), attempts AS (
                    SELECT MIN(created_at) FILTER (WHERE role = 'C') AS asked_at,
                           MIN(created_at) FILTER (WHERE role = 'P') AS answered_at
                      FROM numbered
                  GROUP BY conversation_id, attempt
                )
                SELECT AVG(GREATEST(0, EXTRACT(EPOCH FROM answered_at - asked_at)))
                  FROM attempts
                 WHERE asked_at IS NOT NULL
                   AND answered_at IS NOT NULL
                SQL,
            [
                'prestataire' => $prestataireProfile->getId(),
                'messageType' => MessageTypeEnum::USER->value,
            ],
        );

        if (null === $averageSeconds || false === $averageSeconds) {
            return null;
        }

        return max(1, (int) round((float) $averageSeconds / 60));
    }
}
