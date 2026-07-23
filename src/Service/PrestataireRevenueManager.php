<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\PrestataireRevenueEntry;
use Doctrine\ORM\EntityManagerInterface;

final class PrestataireRevenueManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function markInvoiceAsPaid(Invoice $invoice): void
    {
        if ($invoice->isPaid()) {
            return;
        }

        $invoice->setPaidAt(new \DateTimeImmutable());
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();
    }

    public function markManualRevenueAsPaid(PrestataireRevenueEntry $entry): void
    {
        if ($entry->isPaid()) {
            return;
        }

        $entry->setPaidAt(new \DateTimeImmutable());
        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }

    public function markManualRevenueAsUnpaid(PrestataireRevenueEntry $entry): void
    {
        if (!$entry->isPaid()) {
            return;
        }

        $entry->setPaidAt(null);
        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }

    public function deleteManualRevenue(PrestataireRevenueEntry $entry): void
    {
        $this->entityManager->remove($entry);
        $this->entityManager->flush();
    }
}
