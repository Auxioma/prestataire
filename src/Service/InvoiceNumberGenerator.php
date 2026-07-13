<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\PrestataireProfile;
use App\Entity\QuoteProposal;
use App\Repository\InvoiceRepository;

final class InvoiceNumberGenerator
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
    ) {}

    public function generate(PrestataireProfile $prestataire, ?QuoteProposal $proposal = null): string
    {
        $year = $this->resolveInvoiceYear($proposal);

        if ($proposal instanceof QuoteProposal && $proposal->getProposalSequenceNumber() !== null) {
            $candidate = sprintf('FAC-%s-%05d', $year, $proposal->getProposalSequenceNumber());
            $existing = $this->invoiceRepository->findOneByQuoteProposal($proposal);

            if (!$existing instanceof Invoice || $existing->getQuoteProposal()?->getId() === $proposal->getId()) {
                return $candidate;
            }
        }

        $sequence = max(1, $this->invoiceRepository->findNextSequenceForPrestataire($prestataire));
        $invoiceNumber = sprintf('FAC-%s-%05d', $year, $sequence);

        return $invoiceNumber;
    }

    private function resolveInvoiceYear(?QuoteProposal $proposal): string
    {
        if ($proposal instanceof QuoteProposal) {
            $proposalNumber = $proposal->getProposalNumber();

            if (is_string($proposalNumber) && preg_match('/^DEV-(\d{4})-\d{5}$/', $proposalNumber, $matches) === 1) {
                return $matches[1];
            }

            $date = $proposal->getFinalizedAt() ?? $proposal->getCreatedAt();
            if ($date instanceof \DateTimeInterface) {
                return $date->format('Y');
            }
        }

        return (new \DateTimeImmutable())->format('Y');
    }
}
