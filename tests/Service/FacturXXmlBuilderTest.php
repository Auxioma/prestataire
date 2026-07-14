<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\QuoteProposal;
use App\Service\FacturXXmlBuilder;
use PHPUnit\Framework\TestCase;

final class FacturXXmlBuilderTest extends TestCase
{
    public function testBuildProducesEn16931GuidelineAndTaxBreakdowns(): void
    {
        $builder = new FacturXXmlBuilder();
        $invoice = $this->createInvoiceFixture();

        $xml = $builder->build($invoice);

        $document = new \DOMDocument();
        $document->loadXML($xml);
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('rsm', 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100');
        $xpath->registerNamespace('ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100');

        self::assertSame(
            'urn:cen.eu:en16931:2017',
            $xpath->evaluate('string(/rsm:CrossIndustryInvoice/rsm:ExchangedDocumentContext/ram:GuidelineSpecifiedDocumentContextParameter/ram:ID)')
        );
        self::assertSame(
            'B1',
            $xpath->evaluate('string(/rsm:CrossIndustryInvoice/rsm:ExchangedDocumentContext/ram:BusinessProcessSpecifiedDocumentContextParameter/ram:ID)')
        );

        self::assertCount(
            2,
            $xpath->query('/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:ApplicableHeaderTradeSettlement/ram:ApplicableTradeTax')
        );

        self::assertSame(
            '123456789',
            $xpath->evaluate('string(/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:ApplicableHeaderTradeAgreement/ram:SellerTradeParty/ram:SpecifiedLegalOrganization/ram:ID)')
        );
        self::assertSame(
            '987654321',
            $xpath->evaluate('string(/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:ApplicableHeaderTradeAgreement/ram:BuyerTradeParty/ram:SpecifiedLegalOrganization/ram:ID)')
        );

        self::assertSame(
            '12 rue du Chantier',
            $xpath->evaluate('string(/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:ApplicableHeaderTradeDelivery/ram:ShipToTradeParty/ram:PostalTradeAddress/ram:LineOne)')
        );
        self::assertCount(
            3,
            $xpath->query('/rsm:CrossIndustryInvoice/rsm:ExchangedDocument/ram:IncludedNote[ram:SubjectCode]')
        );
        self::assertSame(
            'PMT',
            $xpath->evaluate('string((/rsm:CrossIndustryInvoice/rsm:ExchangedDocument/ram:IncludedNote[ram:SubjectCode])[1]/ram:SubjectCode)')
        );
    }

    private function createInvoiceFixture(): Invoice
    {
        $quote = (new QuoteProposal())
            ->setProposalNumber('DEV-2026-00002')
            ->setPrestataireCompanyName('Acme Services')
            ->setPrestataireLegalName('Acme Services SARL')
            ->setPrestataireSiret('12345678900012')
            ->setPrestataireVatNumber('FR12123456789')
            ->setPrestataireAddress('10 rue de la Paix')
            ->setPrestataireAddressComplement('Batiment A')
            ->setPrestatairePostalCode('75002')
            ->setPrestataireCity('Paris')
            ->setPrestataireCountry('France')
            ->setClientCompanyName('Client Exemple')
            ->setClientFullName('Jean Client')
            ->setClientSiret('98765432100019')
            ->setClientBillingAddress('20 avenue du Test')
            ->setClientBillingPostalCode('69001')
            ->setClientBillingCity('Lyon')
            ->setClientBillingCountry('France')
            ->setClientInterventionAddress('12 rue du Chantier')
            ->setClientInterventionAddressComplement('Entree C')
            ->setClientInterventionPostalCode('33000')
            ->setClientInterventionCity('Bordeaux')
            ->setClientInterventionCountry('France');

        $invoice = (new Invoice())
            ->setQuoteProposal($quote)
            ->setInvoiceNumber('FAC-TEST-XML-001')
            ->setIssuedAt(new \DateTimeImmutable('2026-07-14 12:00:00'))
            ->setDueAt(new \DateTimeImmutable('2026-08-13 00:00:00'))
            ->setSubtotalHt('250.00')
            ->setTaxAmount('40.00')
            ->setTotalTtc('290.00')
            ->setTerms('Paiement à 30 jours')
            ->setFixedRecoveryCompensationTerms('Indemnite forfaitaire de 40 EUR pour frais de recouvrement en cas de retard de paiement.')
            ->setLatePaymentPenaltyTerms('Penalites de retard exigibles en cas de paiement apres la date d\'echeance.')
            ->setEarlyPaymentDiscountTerms('Pas d\'escompte pour paiement anticipe.');

        $invoice->addItem(
            (new InvoiceItem())
                ->setLabel('Ligne 20')
                ->setQuantity('1.00')
                ->setUnitPriceHt('200.00')
                ->setVatRate('20.00')
                ->setTotalHt('200.00')
                ->setPosition(1)
        );

        $invoice->addItem(
            (new InvoiceItem())
                ->setLabel('Ligne 10')
                ->setQuantity('1.00')
                ->setUnitPriceHt('50.00')
                ->setVatRate('10.00')
                ->setTotalHt('50.00')
                ->setPosition(2)
        );

        return $invoice;
    }
}
