<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\PrestataireProfile;
use App\Entity\User;
use App\Enum\MessageTypeEnum;
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

    public function calculateAverageFirstResponseTimeMinutesForPrestataire(
        PrestataireProfile $prestataireProfile,
    ): ?int {
        $messages = $this->createQueryBuilder('m')
            ->addSelect('conversation', 'author', 'authorPrestataire', 'authorClient')
            ->leftJoin('m.conversation', 'conversation')
            ->leftJoin('m.author', 'author')
            ->leftJoin('author.prestataireProfile', 'authorPrestataire')
            ->leftJoin('author.clientProfile', 'authorClient')
            ->andWhere('conversation.prestataire = :prestataire')
            ->andWhere('m.type = :messageType')
            ->andWhere('m.author IS NOT NULL')
            ->setParameter('prestataire', $prestataireProfile)
            ->setParameter('messageType', MessageTypeEnum::USER)
            ->orderBy('conversation.id', 'ASC')
            ->addOrderBy('m.createdAt', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();

        $firstClientMessageAtByConversation = [];
        $responseTimesInSeconds = [];

        foreach ($messages as $message) {
            $conversation = $message->getConversation();
            $author = $message->getAuthor();

            if (!$message instanceof Message || !$conversation instanceof Conversation || !$author instanceof User) {
                continue;
            }

            $conversationId = $conversation->getId();

            if (null === $conversationId) {
                continue;
            }

            $authorClientProfile = $author->getClientProfile();
            $authorPrestataireProfile = $author->getPrestataireProfile();

            if (
                $authorClientProfile instanceof \App\Entity\ClientProfile
                && $conversation->getClient()?->getId() === $authorClientProfile->getId()
            ) {
                $firstClientMessageAtByConversation[$conversationId] ??= $message->getCreatedAt();

                continue;
            }

            if (
                !$authorPrestataireProfile instanceof PrestataireProfile
                || $authorPrestataireProfile->getId() !== $prestataireProfile->getId()
            ) {
                continue;
            }

            if (!isset($firstClientMessageAtByConversation[$conversationId])) {
                continue;
            }

            $firstClientMessageAt = $firstClientMessageAtByConversation[$conversationId];

            if (!$firstClientMessageAt instanceof \DateTimeImmutable || !$message->getCreatedAt() instanceof \DateTimeImmutable) {
                continue;
            }

            $responseTimesInSeconds[] = max(
                0,
                $message->getCreatedAt()->getTimestamp() - $firstClientMessageAt->getTimestamp()
            );

            unset($firstClientMessageAtByConversation[$conversationId]);
        }

        if ([] === $responseTimesInSeconds) {
            return null;
        }

        $averageResponseTimeInSeconds = array_sum($responseTimesInSeconds) / count($responseTimesInSeconds);

        return max(1, (int) round($averageResponseTimeInSeconds / 60));
    }
}
