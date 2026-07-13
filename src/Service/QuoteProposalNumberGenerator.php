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
        $year = (new \DateTimeImmutable())->format('Y');
        $proposalNumber = sprintf('DEV-%s-%05d', $year, $sequence);

        return [
            'number' => $proposalNumber,
            'sequence' => $sequence,
        ];
    }
}
