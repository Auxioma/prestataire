<?php

namespace App\Repository;

use App\Entity\ClientProfile;
use App\Entity\Invoice;
use App\Entity\PrestataireProfile;
use App\Entity\QuoteProposal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    public function findOneByQuoteProposal(QuoteProposal $quoteProposal): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.quoteProposal = :quoteProposal')
            ->setParameter('quoteProposal', $quoteProposal)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneForPrestataireByProposalReference(string $publicReference, PrestataireProfile $prestataire): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.quoteProposal', 'qp')
            ->andWhere('qp.publicReference = :publicReference')
            ->andWhere('i.prestataire = :prestataire')
            ->setParameter('publicReference', $publicReference)
            ->setParameter('prestataire', $prestataire)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneVisibleForClientByProposalReference(string $publicReference, ClientProfile $client): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.quoteProposal', 'qp')
            ->andWhere('qp.publicReference = :publicReference')
            ->andWhere('i.client = :client')
            ->andWhere('i.status = :status')
            ->setParameter('publicReference', $publicReference)
            ->setParameter('client', $client)
            ->setParameter('status', \App\Enum\InvoiceStatusEnum::ISSUED)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLastInvoiceNumberForYear(string $year): ?string
    {
        $result = $this->createQueryBuilder('i')
            ->select('i.invoiceNumber')
            ->andWhere('i.invoiceNumber LIKE :pattern')
            ->setParameter('pattern', 'FAC-' . $year . '-%')
            ->orderBy('i.invoiceNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (\is_array($result)) {
            return $result['invoiceNumber'] ?? null;
        }

        return null;
    }

    public function findNextSequenceForPrestataire(PrestataireProfile $prestataire): int
    {
        $result = $this->createQueryBuilder('i')
            ->select('MAX(i.invoiceSequenceNumber) AS maxSequence')
            ->andWhere('i.prestataire = :prestataire')
            ->setParameter('prestataire', $prestataire)
            ->getQuery()
            ->getOneOrNullResult();

        $maxSequence = is_array($result) ? (int) ($result['maxSequence'] ?? 0) : 0;

        return $maxSequence + 1;
    }
}
