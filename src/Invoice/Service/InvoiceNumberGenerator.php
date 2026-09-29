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

namespace App\Invoice\Service;

use App\Invoice\Entity\Invoice;
use App\Invoice\Repository\InvoiceRepository;
use App\Prestataire\Entity\PrestataireProfile;
use App\Quote\Entity\QuoteProposal;

final class InvoiceNumberGenerator
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
    ) {
    }

    public function generate(PrestataireProfile $prestataire, ?QuoteProposal $proposal = null): string
    {
        $year = $this->resolveInvoiceYear($proposal);

        if ($proposal instanceof QuoteProposal && null !== $proposal->getProposalSequenceNumber()) {
            $candidate = \sprintf('FAC-%s-%05d', $year, $proposal->getProposalSequenceNumber());
            $existing = $this->invoiceRepository->findOneByQuoteProposal($proposal);

            if (!$existing instanceof Invoice || $existing->getQuoteProposal()?->getId() === $proposal->getId()) {
                return $candidate;
            }
        }

        $sequence = max(1, $this->invoiceRepository->findNextSequenceForPrestataire($prestataire));
        $invoiceNumber = \sprintf('FAC-%s-%05d', $year, $sequence);

        return $invoiceNumber;
    }

    private function resolveInvoiceYear(?QuoteProposal $proposal): string
    {
        if ($proposal instanceof QuoteProposal) {
            $proposalNumber = $proposal->getProposalNumber();

            if (\is_string($proposalNumber) && 1 === preg_match('/^DEV-(\d{4})-\d{5}$/', $proposalNumber, $matches)) {
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
