<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Invoice;
use App\Enum\InvoiceSourceTypeEnum;

final class FacturXXmlBuilder
{
    public function build(Invoice $invoice): string
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $root = $document->createElement('CrossIndustryInvoice');
        $root->setAttribute('generatedBy', 'TrouveMoi');
        $root->setAttribute('profile', 'factur-x-preparation-v1');
        $document->appendChild($root);

        $header = $document->createElement('DocumentHeader');
        $this->appendText($document, $header, 'InvoiceNumber', $invoice->getInvoiceNumber() ?: 'BROUILLON');
        $this->appendText($document, $header, 'Status', $invoice->getStatus()->value);
        $this->appendText($document, $header, 'SourceType', $invoice->getSourceType()->value);
        $this->appendText($document, $header, 'Currency', $invoice->getCurrency());
        $this->appendText($document, $header, 'IssueDate', $invoice->getIssuedAt()?->format('Y-m-d'));
        $this->appendText($document, $header, 'DueDate', $invoice->getDueAt()?->format('Y-m-d'));
        $root->appendChild($header);

        $quote = $invoice->getQuoteProposal();
        if ($quote !== null) {
            $quoteNode = $document->createElement('RelatedQuote');
            $this->appendText($document, $quoteNode, 'ProposalNumber', $quote->getProposalNumber());
            $this->appendText($document, $quoteNode, 'PublicReference', $quote->getPublicReference());
            $root->appendChild($quoteNode);
        }

        $seller = $document->createElement('Seller');
        $this->appendText($document, $seller, 'CompanyName', $quote?->getPrestataireCompanyName());
        $this->appendText($document, $seller, 'LegalName', $quote?->getPrestataireLegalName());
        $this->appendText($document, $seller, 'Siret', $quote?->getPrestataireSiret());
        $this->appendText($document, $seller, 'VatNumber', $quote?->getPrestataireVatNumber());
        $this->appendAddress($document, $seller, [
            'Street' => $quote?->getPrestataireAddress(),
            'Complement' => $quote?->getPrestataireAddressComplement(),
            'PostalCode' => $quote?->getPrestatairePostalCode(),
            'City' => $quote?->getPrestataireCity(),
            'Country' => $quote?->getPrestataireCountry(),
        ]);
        $root->appendChild($seller);

        $buyer = $document->createElement('Buyer');
        $this->appendText($document, $buyer, 'FullName', $quote?->getClientFullName());
        $this->appendText($document, $buyer, 'CompanyName', $quote?->getClientCompanyName());
        $this->appendText($document, $buyer, 'Email', $quote?->getClientEmail());
        $this->appendText($document, $buyer, 'Phone', $quote?->getClientPhone());
        $this->appendAddress($document, $buyer, [
            'Street' => $quote?->getClientBillingAddress() ?: $quote?->getClientInterventionAddress(),
            'Complement' => $quote?->getClientInterventionAddressComplement(),
            'PostalCode' => $quote?->getClientBillingPostalCode() ?: $quote?->getClientInterventionPostalCode(),
            'City' => $quote?->getClientBillingCity() ?: $quote?->getClientInterventionCity(),
            'Country' => $quote?->getClientBillingCountry() ?: $quote?->getClientInterventionCountry(),
        ]);
        $root->appendChild($buyer);

        $lines = $document->createElement('Lines');
        if ($invoice->getSourceType() !== InvoiceSourceTypeEnum::EXTERNAL_IMPORT) {
            foreach ($invoice->getItems() as $item) {
                $line = $document->createElement('Line');
                $this->appendText($document, $line, 'Position', (string) ($item->getPosition() ?? 0));
                $this->appendText($document, $line, 'Label', $item->getLabel());
                $this->appendText($document, $line, 'Description', $item->getDescription());
                $this->appendText($document, $line, 'Quantity', $item->getQuantity());
                $this->appendText($document, $line, 'UnitPriceHt', $item->getUnitPriceHt());
                $this->appendText($document, $line, 'VatRate', $item->getVatRate());
                $this->appendText($document, $line, 'TotalHt', $item->getTotalHt());
                $lines->appendChild($line);
            }
        }
        $root->appendChild($lines);

        $totals = $document->createElement('Totals');
        $this->appendText($document, $totals, 'SubtotalHt', $invoice->getSubtotalHt());
        $this->appendText($document, $totals, 'TaxAmount', $invoice->getTaxAmount());
        $this->appendText($document, $totals, 'TotalTtc', $invoice->getTotalTtc());
        $root->appendChild($totals);

        if ($invoice->getNotes() !== null || $invoice->getTerms() !== null) {
            $freeText = $document->createElement('FreeText');
            $this->appendText($document, $freeText, 'Notes', $invoice->getNotes());
            $this->appendText($document, $freeText, 'Terms', $invoice->getTerms());
            $this->appendText(
                $document,
                $freeText,
                'ComplianceNotice',
                'Document de preparation Factur-X genere par TrouveMoi. Ce service ne realise pas la transmission reglementaire officielle.'
            );
            $root->appendChild($freeText);
        }

        return $document->saveXML() ?: '';
    }

    private function appendAddress(\DOMDocument $document, \DOMElement $parent, array $parts): void
    {
        $address = $document->createElement('Address');

        foreach ($parts as $tag => $value) {
            $this->appendText($document, $address, $tag, $value);
        }

        $parent->appendChild($address);
    }

    private function appendText(\DOMDocument $document, \DOMElement $parent, string $tag, ?string $value): void
    {
        if ($value === null || trim($value) === '') {
            return;
        }

        $parent->appendChild($document->createElement($tag, $value));
    }
}
