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
        if ($proposal instanceof QuoteProposal && $proposal->getProposalSequenceNumber() !== null) {
            $candidate = sprintf('FAC-%05d', $proposal->getProposalSequenceNumber());

            $existing = $this->invoiceRepository->findOneBy([
                'prestataire' => $prestataire,
                'invoiceNumber' => $candidate,
            ]);

            if (!$existing instanceof Invoice || $existing->getQuoteProposal()?->getId() === $proposal->getId()) {
                return $candidate;
            }
        }

        $sequence = max(1, $this->invoiceRepository->findNextSequenceForPrestataire($prestataire));

        do {
            $invoiceNumber = sprintf('FAC-%05d', $sequence);
            ++$sequence;
        } while ($this->invoiceRepository->findOneBy([
            'prestataire' => $prestataire,
            'invoiceNumber' => $invoiceNumber,
        ]) !== null);

        return $invoiceNumber;
    }
}
