<?php

declare(strict_types=1);

namespace App\Service\Subscription;

use App\Entity\Subscription\SubscriptionInvoice;

final class SubscriptionFacturXXmlBuilder
{
    private const NS_RSM = 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100';
    private const NS_RAM = 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100';
    private const NS_QDT = 'urn:un:unece:uncefact:data:standard:QualifiedDataType:100';
    private const NS_UDT = 'urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100';
    private const FACTURX_GUIDELINE = 'urn:cen.eu:en16931:2017';
    private const DEFAULT_UNIT_CODE = 'C62';
    private const SIREN_SCHEME_ID = '0002';
    private const PLATFORM_NAME = 'TrouveMoi SAS';
    private const PLATFORM_EMAIL = 'contact@trouvemoi.com';
    private const PLATFORM_VAT_NUMBER = 'FR 12 123456789';
    private const PLATFORM_SIREN = '123456789';
    private const PLATFORM_LATE_PAYMENT_TERMS = 'Penalites de retard exigibles en cas de paiement apres la date d\'echeance.';
    private const PLATFORM_RECOVERY_TERMS = 'Indemnite forfaitaire de 40 EUR pour frais de recouvrement en cas de retard de paiement.';
    private const PLATFORM_EARLY_PAYMENT_DISCOUNT_TERMS = 'Pas d\'escompte pour paiement anticipe.';

