<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\QuoteProposal;
use App\Enum\InvoiceSourceTypeEnum;

final class FacturXXmlBuilder
{
    private const NS_RSM = 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100';
    private const NS_RAM = 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100';
    private const NS_QDT = 'urn:un:unece:uncefact:data:standard:QualifiedDataType:100';
    private const NS_UDT = 'urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100';
    private const FACTURX_GUIDELINE = 'urn:factur-x.eu:1p0:minimum';

    public function build(Invoice $invoice): string
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $root = $document->createElementNS(self::NS_RSM, 'rsm:CrossIndustryInvoice');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ram', self::NS_RAM);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:qdt', self::NS_QDT);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:udt', self::NS_UDT);
        $document->appendChild($root);

        $this->appendDocumentContext($document, $root);
        $this->appendExchangedDocument($document, $root, $invoice);
        $this->appendTradeTransaction($document, $root, $invoice);

        return $document->saveXML() ?: '';
    }

    private function appendDocumentContext(\DOMDocument $document, \DOMElement $root): void
    {
        $context = $document->createElementNS(self::NS_RSM, 'rsm:ExchangedDocumentContext');
        $guideline = $document->createElementNS(self::NS_RAM, 'ram:GuidelineSpecifiedDocumentContextParameter');
        $guideline->appendChild($document->createElementNS(self::NS_RAM, 'ram:ID', self::FACTURX_GUIDELINE));
        $context->appendChild($guideline);
        $root->appendChild($context);
    }

    private function appendExchangedDocument(\DOMDocument $document, \DOMElement $root, Invoice $invoice): void
    {
        $header = $document->createElementNS(self::NS_RSM, 'rsm:ExchangedDocument');
        $header->appendChild($document->createElementNS(self::NS_RAM, 'ram:ID', $invoice->getInvoiceNumber() ?: 'BROUILLON'));
        $header->appendChild($document->createElementNS(self::NS_RAM, 'ram:TypeCode', '380'));

        $issueDate = $invoice->getIssuedAt() ?? $invoice->getCreatedAt();
        if ($issueDate instanceof \DateTimeInterface) {
            $issueDateTime = $document->createElementNS(self::NS_RAM, 'ram:IssueDateTime');
            $dateTimeString = $document->createElementNS(self::NS_UDT, 'udt:DateTimeString', $issueDate->format('Ymd'));
            $dateTimeString->setAttribute('format', '102');
            $issueDateTime->appendChild($dateTimeString);
            $header->appendChild($issueDateTime);
        }

        if ($invoice->getNotes() !== null && trim($invoice->getNotes()) !== '') {
            $includedNote = $document->createElementNS(self::NS_RAM, 'ram:IncludedNote');
            $includedNote->appendChild($document->createElementNS(self::NS_RAM, 'ram:Content', $invoice->getNotes()));
            $header->appendChild($includedNote);
        }

        $root->appendChild($header);
    }

    private function appendTradeTransaction(\DOMDocument $document, \DOMElement $root, Invoice $invoice): void
    {
        $transaction = $document->createElementNS(self::NS_RSM, 'rsm:SupplyChainTradeTransaction');

        if ($invoice->getSourceType() !== InvoiceSourceTypeEnum::EXTERNAL_IMPORT) {
            foreach ($invoice->getItems() as $item) {
                $line = $document->createElementNS(self::NS_RAM, 'ram:IncludedSupplyChainTradeLineItem');

                $lineDocument = $document->createElementNS(self::NS_RAM, 'ram:AssociatedDocumentLineDocument');
                $lineDocument->appendChild($document->createElementNS(self::NS_RAM, 'ram:LineID', (string) ($item->getPosition() ?? 0)));
                $line->appendChild($lineDocument);

                $product = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedTradeProduct');
                $product->appendChild($document->createElementNS(self::NS_RAM, 'ram:Name', $item->getLabel() ?: 'Ligne'));
                if ($item->getDescription() !== null && trim($item->getDescription()) !== '') {
                    $product->appendChild($document->createElementNS(self::NS_RAM, 'ram:Description', $item->getDescription()));
                }
                $line->appendChild($product);

                $agreement = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedLineTradeAgreement');
                $price = $document->createElementNS(self::NS_RAM, 'ram:NetPriceProductTradePrice');
                $price->appendChild($document->createElementNS(self::NS_RAM, 'ram:ChargeAmount', $this->formatAmount($item->getUnitPriceHt())));
                $agreement->appendChild($price);
                $line->appendChild($agreement);

                $delivery = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedLineTradeDelivery');
                $delivery->appendChild($document->createElementNS(self::NS_RAM, 'ram:BilledQuantity', $this->formatAmount($item->getQuantity())));
                $line->appendChild($delivery);

                $settlement = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedLineTradeSettlement');
                $tax = $document->createElementNS(self::NS_RAM, 'ram:ApplicableTradeTax');
                $tax->appendChild($document->createElementNS(self::NS_RAM, 'ram:TypeCode', 'VAT'));
                $tax->appendChild($document->createElementNS(self::NS_RAM, 'ram:CategoryCode', 'S'));
                $tax->appendChild($document->createElementNS(self::NS_RAM, 'ram:RateApplicablePercent', $this->formatAmount($item->getVatRate())));
                $settlement->appendChild($tax);

                $lineSum = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedTradeSettlementLineMonetarySummation');
                $lineSum->appendChild($document->createElementNS(self::NS_RAM, 'ram:LineTotalAmount', $this->formatAmount($item->getTotalHt())));
                $settlement->appendChild($lineSum);
                $line->appendChild($settlement);

                $transaction->appendChild($line);
            }
        }

        $quote = $invoice->getQuoteProposal();
        $transaction->appendChild($this->buildHeaderAgreement($document, $quote));
        $transaction->appendChild($this->buildHeaderDelivery($document, $invoice));
        $transaction->appendChild($this->buildHeaderSettlement($document, $invoice, $quote));

        $root->appendChild($transaction);
    }

    private function buildHeaderAgreement(\DOMDocument $document, ?QuoteProposal $quote): \DOMElement
    {
        $agreement = $document->createElementNS(self::NS_RAM, 'ram:ApplicableHeaderTradeAgreement');

        $seller = $document->createElementNS(self::NS_RAM, 'ram:SellerTradeParty');
        $seller->appendChild($document->createElementNS(self::NS_RAM, 'ram:Name', $quote?->getPrestataireCompanyName() ?: $quote?->getPrestataireLegalName() ?: 'Prestataire'));
        $this->appendPostalAddress($document, $seller, [
            'ram:LineOne' => $quote?->getPrestataireAddress(),
            'ram:PostcodeCode' => $quote?->getPrestatairePostalCode(),
            'ram:CityName' => $quote?->getPrestataireCity(),
            'ram:CountryID' => $this->normalizeCountryCode($quote?->getPrestataireCountry()),
        ]);
        $this->appendTaxRegistration($document, $seller, $quote?->getPrestataireVatNumber());
        $agreement->appendChild($seller);

        $buyer = $document->createElementNS(self::NS_RAM, 'ram:BuyerTradeParty');
        $buyer->appendChild($document->createElementNS(self::NS_RAM, 'ram:Name', $quote?->getClientCompanyName() ?: $quote?->getClientFullName() ?: 'Client'));
        $this->appendPostalAddress($document, $buyer, [
            'ram:LineOne' => $quote?->getClientBillingAddress() ?: $quote?->getClientInterventionAddress(),
            'ram:PostcodeCode' => $quote?->getClientBillingPostalCode() ?: $quote?->getClientInterventionPostalCode(),
            'ram:CityName' => $quote?->getClientBillingCity() ?: $quote?->getClientInterventionCity(),
            'ram:CountryID' => $this->normalizeCountryCode($quote?->getClientBillingCountry() ?: $quote?->getClientInterventionCountry()),
        ]);
        $agreement->appendChild($buyer);

        if ($quote?->getProposalNumber() !== null && trim($quote->getProposalNumber()) !== '') {
            $referencedDocument = $document->createElementNS(self::NS_RAM, 'ram:BuyerOrderReferencedDocument');
            $referencedDocument->appendChild($document->createElementNS(self::NS_RAM, 'ram:IssuerAssignedID', $quote->getProposalNumber()));
            $agreement->appendChild($referencedDocument);
        }

        return $agreement;
    }

    private function buildHeaderDelivery(\DOMDocument $document, Invoice $invoice): \DOMElement
    {
        $delivery = $document->createElementNS(self::NS_RAM, 'ram:ApplicableHeaderTradeDelivery');
        $occurrence = $invoice->getIssuedAt() ?? $invoice->getCreatedAt();

        if ($occurrence instanceof \DateTimeInterface) {
            $event = $document->createElementNS(self::NS_RAM, 'ram:ActualDeliverySupplyChainEvent');
            $date = $document->createElementNS(self::NS_RAM, 'ram:OccurrenceDateTime');
            $dateTimeString = $document->createElementNS(self::NS_UDT, 'udt:DateTimeString', $occurrence->format('Ymd'));
            $dateTimeString->setAttribute('format', '102');
            $date->appendChild($dateTimeString);
            $event->appendChild($date);
            $delivery->appendChild($event);
        }

        return $delivery;
    }

    private function buildHeaderSettlement(\DOMDocument $document, Invoice $invoice, ?QuoteProposal $quote): \DOMElement
    {
        $settlement = $document->createElementNS(self::NS_RAM, 'ram:ApplicableHeaderTradeSettlement');
        $settlement->appendChild($document->createElementNS(self::NS_RAM, 'ram:InvoiceCurrencyCode', $invoice->getCurrency()));

        $tax = $document->createElementNS(self::NS_RAM, 'ram:ApplicableTradeTax');
        $tax->appendChild($document->createElementNS(self::NS_RAM, 'ram:CalculatedAmount', $this->formatAmount($invoice->getTaxAmount())));
        $tax->appendChild($document->createElementNS(self::NS_RAM, 'ram:TypeCode', 'VAT'));
        $tax->appendChild($document->createElementNS(self::NS_RAM, 'ram:BasisAmount', $this->formatAmount($invoice->getSubtotalHt())));
        $tax->appendChild($document->createElementNS(self::NS_RAM, 'ram:CategoryCode', 'S'));
        $tax->appendChild($document->createElementNS(self::NS_RAM, 'ram:RateApplicablePercent', $this->resolveVatRate($invoice)));
        $settlement->appendChild($tax);

        if ($invoice->getDueAt() instanceof \DateTimeInterface || ($invoice->getTerms() !== null && trim($invoice->getTerms()) !== '')) {
            $terms = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedTradePaymentTerms');

            if ($invoice->getDueAt() instanceof \DateTimeInterface) {
                $dueDate = $document->createElementNS(self::NS_RAM, 'ram:DueDateDateTime');
                $dateTimeString = $document->createElementNS(self::NS_UDT, 'udt:DateTimeString', $invoice->getDueAt()->format('Ymd'));
                $dateTimeString->setAttribute('format', '102');
                $dueDate->appendChild($dateTimeString);
                $terms->appendChild($dueDate);
            }

            if ($invoice->getTerms() !== null && trim($invoice->getTerms()) !== '') {
                $terms->appendChild($document->createElementNS(self::NS_RAM, 'ram:Description', $invoice->getTerms()));
            }

            $settlement->appendChild($terms);
        }

        $summation = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedTradeSettlementHeaderMonetarySummation');
        $summation->appendChild($document->createElementNS(self::NS_RAM, 'ram:LineTotalAmount', $this->formatAmount($invoice->getSubtotalHt())));
        $summation->appendChild($document->createElementNS(self::NS_RAM, 'ram:TaxBasisTotalAmount', $this->formatAmount($invoice->getSubtotalHt())));
        $summation->appendChild($document->createElementNS(self::NS_RAM, 'ram:TaxTotalAmount', $this->formatAmount($invoice->getTaxAmount())));
        $summation->appendChild($document->createElementNS(self::NS_RAM, 'ram:GrandTotalAmount', $this->formatAmount($invoice->getTotalTtc())));
        $summation->appendChild($document->createElementNS(self::NS_RAM, 'ram:DuePayableAmount', $this->formatAmount($invoice->getTotalTtc())));
        $settlement->appendChild($summation);

        return $settlement;
    }

    private function appendPostalAddress(\DOMDocument $document, \DOMElement $parent, array $parts): void
    {
        $values = array_filter($parts, static fn (?string $value): bool => $value !== null && trim($value) !== '');
        if ($values === []) {
            return;
        }

        $address = $document->createElementNS(self::NS_RAM, 'ram:PostalTradeAddress');
        foreach ($values as $tag => $value) {
            $address->appendChild($document->createElementNS(self::NS_RAM, $tag, $value));
        }

        $parent->appendChild($address);
    }

    private function appendTaxRegistration(\DOMDocument $document, \DOMElement $parent, ?string $vatNumber): void
    {
        if ($vatNumber === null || trim($vatNumber) === '') {
            return;
        }

        $taxRegistration = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedTaxRegistration');
        $taxId = $document->createElementNS(self::NS_RAM, 'ram:ID', $vatNumber);
        $taxId->setAttribute('schemeID', 'VA');
        $taxRegistration->appendChild($taxId);
        $parent->appendChild($taxRegistration);
    }

    private function resolveVatRate(Invoice $invoice): string
    {
        foreach ($invoice->getItems() as $item) {
            if ($item->getVatRate() !== null) {
                return $this->formatAmount($item->getVatRate());
            }
        }

        return '0.00';
    }

    private function normalizeCountryCode(?string $country): ?string
    {
        if ($country === null || trim($country) === '') {
            return null;
        }

        $normalized = strtoupper(trim($country));

        return match ($normalized) {
            'FRANCE' => 'FR',
            default => strlen($normalized) === 2 ? $normalized : $normalized,
        };
    }

    private function formatAmount(null|string|int|float $value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        return number_format((float) $value, 2, '.', '');
    }
}
