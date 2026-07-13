<?php

namespace App\Repository;

use App\Entity\ClientProfile;
use App\Entity\Conversation;
use App\Entity\PrestataireProfile;
use App\Entity\QuoteProposal;
use App\Entity\QuoteRequest;
use App\Enum\QuoteProposalStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuoteProposalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteProposal::class);
    }

    public function findLastProposalNumberForYear(string $year): ?string
    {
        $result = $this->createQueryBuilder('qp')
            ->select('qp.proposalNumber')
            ->andWhere('qp.proposalNumber LIKE :pattern')
            ->setParameter('pattern', 'DEV-' . $year . '-%')
            ->orderBy('qp.proposalNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (\is_array($result)) {
            return $result['proposalNumber'] ?? null;
        }

        return null;
    }

    public function findNextSequenceForPrestataire(PrestataireProfile $prestataire): int
    {
        $result = $this->createQueryBuilder('qp')
            ->select('MAX(qp.proposalSequenceNumber) AS maxSequence')
            ->andWhere('qp.prestataire = :prestataire')
            ->setParameter('prestataire', $prestataire)
            ->getQuery()
            ->getOneOrNullResult();

        $maxSequence = is_array($result) ? (int) ($result['maxSequence'] ?? 0) : 0;

        return $maxSequence + 1;
    }

    public function findOneVisibleForClientByPublicReference(
        string $publicReference,
        ClientProfile $client
    ): ?QuoteProposal {
        return $this->createQueryBuilder('qp')
            ->andWhere('qp.publicReference = :publicReference')
            ->andWhere('qp.client = :client')
            ->andWhere('qp.deletedAt IS NULL')
            ->andWhere('qp.status IN (:statuses)')
            ->setParameter('publicReference', $publicReference)
            ->setParameter('client', $client)
            ->setParameter('statuses', [
                QuoteProposalStatusEnum::FINALIZED,
                QuoteProposalStatusEnum::ACCEPTED,
                QuoteProposalStatusEnum::ARCHIVED,
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneActiveByQuoteRequestAndPrestataire(
        QuoteRequest $quoteRequest,
        PrestataireProfile $prestataire
    ): ?QuoteProposal {
        return $this->createQueryBuilder('qp')
            ->andWhere('qp.quoteRequest = :quoteRequest')
            ->andWhere('qp.prestataire = :prestataire')
            ->andWhere('qp.deletedAt IS NULL')
            ->andWhere('qp.archivedByPrestataireAt IS NULL')
            ->setParameter('quoteRequest', $quoteRequest)
            ->setParameter('prestataire', $prestataire)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneForPrestataireById(string|int $id, PrestataireProfile $prestataire): ?QuoteProposal
    {
        return $this->createQueryBuilder('qp')
            ->andWhere('qp.id = :id')
            ->andWhere('qp.prestataire = :prestataire')
            ->andWhere('qp.deletedAt IS NULL')
            ->andWhere('qp.archivedByPrestataireAt IS NULL')
            ->setParameter('id', (string) $id)
            ->setParameter('prestataire', $prestataire)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneForPrestataireByPublicReference(
        string $publicReference,
        PrestataireProfile $prestataire
    ): ?QuoteProposal {
        return $this->createQueryBuilder('qp')
            ->andWhere('qp.publicReference = :publicReference')
            ->andWhere('qp.prestataire = :prestataire')
            ->andWhere('qp.deletedAt IS NULL')
            ->andWhere('qp.archivedByPrestataireAt IS NULL')
            ->setParameter('publicReference', $publicReference)
            ->setParameter('prestataire', $prestataire)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneForPrestataireByPublicReferenceIncludingArchived(
        string $publicReference,
        PrestataireProfile $prestataire
    ): ?QuoteProposal {
        return $this->createQueryBuilder('qp')
            ->andWhere('qp.publicReference = :publicReference')
            ->andWhere('qp.prestataire = :prestataire')
            ->andWhere('qp.deletedAt IS NULL')
            ->setParameter('publicReference', $publicReference)
            ->setParameter('prestataire', $prestataire)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneForClientById(string|int $id, ClientProfile $client): ?QuoteProposal
    {
        return $this->createQueryBuilder('qp')
            ->andWhere('qp.id = :id')
            ->andWhere('qp.client = :client')
            ->andWhere('qp.deletedAt IS NULL')
            ->andWhere('qp.archivedByPrestataireAt IS NULL')
            ->andWhere('qp.status IN (:statuses)')
            ->setParameter('id', (string) $id)
            ->setParameter('client', $client)
            ->setParameter('statuses', [
                QuoteProposalStatusEnum::FINALIZED,
                QuoteProposalStatusEnum::ACCEPTED,
                QuoteProposalStatusEnum::ARCHIVED,
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<int, QuoteProposal>
     */
    public function findActiveByConversation(Conversation $conversation): array
    {
        return $this->createQueryBuilder('qp')
            ->andWhere('qp.conversation = :conversation')
            ->andWhere('qp.deletedAt IS NULL')
            ->andWhere('qp.archivedByPrestataireAt IS NULL')
            ->orderBy('qp.createdAt', 'DESC')
            ->setParameter('conversation', $conversation)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, QuoteProposal>
     */
    public function findActiveByQuoteRequest(QuoteRequest $quoteRequest): array
    {
        return $this->createQueryBuilder('qp')
            ->andWhere('qp.quoteRequest = :quoteRequest')
            ->andWhere('qp.deletedAt IS NULL')
            ->andWhere('qp.archivedByPrestataireAt IS NULL')
            ->orderBy('qp.createdAt', 'DESC')
            ->setParameter('quoteRequest', $quoteRequest)
            ->getQuery()
            ->getResult();
    }

    public function findOneArchivedByQuoteRequestAndPrestataire(
        QuoteRequest $quoteRequest,
        PrestataireProfile $prestataire
    ): ?QuoteProposal {
        return $this->createQueryBuilder('qp')
            ->andWhere('qp.quoteRequest = :quoteRequest')
            ->andWhere('qp.prestataire = :prestataire')
            ->andWhere('qp.deletedAt IS NULL')
            ->andWhere('qp.archivedByPrestataireAt IS NOT NULL')
            ->setParameter('quoteRequest', $quoteRequest)
            ->setParameter('prestataire', $prestataire)
            ->orderBy('qp.archivedByPrestataireAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneVisibleByQuoteRequestAndPrestataire(
        QuoteRequest $quoteRequest,
        PrestataireProfile $prestataire
    ): ?QuoteProposal {
        return $this->createQueryBuilder('qp')
            ->andWhere('qp.quoteRequest = :quoteRequest')
            ->andWhere('qp.prestataire = :prestataire')
            ->andWhere('qp.deletedAt IS NULL')
            ->setParameter('quoteRequest', $quoteRequest)
            ->setParameter('prestataire', $prestataire)
            ->orderBy('qp.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
