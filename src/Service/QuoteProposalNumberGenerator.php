<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\PrestataireProfile;
use App\Repository\QuoteProposalRepository;

class QuoteProposalNumberGenerator
{
    public function __construct(
        private readonly QuoteProposalRepository $quoteProposalRepository,
    ) {
    }

    public function generate(PrestataireProfile $prestataire): array
    {
        $sequence = max(1, $this->quoteProposalRepository->findNextSequenceForPrestataire($prestataire));

        do {
            $proposalNumber = sprintf('DEV-%05d', $sequence);
            ++$sequence;
        } while ($this->quoteProposalRepository->findOneBy(['proposalNumber' => $proposalNumber]) !== null);

        return [
            'number' => $proposalNumber,
            'sequence' => $sequence - 1,
        ];
    }
}
