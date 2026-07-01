<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\QuoteProposalRepository;

class QuoteProposalNumberGenerator
{
    public function __construct(
        private readonly QuoteProposalRepository $quoteProposalRepository,
    ) {
    }

    public function generate(): string
    {
        $year = (new \DateTimeImmutable())->format('Y');

        $lastNumber = $this->quoteProposalRepository->findLastProposalNumberForYear($year);

        $sequence = 1;

        if ($lastNumber !== null && preg_match('/^DEV-' . preg_quote($year, '/') . '-(\d{5})$/', $lastNumber, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('DEV-%s-%05d', $year, $sequence);
    }
}