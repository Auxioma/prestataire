<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ClientProfile;
use App\Entity\Invoice;
use App\Entity\PrestataireProfile;
use App\Entity\PrestataireRevenueEntry;
use App\Entity\QuoteProposal;
use App\Entity\User;
use App\Repository\InvoiceRepository;
use App\Repository\PrestataireRevenueEntryRepository;

final class PrestataireRevenueOverviewBuilder
{
    private const MONTH_LABELS = [
        1 => 'Janvier',
        2 => 'Fevrier',
        3 => 'Mars',
        4 => 'Avril',
        5 => 'Mai',
        6 => 'Juin',
        7 => 'Juillet',
        8 => 'Aout',
        9 => 'Septembre',
        10 => 'Octobre',
        11 => 'Novembre',
        12 => 'Decembre',
    ];

    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
        private readonly PrestataireRevenueEntryRepository $prestataireRevenueEntryRepository,
    ) {
    }

    public function build(PrestataireProfile $prestataire, int $selectedMonth, int $selectedYear): array
    {
        $invoices = $this->invoiceRepository->findIssuedForPrestataireRevenue($prestataire);
        $manualEntries = $this->prestataireRevenueEntryRepository->findForPrestataireRevenue($prestataire);

        $history = [];
        $monthly = [];
        $yearly = [];
        $services = [];

        $now = new \DateTimeImmutable();
        $monthKey = sprintf('%04d-%02d', $selectedYear, $selectedMonth);
        $yearKey = sprintf('%04d', $selectedYear);
        $availableYears = [$now->format('Y') => $now->format('Y')];

        $currentMonthInvoiced = 0;
        $currentMonthPaid = 0;
        $currentYearInvoiced = 0;
        $currentYearPaid = 0;
        $outstandingTotal = 0;
        $paidCount = 0;
        $unpaidCount = 0;

        foreach ($invoices as $invoice) {
            $item = $this->buildInvoiceHistoryItem($invoice);
            $history[] = $item;
            $this->aggregatePeriodData($monthly, $yearly, $services, $item);
            $availableYears[$item['issuedYearKey']] = $item['issuedYearKey'];

            $amountCents = $item['totalCents'];

            if ($item['issuedMonthKey'] === $monthKey) {
                $currentMonthInvoiced += $amountCents;
            }

            if ($item['issuedYearKey'] === $yearKey) {
                $currentYearInvoiced += $amountCents;
            }

            if ($item['paidMonthKey'] === $monthKey) {
                $currentMonthPaid += $amountCents;
            }

            if ($item['paidYearKey'] === $yearKey) {
                $currentYearPaid += $amountCents;
            }

            if ($item['isPaid']) {
                ++$paidCount;
            } else {
                ++$unpaidCount;
                $outstandingTotal += $amountCents;
            }
        }

        foreach ($manualEntries as $entry) {
            $item = $this->buildManualHistoryItem($entry);
            $history[] = $item;
            $this->aggregatePeriodData($monthly, $yearly, $services, $item);
            $availableYears[$item['issuedYearKey']] = $item['issuedYearKey'];

            $amountCents = $item['totalCents'];

            if ($item['issuedMonthKey'] === $monthKey) {
                $currentMonthInvoiced += $amountCents;
            }

            if ($item['issuedYearKey'] === $yearKey) {
                $currentYearInvoiced += $amountCents;
            }

            if ($item['paidMonthKey'] === $monthKey) {
                $currentMonthPaid += $amountCents;
            }

            if ($item['paidYearKey'] === $yearKey) {
                $currentYearPaid += $amountCents;
            }

            if ($item['isPaid']) {
                ++$paidCount;
            } else {
                ++$unpaidCount;
                $outstandingTotal += $amountCents;
            }
        }

        usort($history, static fn (array $a, array $b): int => $b['issuedAt']->getTimestamp() <=> $a['issuedAt']->getTimestamp());

        $unpaid = array_values(array_filter($history, static fn (array $item): bool => !$item['isPaid']));
        usort($unpaid, static fn (array $a, array $b): int => $a['issuedAt']->getTimestamp() <=> $b['issuedAt']->getTimestamp());

        return [
            'summary' => [
                'selectedMonthInvoiced' => $this->formatCents($currentMonthInvoiced),
                'selectedMonthPaid' => $this->formatCents($currentMonthPaid),
                'selectedYearInvoiced' => $this->formatCents($currentYearInvoiced),
                'selectedYearPaid' => $this->formatCents($currentYearPaid),
                'outstandingTotal' => $this->formatCents($outstandingTotal),
                'paidCount' => $paidCount,
                'unpaidCount' => $unpaidCount,
                'invoicedCount' => \count($history),
            ],
            'filters' => [
                'selectedMonth' => $selectedMonth,
                'selectedYear' => $selectedYear,
                'selectedMonthLabel' => $this->buildMonthLabel($selectedMonth, $selectedYear),
                'availableMonths' => $this->buildMonthChoices(),
                'availableYears' => $this->buildYearChoices($availableYears),
            ],
            'monthly' => $this->normalizeGroupedRows($monthly, true),
            'yearly' => $this->normalizeGroupedRows($yearly, true),
            'services' => $this->normalizeGroupedRows($services, false),
            'history' => $history,
            'unpaid' => $unpaid,
        ];
    }

    private function buildInvoiceHistoryItem(Invoice $invoice): array
    {
        $issuedAt = $invoice->getIssuedAt() ?? $invoice->getCreatedAt() ?? new \DateTimeImmutable();
        $paidAt = $invoice->getPaidAt();
        $proposal = $invoice->getQuoteProposal();
        $quoteRequest = $invoice->getQuoteRequest();

        return [
            'kind' => 'invoice',
            'id' => (string) $invoice->getId(),
            'sourceLabel' => 'TrouveMoi',
            'label' => $proposal?->getTitle() ?: ($invoice->getInvoiceNumber() ?: 'Facture'),
            'invoiceNumber' => $invoice->getInvoiceNumber(),
            'clientName' => $this->resolveClientNameFromInvoice($invoice),
            'serviceLabel' => $quoteRequest?->getPrestation()?->getDisplayTitle() ?: ($proposal?->getTitle() ?: 'Prestation'),
            'issuedAt' => $issuedAt,
            'paidAt' => $paidAt,
            'issuedMonthKey' => $issuedAt->format('Y-m'),
            'issuedYearKey' => $issuedAt->format('Y'),
            'paidMonthKey' => $paidAt?->format('Y-m'),
            'paidYearKey' => $paidAt?->format('Y'),
            'statusLabel' => $invoice->isPaid() ? 'Payee' : 'Emise',
            'isPaid' => $invoice->isPaid(),
            'notes' => $invoice->getNotes(),
            'totalTtc' => $invoice->getTotalTtc(),
            'totalCents' => $this->decimalToCents($invoice->getTotalTtc()),
            'viewUrl' => $proposal?->getPublicReference()
                ? ['route' => 'app_prestataire_invoice_show', 'parameters' => ['publicReference' => $proposal->getPublicReference()]]
                : null,
            'markPaidRoute' => !$invoice->isPaid()
                ? ['route' => 'app_prestataire_revenue_invoice_mark_paid', 'parameters' => ['id' => $invoice->getId()]]
                : null,
            'markUnpaidRoute' => null,
            'editUrl' => null,
            'deleteRoute' => null,
        ];
    }

    private function buildManualHistoryItem(PrestataireRevenueEntry $entry): array
    {
        $issuedAt = $entry->getIssuedAt() ?? new \DateTimeImmutable();
        $paidAt = $entry->getPaidAt();

        return [
            'kind' => 'manual',
            'id' => (string) $entry->getId(),
            'sourceLabel' => 'Externe',
            'label' => $entry->getLabel() ?: 'Revenu externe',
            'invoiceNumber' => $entry->getInvoiceNumber(),
            'clientName' => $entry->getClientName() ?: 'Client externe',
            'serviceLabel' => $entry->getResolvedServiceLabel(),
            'issuedAt' => $issuedAt,
            'paidAt' => $paidAt,
            'issuedMonthKey' => $issuedAt->format('Y-m'),
            'issuedYearKey' => $issuedAt->format('Y'),
            'paidMonthKey' => $paidAt?->format('Y-m'),
            'paidYearKey' => $paidAt?->format('Y'),
            'statusLabel' => $entry->isPaid() ? 'Payee' : 'Emise',
            'isPaid' => $entry->isPaid(),
            'notes' => $entry->getNotes(),
            'totalTtc' => $entry->getTotalTtc(),
            'totalCents' => $this->decimalToCents($entry->getTotalTtc()),
            'viewUrl' => null,
            'markPaidRoute' => !$entry->isPaid()
                ? ['route' => 'app_prestataire_revenue_manual_mark_paid', 'parameters' => ['id' => $entry->getId()]]
                : null,
            'markUnpaidRoute' => $entry->isPaid()
                ? ['route' => 'app_prestataire_revenue_manual_mark_unpaid', 'parameters' => ['id' => $entry->getId()]]
                : null,
            'editUrl' => ['route' => 'app_prestataire_dashboard', 'parameters' => ['tab' => 'revenus', 'edit_revenue' => $entry->getId()]],
            'deleteRoute' => ['route' => 'app_prestataire_revenue_manual_delete', 'parameters' => ['id' => $entry->getId()]],
        ];
    }

    private function aggregatePeriodData(array &$monthly, array &$yearly, array &$services, array $item): void
    {
        $this->addGroupedAmount(
            $monthly,
            $item['issuedMonthKey'],
            $item['issuedAt']->format('m/Y'),
            $item['totalCents'],
            $item['isPaid'] ? $item['totalCents'] : 0
        );

        $this->addGroupedAmount(
            $yearly,
            $item['issuedYearKey'],
            $item['issuedAt']->format('Y'),
            $item['totalCents'],
            $item['isPaid'] ? $item['totalCents'] : 0
        );

        $this->addGroupedAmount(
            $services,
            $item['serviceLabel'],
            $item['serviceLabel'],
            $item['totalCents'],
            $item['isPaid'] ? $item['totalCents'] : 0
        );
    }

    private function addGroupedAmount(array &$rows, string $key, string $label, int $invoicedCents, int $paidCents): void
    {
        if (!isset($rows[$key])) {
            $rows[$key] = [
                'key' => $key,
                'label' => $label,
                'invoicedCents' => 0,
                'paidCents' => 0,
                'count' => 0,
            ];
        }

        $rows[$key]['invoicedCents'] += $invoicedCents;
        $rows[$key]['paidCents'] += $paidCents;
        ++$rows[$key]['count'];
    }

    private function normalizeGroupedRows(array $rows, bool $sortDescending): array
    {
        $normalized = array_values(array_map(function (array $row): array {
            return [
                'key' => $row['key'],
                'label' => $row['label'],
                'invoicedTotal' => $this->formatCents($row['invoicedCents']),
                'paidTotal' => $this->formatCents($row['paidCents']),
                'count' => $row['count'],
            ];
        }, $rows));

        usort($normalized, static function (array $a, array $b) use ($sortDescending): int {
            return $sortDescending
                ? strcmp($b['key'], $a['key'])
                : strcmp($a['key'], $b['key']);
        });

        return $normalized;
    }

    private function resolveClientNameFromInvoice(Invoice $invoice): string
    {
        $proposal = $invoice->getQuoteProposal();

        if ($proposal instanceof QuoteProposal) {
            $companyName = trim((string) $proposal->getClientCompanyName());
            if ('' !== $companyName) {
                return $companyName;
            }

            $fullName = trim((string) $proposal->getClientFullName());
            if ('' !== $fullName) {
                return $fullName;
            }
        }

        $client = $invoice->getClient();
        if ($client instanceof ClientProfile) {
            $companyName = trim((string) $client->getCompanyName());
            if ('' !== $companyName) {
                return $companyName;
            }

            $account = $client->getAccount();
            if ($account instanceof User) {
                $fullName = trim((string) $account->getFirstName() . ' ' . (string) $account->getLastName());
                if ('' !== $fullName) {
                    return $fullName;
                }
            }
        }

        return 'Client';
    }

    private function decimalToCents(string $value): int
    {
        return (int) round(((float) $value) * 100);
    }

    private function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function buildMonthChoices(): array
    {
        $choices = [];

        for ($month = 1; $month <= 12; ++$month) {
            $choices[] = [
                'value' => $month,
                'label' => self::MONTH_LABELS[$month] ?? (string) $month,
            ];
        }

        return $choices;
    }

    private function buildYearChoices(array $availableYears): array
    {
        krsort($availableYears);

        return array_values(array_map(
            static fn (string $year): array => ['value' => (int) $year, 'label' => $year],
            array_keys($availableYears)
        ));
    }

    private function buildMonthLabel(int $month, int $year): string
    {
        $monthLabel = self::MONTH_LABELS[$month] ?? sprintf('%02d', $month);

        return sprintf('%s %04d', $monthLabel, $year);
    }
}