    public function build(SubscriptionInvoice $invoice): string
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
        $guideline->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:ID', self::FACTURX_GUIDELINE));
        $context->appendChild($guideline);
        $root->appendChild($context);
    }

    private function appendExchangedDocument(\DOMDocument $document, \DOMElement $root, SubscriptionInvoice $invoice): void
    {
        $header = $document->createElementNS(self::NS_RSM, 'rsm:ExchangedDocument');
        $header->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:ID', $this->resolveInvoiceNumber($invoice)));
        $header->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:TypeCode', '380'));

        $issueDate = $invoice->getPaidAt() ?? $invoice->getCreatedAt();
        $issueDateTime = $document->createElementNS(self::NS_RAM, 'ram:IssueDateTime');
        $dateTimeString = $this->createTextElementNS($document, self::NS_UDT, 'udt:DateTimeString', $issueDate->format('Ymd'));
        $dateTimeString->setAttribute('format', '102');
        $issueDateTime->appendChild($dateTimeString);
        $header->appendChild($issueDateTime);

        $note = $document->createElementNS(self::NS_RAM, 'ram:IncludedNote');
        $note->appendChild($this->createTextElementNS(
            $document,
            self::NS_RAM,
            'ram:Content',
            'Facture d’abonnement générée par TrouveMoi à partir des données de paiement synchronisées avec la plateforme.'
        ));
        $header->appendChild($note);
        $this->appendFrenchPaymentNotice($document, $header, self::PLATFORM_RECOVERY_TERMS, 'PMT');
        $this->appendFrenchPaymentNotice($document, $header, self::PLATFORM_LATE_PAYMENT_TERMS, 'PMD');
        $this->appendFrenchPaymentNotice($document, $header, self::PLATFORM_EARLY_PAYMENT_DISCOUNT_TERMS, 'AAB');

        $root->appendChild($header);
    }

    private function appendTradeTransaction(\DOMDocument $document, \DOMElement $root, SubscriptionInvoice $invoice): void
    {
        $transaction = $document->createElementNS(self::NS_RSM, 'rsm:SupplyChainTradeTransaction');
        $transaction->appendChild($this->buildLineItem($document, $invoice));
        $transaction->appendChild($this->buildHeaderAgreement($document, $invoice));
        $transaction->appendChild($this->buildHeaderDelivery($document, $invoice));
        $transaction->appendChild($this->buildHeaderSettlement($document, $invoice));
        $root->appendChild($transaction);
    }

    private function buildLineItem(\DOMDocument $document, SubscriptionInvoice $invoice): \DOMElement
    {
        $subscription = $invoice->getSubscription();
        $planName = $subscription?->getPlan()?->getName() ?: 'Abonnement TrouveMoi';
        $description = $planName;

        if ($invoice->getPeriodStart() instanceof \DateTimeInterface && $invoice->getPeriodEnd() instanceof \DateTimeInterface) {
            $description .= sprintf(
                ' - periode du %s au %s',
                $invoice->getPeriodStart()->format('d/m/Y'),
                $invoice->getPeriodEnd()->format('d/m/Y')
            );
        }

        $line = $document->createElementNS(self::NS_RAM, 'ram:IncludedSupplyChainTradeLineItem');

        $lineDocument = $document->createElementNS(self::NS_RAM, 'ram:AssociatedDocumentLineDocument');
        $lineDocument->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:LineID', '1'));
        $line->appendChild($lineDocument);

        $product = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedTradeProduct');
        $product->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:Name', $planName));
        $product->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:Description', $description));
        $line->appendChild($product);

        $agreement = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedLineTradeAgreement');
        $price = $document->createElementNS(self::NS_RAM, 'ram:NetPriceProductTradePrice');
        $price->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:ChargeAmount', $this->formatAmount($this->resolveSubtotal($invoice))));
        $agreement->appendChild($price);
        $line->appendChild($agreement);

        $delivery = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedLineTradeDelivery');
        $quantity = $this->createTextElementNS($document, self::NS_RAM, 'ram:BilledQuantity', '1.00');
        $quantity->setAttribute('unitCode', self::DEFAULT_UNIT_CODE);
        $delivery->appendChild($quantity);
        $line->appendChild($delivery);

        $settlement = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedLineTradeSettlement');
        $tax = $document->createElementNS(self::NS_RAM, 'ram:ApplicableTradeTax');
        $taxRate = $this->resolveVatRate($invoice);
        $tax->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:TypeCode', 'VAT'));
        $tax->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:CategoryCode', $taxRate > 0.0 ? 'S' : 'Z'));
        $tax->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:RateApplicablePercent', $this->formatAmount($taxRate)));
        $settlement->appendChild($tax);

        $lineSum = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedTradeSettlementLineMonetarySummation');
        $lineSum->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:LineTotalAmount', $this->formatAmount($this->resolveSubtotal($invoice))));
        $settlement->appendChild($lineSum);
        $line->appendChild($settlement);

        return $line;
    }

    private function buildHeaderAgreement(\DOMDocument $document, SubscriptionInvoice $invoice): \DOMElement
    {
        $agreement = $document->createElementNS(self::NS_RAM, 'ram:ApplicableHeaderTradeAgreement');

        $seller = $document->createElementNS(self::NS_RAM, 'ram:SellerTradeParty');
        $seller->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:Name', self::PLATFORM_NAME));
        $this->appendLegalOrganization($document, $seller, self::PLATFORM_SIREN);
        $this->appendPostalAddress($document, $seller, [
            'ram:PostcodeCode' => '75002',
            'ram:LineOne' => '15 rue de la Paix',
            'ram:CityName' => 'Paris',
            'ram:CountryID' => 'FR',
        ]);
        $this->appendElectronicCommunication($document, $seller, self::PLATFORM_EMAIL);
        $this->appendTaxRegistration($document, $seller, self::PLATFORM_VAT_NUMBER);
        $agreement->appendChild($seller);

        $prestataire = $invoice->getSubscription()?->getPrestataireProfile();
        $customer = $invoice->getSubscription()?->getCustomer();
        $buyer = $document->createElementNS(self::NS_RAM, 'ram:BuyerTradeParty');
        $buyer->appendChild($this->createTextElementNS(
            $document,
            self::NS_RAM,
            'ram:Name',
            $prestataire?->getCompanyName() ?: $prestataire?->getLegalName() ?: 'Prestataire'
        ));
        $this->appendLegalOrganization($document, $buyer, $this->extractSiren($prestataire?->getSiret()));
        $this->appendPostalAddress($document, $buyer, [
            'ram:PostcodeCode' => $prestataire?->getPostalCode(),
            'ram:LineOne' => $prestataire?->getAddress(),
            'ram:LineTwo' => $prestataire?->getAddressComplement(),
            'ram:CityName' => $prestataire?->getCity(),
            'ram:CountryID' => $this->normalizeCountryCode($prestataire?->getCountry()),
        ]);
        $this->appendElectronicCommunication(
            $document,
            $buyer,
            $customer?->getBillingEmail() ?: $prestataire?->getAccount()?->getEmail()
        );
        $this->appendTaxRegistration($document, $buyer, $prestataire?->getVatNumber());
        $agreement->appendChild($buyer);

        return $agreement;
    }

    private function buildHeaderDelivery(\DOMDocument $document, SubscriptionInvoice $invoice): \DOMElement
    {
        $delivery = $document->createElementNS(self::NS_RAM, 'ram:ApplicableHeaderTradeDelivery');
        $occurrence = $invoice->getPeriodEnd() ?? $invoice->getPaidAt() ?? $invoice->getCreatedAt();

        $event = $document->createElementNS(self::NS_RAM, 'ram:ActualDeliverySupplyChainEvent');
        $date = $document->createElementNS(self::NS_RAM, 'ram:OccurrenceDateTime');
        $dateTimeString = $this->createTextElementNS($document, self::NS_UDT, 'udt:DateTimeString', $occurrence->format('Ymd'));
        $dateTimeString->setAttribute('format', '102');
        $date->appendChild($dateTimeString);
        $event->appendChild($date);
        $delivery->appendChild($event);

        return $delivery;
    }

    private function buildHeaderSettlement(\DOMDocument $document, SubscriptionInvoice $invoice): \DOMElement
    {
        $settlement = $document->createElementNS(self::NS_RAM, 'ram:ApplicableHeaderTradeSettlement');
        $settlement->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:InvoiceCurrencyCode', $invoice->getCurrencyCode()));

        $taxRate = $this->resolveVatRate($invoice);
        $tax = $document->createElementNS(self::NS_RAM, 'ram:ApplicableTradeTax');
        $tax->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:CalculatedAmount', $this->formatAmount($invoice->getTaxAmount() ?: '0.00')));
        $tax->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:TypeCode', 'VAT'));
        $tax->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:BasisAmount', $this->formatAmount($this->resolveSubtotal($invoice))));
        $tax->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:CategoryCode', $taxRate > 0.0 ? 'S' : 'Z'));
        $tax->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:RateApplicablePercent', $this->formatAmount($taxRate)));
        $settlement->appendChild($tax);

        if ($invoice->getDueAt() instanceof \DateTimeInterface) {
            $terms = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedTradePaymentTerms');
            $terms->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:Description', 'Paiement de l’abonnement.'));
            $dueDate = $document->createElementNS(self::NS_RAM, 'ram:DueDateDateTime');
            $dateTimeString = $this->createTextElementNS($document, self::NS_UDT, 'udt:DateTimeString', $invoice->getDueAt()->format('Ymd'));
            $dateTimeString->setAttribute('format', '102');
            $dueDate->appendChild($dateTimeString);
            $terms->appendChild($dueDate);
            $settlement->appendChild($terms);
        }

        $summation = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedTradeSettlementHeaderMonetarySummation');
        $summation->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:LineTotalAmount', $this->formatAmount($this->resolveSubtotal($invoice))));
        $summation->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:TaxBasisTotalAmount', $this->formatAmount($this->resolveSubtotal($invoice))));
        $summation->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:TaxTotalAmount', $this->formatAmount($invoice->getTaxAmount() ?: '0.00')));
        $summation->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:GrandTotalAmount', $this->formatAmount($this->resolveTotal($invoice))));
        $summation->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:DuePayableAmount', $this->formatAmount($this->resolveTotal($invoice))));
        $settlement->appendChild($summation);

        return $settlement;
    }

    private function appendLegalOrganization(\DOMDocument $document, \DOMElement $parent, ?string $siren): void
    {
        if ($siren === null) {
            return;
        }

        $organization = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedLegalOrganization');
        $identifier = $this->createTextElementNS($document, self::NS_RAM, 'ram:ID', $siren);
        $identifier->setAttribute('schemeID', self::SIREN_SCHEME_ID);
        $organization->appendChild($identifier);
        $parent->appendChild($organization);
    }

    private function appendPostalAddress(\DOMDocument $document, \DOMElement $parent, array $parts): void
    {
        $orderedTags = [
            'ram:PostcodeCode',
            'ram:LineOne',
            'ram:LineTwo',
            'ram:LineThree',
            'ram:CityName',
            'ram:CountryID',
        ];

        $hasValue = false;
        foreach ($orderedTags as $tag) {
            if (isset($parts[$tag]) && null !== $parts[$tag] && '' !== trim((string) $parts[$tag])) {
                $hasValue = true;
                break;
            }
        }

        if (!$hasValue) {
            return;
        }

        $address = $document->createElementNS(self::NS_RAM, 'ram:PostalTradeAddress');

        foreach ($orderedTags as $tag) {
            $value = $parts[$tag] ?? null;
            if (null === $value || '' === trim((string) $value)) {
                continue;
            }

            $address->appendChild($this->createTextElementNS($document, self::NS_RAM, $tag, trim((string) $value)));
        }

        $parent->appendChild($address);
    }

    private function appendTaxRegistration(\DOMDocument $document, \DOMElement $parent, ?string $vatNumber): void
    {
        if (null === $vatNumber || '' === trim($vatNumber)) {
            return;
        }

        $taxRegistration = $document->createElementNS(self::NS_RAM, 'ram:SpecifiedTaxRegistration');
        $identifier = $this->createTextElementNS($document, self::NS_RAM, 'ram:ID', $this->normalizeVatNumber($vatNumber));
        $identifier->setAttribute('schemeID', 'VA');
        $taxRegistration->appendChild($identifier);
        $parent->appendChild($taxRegistration);
    }

    private function appendElectronicCommunication(\DOMDocument $document, \DOMElement $parent, ?string $email): void
    {
        $email = $this->normalizeEmail($email);
        if ($email === null) {
            return;
        }

        $communication = $document->createElementNS(self::NS_RAM, 'ram:URIUniversalCommunication');
        $identifier = $this->createTextElementNS($document, self::NS_RAM, 'ram:URIID', $email);
        $identifier->setAttribute('schemeID', 'EM');
        $communication->appendChild($identifier);
        $parent->appendChild($communication);
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
        $includedNote->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:Content', trim($content)));

        if ($subjectCode !== null) {
            $includedNote->appendChild($this->createTextElementNS($document, self::NS_RAM, 'ram:SubjectCode', $subjectCode));
        }

        return $includedNote;
    }

    private function createTextElementNS(\DOMDocument $document, string $namespace, string $name, string $value): \DOMElement
    {
        $element = $document->createElementNS($namespace, $name);
        $element->appendChild($document->createTextNode($value));

        return $element;
    }

    private function resolveInvoiceNumber(SubscriptionInvoice $invoice): string
    {
        return $invoice->getInvoiceNumber() ?: ('ABO-' . ($invoice->getId() ?? 'draft'));
    }

    private function resolveSubtotal(SubscriptionInvoice $invoice): string
    {
        return $invoice->getSubtotalAmount() ?: $this->resolveTotal($invoice);
    }

    private function resolveTotal(SubscriptionInvoice $invoice): string
    {
        return $invoice->getTotalAmount() ?: $invoice->getAmountPaid() ?: '0.00';
    }

    private function resolveVatRate(SubscriptionInvoice $invoice): float
    {
        $subtotal = (float) $this->resolveSubtotal($invoice);
        $taxAmount = (float) ($invoice->getTaxAmount() ?: '0');

        if ($subtotal <= 0.0 || $taxAmount <= 0.0) {
            return 0.0;
        }

        return round(($taxAmount / $subtotal) * 100, 2);
    }

    private function formatAmount(string|float|int $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function extractSiren(?string $siret): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $siret) ?: '';

        if (strlen($digits) < 9) {
            return null;
        }

        return substr($digits, 0, 9);
    }

    private function normalizeCountryCode(?string $country): ?string
    {
        $country = trim((string) $country);

        if ('' === $country) {
            return null;
        }

        $upper = mb_strtoupper($country);

        return match ($upper) {
            'FRANCE' => 'FR',
            default => strlen($upper) === 2 ? $upper : null,
        };
    }

    private function normalizeVatNumber(?string $vatNumber): string
    {
        return preg_replace('/\s+/', '', mb_strtoupper(trim((string) $vatNumber))) ?: '';
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = trim((string) $email);

        return $email !== '' ? mb_strtolower($email) : null;
    }

    private function hasTextContent(?string $value): bool
    {
        return trim((string) $value) !== '';
    }
}
