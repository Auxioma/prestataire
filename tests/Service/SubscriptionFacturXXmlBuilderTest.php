<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\PrestataireProfile;
use App\Entity\Subscription\PrestataireSubscription;
use App\Entity\Subscription\SubscriptionCustomer;
use App\Entity\Subscription\SubscriptionInvoice;
use App\Entity\Subscription\SubscriptionPlan;
use App\Entity\User;
use App\Enum\SubscriptionBillingPeriodEnum;
use App\Enum\SubscriptionInvoiceStatusEnum;
use App\Service\Subscription\SubscriptionFacturXXmlBuilder;
use horstoeko\stringmanagement\PathUtils;
use horstoeko\zugferd\ZugferdSettings;
use PHPUnit\Framework\TestCase;

final class SubscriptionFacturXXmlBuilderTest extends TestCase
{
    public function testBuildProducesXsdValidEn16931Xml(): void
    {
        $builder = new SubscriptionFacturXXmlBuilder();
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
            '75002',
            $xpath->evaluate('string(/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:ApplicableHeaderTradeAgreement/ram:SellerTradeParty/ram:PostalTradeAddress/ram:PostcodeCode)')
        );
        self::assertSame(
            'FR12123456789',
            $xpath->evaluate('string(/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:ApplicableHeaderTradeAgreement/ram:SellerTradeParty/ram:SpecifiedTaxRegistration/ram:ID)')
        );
        self::assertSame(
            'contact@trouvemoi.com',
            $xpath->evaluate('string(/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:ApplicableHeaderTradeAgreement/ram:SellerTradeParty/ram:URIUniversalCommunication/ram:URIID)')
        );
        self::assertSame(
            'billing@example.test',
            $xpath->evaluate('string(/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:ApplicableHeaderTradeAgreement/ram:BuyerTradeParty/ram:URIUniversalCommunication/ram:URIID)')
        );
        self::assertCount(
            3,
            $xpath->query('/rsm:CrossIndustryInvoice/rsm:ExchangedDocument/ram:IncludedNote[ram:SubjectCode]')
        );

        $xsd = PathUtils::combineAllPaths(ZugferdSettings::getSchemaDirectory(), 'FACTUR-X_EN16931.xsd');
        libxml_use_internal_errors(true);

        self::assertTrue($document->schemaValidate($xsd), implode("\n", array_map(
            static fn (\LibXMLError $error): string => sprintf('[line %d] %s : %s', $error->line, $error->code, trim($error->message)),
            libxml_get_errors()
        )));

        libxml_clear_errors();
        libxml_use_internal_errors(false);
    }

    private function createInvoiceFixture(): SubscriptionInvoice
    {
        $user = (new User())
            ->setEmail('buyer@example.test');

        $prestataire = (new PrestataireProfile())
            ->setAccount($user)
            ->setCompanyName('Acme Services')
            ->setLegalName('Acme Services SARL')
            ->setAddress('10 rue de la Paix')
            ->setAddressComplement('Batiment A')
            ->setPostalCode('75002')
            ->setCity('Paris')
            ->setCountry('France')
            ->setSiret('98765432100019')
            ->setVatNumber('FR12 123456789');

        $customer = (new SubscriptionCustomer())
            ->setPrestataireProfile($prestataire)
            ->setStripeCustomerId('cus_test_001')
            ->setBillingEmail('billing@example.test');

        $plan = (new SubscriptionPlan())
            ->setCode('pro')
            ->setName('Abonnement Pro')
            ->setMonthlyAmount('49.00');

        $subscription = (new PrestataireSubscription())
            ->setPrestataireProfile($prestataire)
            ->setCustomer($customer)
            ->setPlan($plan)
            ->setBillingPeriod(SubscriptionBillingPeriodEnum::MONTHLY)
            ->setCurrentPeriodStart(new \DateTimeImmutable('2026-07-01 00:00:00'))
            ->setCurrentPeriodEnd(new \DateTimeImmutable('2026-07-31 23:59:59'));

        return (new SubscriptionInvoice())
            ->setSubscription($subscription)
            ->setStripeInvoiceId('in_sub_test_001')
            ->setStripePaymentIntentId('pi_sub_test_001')
            ->setInvoiceNumber('FA-ABO-TEST-001')
            ->setCurrency('eur')
            ->setSubtotalAmount('49.00')
            ->setTaxAmount('0.00')
            ->setTotalAmount('49.00')
            ->setAmountPaid('49.00')
            ->setAmountRemaining('0.00')
            ->setStatus(SubscriptionInvoiceStatusEnum::PAID)
            ->setBillingReason('subscription_cycle')
            ->setPeriodStart(new \DateTimeImmutable('2026-07-01 00:00:00'))
            ->setPeriodEnd(new \DateTimeImmutable('2026-07-31 23:59:59'))
            ->setDueAt(new \DateTimeImmutable('2026-07-03 00:00:00'))
            ->setPaidAt(new \DateTimeImmutable('2026-07-02 12:00:00'))
            ->setCreatedAt(new \DateTimeImmutable('2026-07-01 09:00:00'));
    }
}
