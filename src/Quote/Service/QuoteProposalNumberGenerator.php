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

namespace App\Quote\Service;

use App\Prestataire\Entity\PrestataireProfile;
use App\Quote\Repository\QuoteProposalRepository;

class QuoteProposalNumberGenerator
{
    public function __construct(
        private readonly QuoteProposalRepository $quoteProposalRepository,
    ) {
    }

    public function generate(PrestataireProfile $prestataire): array
    {
        $sequence = max(1, $this->quoteProposalRepository->findNextSequenceForPrestataire($prestataire));
        $year = (new \DateTimeImmutable())->format('Y');
        $proposalNumber = \sprintf('DEV-%s-%05d', $year, $sequence);

        return [
            'number' => $proposalNumber,
            'sequence' => $sequence,
        ];
    }
}
