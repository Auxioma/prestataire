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
use App\Invoice\Entity\InvoiceItem;
use App\Invoice\Enum\InvoiceSourceTypeEnum;
use App\Quote\Entity\QuoteProposal;

final class InvoiceFactoryFromQuote
{
    public function __construct(
        private readonly InvoiceTotalsCalculator $totalsCalculator,
    ) {
    }

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
                $item = new InvoiceItem()
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
