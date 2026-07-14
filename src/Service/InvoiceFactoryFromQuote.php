<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\QuoteProposal;
use App\Enum\InvoiceSourceTypeEnum;

final class InvoiceFactoryFromQuote
{
    public function __construct(
        private readonly InvoiceTotalsCalculator $totalsCalculator,
    ) {}

    public function createFromAcceptedQuote(QuoteProposal $proposal): Invoice
    {
        $invoice = new Invoice();
        $invoice
            ->setQuoteProposal($proposal)
            ->setQuoteRequest($proposal->getQuoteRequest())
            ->setPrestataire($proposal->getPrestataire())
            ->setClient($proposal->getClient())
            ->setCurrency($proposal->getCurrency() ?: 'EUR')
            ->setDueAt($this->resolveDefaultDueAt($proposal))
            ->setNotes($proposal->getNotes())
            ->setTerms($proposal->getTerms())
            ->setLatePaymentPenaltyTerms($proposal->getLatePaymentPenaltyTerms())
            ->setFixedRecoveryCompensationTerms($proposal->getFixedRecoveryCompensationTerms())
            ->setEarlyPaymentDiscountTerms($proposal->getEarlyPaymentDiscountTerms())
            ->setSourceType(
                $proposal->usesExternalPdfDocument()
                    ? InvoiceSourceTypeEnum::MANUAL_FROM_EXTERNAL_QUOTE
                    : InvoiceSourceTypeEnum::GENERATED_FROM_QUOTE
            );

        if (!$proposal->usesExternalPdfDocument()) {
            foreach ($proposal->getItems() as $proposalItem) {
                $item = (new InvoiceItem())
                    ->setLabel($proposalItem->getLabel())
                    ->setDescription($proposalItem->getDescription())
                    ->setQuantity($proposalItem->getQuantity())
                    ->setUnitPriceHt($proposalItem->getUnitPriceHt())
                    ->setVatRate($proposalItem->getVatRate())
                    ->setPosition($proposalItem->getPosition());

                $invoice->addItem($item);
            }
        }

        return $this->totalsCalculator->recalculate($invoice);
    }

    private function resolveDefaultDueAt(QuoteProposal $proposal): \DateTimeImmutable
    {
        $base = $proposal->getAcceptedAt() instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($proposal->getAcceptedAt())
            : new \DateTimeImmutable();

        return $base->modify('+30 days');
    }
}
