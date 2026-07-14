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
    private const FACTURX_GUIDELINE = 'urn:cen.eu:en16931:2017';
    private const DEFAULT_BUSINESS_PROCESS = 'B1';
    private const DEFAULT_UNIT_CODE = 'C62';
    private const SIREN_SCHEME_ID = '0002';

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

        $businessProcess = $document->createElementNS(self::NS_RAM, 'ram:BusinessProcessSpecifiedDocumentContextParameter');
        $businessProcess->appendChild($document->createElementNS(self::NS_RAM, 'ram:ID', self::DEFAULT_BUSINESS_PROCESS));
        $context->appendChild($businessProcess);

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
            $header->appendChild($this->buildIncludedNote($document, $invoice->getNotes()));
        }

        $this->appendFrenchPaymentNotice($document, $header, $invoice->getFixedRecoveryCompensationTerms(), 'PMT');
        $this->appendFrenchPaymentNotice($document, $header, $invoice->getLatePaymentPenaltyTerms(), 'PMD');
        $this->appendFrenchPaymentNotice($document, $header, $invoice->getEarlyPaymentDiscountTerms(), 'AAB');

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
                $quantity = $document->createElementNS(self::NS_RAM, 'ram:BilledQuantity', $this->formatAmount($item->getQuantity()));
                $quantity->setAttribute('unitCode', self::DEFAULT_UNIT_CODE);
                $delivery->appendChild($quantity);
                $line->appendChild($delivery);

                $settlement = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedLineTradeSettlement');
                $tax = $document->createElementNS(self::NS_RAM, 'ram:ApplicableTradeTax');
                $tax->appendChild($document->createElementNS(self::NS_RAM, 'ram:TypeCode', 'VAT'));
                $tax->appendChild($document->createElementNS(self::NS_RAM, 'ram:CategoryCode', $this->resolveTaxCategoryCode($item->getVatRate())));
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
        $this->appendLegalOrganization($document, $seller, $this->extractSiren($quote?->getPrestataireSiret()));
        $this->appendPostalAddress($document, $seller, [
            'ram:LineOne' => $quote?->getPrestataireAddress(),
            'ram:LineTwo' => $quote?->getPrestataireAddressComplement(),
            'ram:PostcodeCode' => $quote?->getPrestatairePostalCode(),
            'ram:CityName' => $quote?->getPrestataireCity(),
            'ram:CountryID' => $this->normalizeCountryCode($quote?->getPrestataireCountry()),
        ]);
        $this->appendTaxRegistration($document, $seller, $quote?->getPrestataireVatNumber());
        $agreement->appendChild($seller);

        $buyer = $document->createElementNS(self::NS_RAM, 'ram:BuyerTradeParty');
        $buyer->appendChild($document->createElementNS(self::NS_RAM, 'ram:Name', $quote?->getClientCompanyName() ?: $quote?->getClientFullName() ?: 'Client'));
        $this->appendLegalOrganization($document, $buyer, $this->extractSiren($quote?->getClientSiret()));
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
        $quote = $invoice->getQuoteProposal();
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

        if ($this->hasDistinctDeliveryAddress($quote)) {
            $shipTo = $document->createElementNS(self::NS_RAM, 'ram:ShipToTradeParty');
            $shipTo->appendChild($document->createElementNS(self::NS_RAM, 'ram:Name', $quote?->getClientCompanyName() ?: $quote?->getClientFullName() ?: 'Client'));
            $this->appendPostalAddress($document, $shipTo, [
                'ram:LineOne' => $quote?->getClientInterventionAddress(),
                'ram:LineTwo' => $quote?->getClientInterventionAddressComplement(),
                'ram:PostcodeCode' => $quote?->getClientInterventionPostalCode(),
                'ram:CityName' => $quote?->getClientInterventionCity(),
                'ram:CountryID' => $this->normalizeCountryCode($quote?->getClientInterventionCountry()),
            ]);
            $delivery->appendChild($shipTo);
        }

        return $delivery;
    }

    private function buildHeaderSettlement(\DOMDocument $document, Invoice $invoice, ?QuoteProposal $quote): \DOMElement
    {
        $settlement = $document->createElementNS(self::NS_RAM, 'ram:ApplicableHeaderTradeSettlement');
        $settlement->appendChild($document->createElementNS(self::NS_RAM, 'ram:InvoiceCurrencyCode', $invoice->getCurrency()));

        foreach ($this->buildTaxBreakdowns($invoice) as $taxBreakdown) {
            $tax = $document->createElementNS(self::NS_RAM, 'ram:ApplicableTradeTax');
            $tax->appendChild($document->createElementNS(self::NS_RAM, 'ram:CalculatedAmount', $taxBreakdown['taxAmount']));
            $tax->appendChild($document->createElementNS(self::NS_RAM, 'ram:TypeCode', 'VAT'));
            $tax->appendChild($document->createElementNS(self::NS_RAM, 'ram:BasisAmount', $taxBreakdown['basisAmount']));
            $tax->appendChild($document->createElementNS(self::NS_RAM, 'ram:CategoryCode', $taxBreakdown['categoryCode']));
            $tax->appendChild($document->createElementNS(self::NS_RAM, 'ram:RateApplicablePercent', $taxBreakdown['rate']));
            $settlement->appendChild($tax);
        }

        if ($this->hasPaymentTerms($invoice)) {
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

    private function appendLegalOrganization(\DOMDocument $document, \DOMElement $parent, ?string $siren): void
    {
        if ($siren === null) {
            return;
        }

        $organization = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedLegalOrganization');
        $identifier = $document->createElementNS(self::NS_RAM, 'ram:ID', $siren);
        $identifier->setAttribute('schemeID', self::SIREN_SCHEME_ID);
        $organization->appendChild($identifier);
        $parent->appendChild($organization);
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

    private function hasPaymentTerms(Invoice $invoice): bool
    {
        return $invoice->getDueAt() instanceof \DateTimeInterface
            || $this->hasTextContent($invoice->getTerms())
            || $this->hasTextContent($invoice->getLatePaymentPenaltyTerms())
            || $this->hasTextContent($invoice->getFixedRecoveryCompensationTerms())
            || $this->hasTextContent($invoice->getEarlyPaymentDiscountTerms());
    }

    private function appendPaymentTermDescription(\DOMDocument $document, \DOMElement $terms, ?string $description): void
    {
        if (!$this->hasTextContent($description)) {
            return;
        }

        $terms->appendChild($document->createElementNS(self::NS_RAM, 'ram:Description', $description));
    }

    private function appendFrenchPaymentNotice(\DOMDocument $document, \DOMElement $header, ?string $content, string $subjectCode): void
    {
        if (!$this->hasTextContent($content)) {
            return;
        }

        $header->appendChild($this->buildIncludedNote($document, $content, $subjectCode));
    }

    private function buildIncludedNote(\DOMDocument $document, string $content, ?string $subjectCode = null): \DOMElement
    {
        $includedNote = $document->createElementNS(self::NS_RAM, 'ram:IncludedNote');
        $includedNote->appendChild($document->createElementNS(self::NS_RAM, 'ram:Content', $content));

        if ($subjectCode !== null) {
            $includedNote->appendChild($document->createElementNS(self::NS_RAM, 'ram:SubjectCode', $subjectCode));
        }

        return $includedNote;
    }

    /**
     * @return list<array{rate: string, basisAmount: string, taxAmount: string, categoryCode: string}>
     */
    private function buildTaxBreakdowns(Invoice $invoice): array
    {
        $breakdowns = [];

        foreach ($invoice->getItems() as $item) {
            $rate = $this->formatAmount($item->getVatRate());
            $categoryCode = $this->resolveTaxCategoryCode($item->getVatRate());
            $key = $categoryCode . '|' . $rate;
            $basisAmount = $this->formatAmount($item->getTotalHt());
            $taxAmount = $this->calculateLineTaxAmount($basisAmount, $rate);

            if (!isset($breakdowns[$key])) {
                $breakdowns[$key] = [
                    'rate' => $rate,
                    'basisAmount' => '0.00',
                    'taxAmount' => '0.00',
                    'categoryCode' => $categoryCode,
                ];
            }

            $breakdowns[$key]['basisAmount'] = bcadd($breakdowns[$key]['basisAmount'], $basisAmount, 2);
            $breakdowns[$key]['taxAmount'] = bcadd($breakdowns[$key]['taxAmount'], $taxAmount, 2);
        }

        if ($breakdowns === []) {
            $rate = $invoice->getTaxAmount() === '0.00' ? '0.00' : $this->formatAmount(0);

            return [[
                'rate' => $rate,
                'basisAmount' => $this->formatAmount($invoice->getSubtotalHt()),
                'taxAmount' => $this->formatAmount($invoice->getTaxAmount()),
                'categoryCode' => $this->resolveTaxCategoryCode($rate),
            ]];
        }

        return array_values(array_map(fn (array $breakdown): array => [
            'rate' => $breakdown['rate'],
            'basisAmount' => $breakdown['basisAmount'],
            'taxAmount' => $breakdown['taxAmount'],
            'categoryCode' => $breakdown['categoryCode'],
        ], $breakdowns));
    }

    private function calculateLineTaxAmount(string $basisAmount, string $rate): string
    {
        return bcmul($basisAmount, bcdiv($rate, '100', 4), 2);
    }

    private function resolveTaxCategoryCode(null|string|int|float $rate): string
    {
        return ((float) $rate) > 0 ? 'S' : 'Z';
    }

    private function extractSiren(?string $siret): ?string
    {
        if ($siret === null) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $siret);

        if (!is_string($normalized) || strlen($normalized) < 9) {
            return null;
        }

        return substr($normalized, 0, 9);
    }

    private function hasDistinctDeliveryAddress(?QuoteProposal $quote): bool
    {
        if (!$quote instanceof QuoteProposal) {
            return false;
        }

        $deliveryAddress = $this->normalizeForComparison($quote->getClientInterventionAddress());
        $deliveryPostalCode = $this->normalizeForComparison($quote->getClientInterventionPostalCode());
        $deliveryCity = $this->normalizeForComparison($quote->getClientInterventionCity());
        $deliveryCountry = $this->normalizeForComparison($quote->getClientInterventionCountry());

        if ($deliveryAddress === null && $deliveryPostalCode === null && $deliveryCity === null && $deliveryCountry === null) {
            return false;
        }

        return $deliveryAddress !== $this->normalizeForComparison($quote->getClientBillingAddress())
            || $deliveryPostalCode !== $this->normalizeForComparison($quote->getClientBillingPostalCode())
            || $deliveryCity !== $this->normalizeForComparison($quote->getClientBillingCity())
            || $deliveryCountry !== $this->normalizeForComparison($quote->getClientBillingCountry());
    }

    private function normalizeForComparison(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim(mb_strtolower($value));

        return $normalized === '' ? null : $normalized;
    }

    private function hasTextContent(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
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
