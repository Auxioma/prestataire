<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\QuoteProposal;
use App\Enum\InvoiceSourceTypeEnum;
use App\Enum\InvoiceStatusEnum;
use App\Service\FacturXXmlBuilder;
use App\Service\InvoicePdfGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Storage\StorageInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

final class InvoicePdfGeneratorTest extends TestCase
{
    public function testGeneratedPdfEmbedsFacturXXml(): void
    {
        $xmlBuilder = new FacturXXmlBuilder();
        $pdfGenerator = new InvoicePdfGenerator(
            new Environment(new FilesystemLoader(__DIR__ . '/../../templates')),
            new UploaderHelper(new class implements StorageInterface {
                public function upload(object $obj, PropertyMapping $mapping): void {}
                public function remove(object $obj, PropertyMapping $mapping): ?bool { return null; }
                public function resolvePath(object|array $obj, ?string $fieldName = null, ?string $className = null, ?bool $relative = false): ?string { return null; }
                public function resolveUri(object|array $obj, ?string $fieldName = null, ?string $className = null): ?string { return null; }
                public function resolveStream(object|array $obj, ?string $fieldName = null, ?string $className = null) { return null; }
            }),
            new RequestStack(),
        );

        $invoice = $this->createInvoiceFixture();
        $xmlContent = $xmlBuilder->build($invoice);

        self::assertStringContainsString('<CrossIndustryInvoice', $xmlContent);
        self::assertStringContainsString('<InvoiceNumber>FAC-TEST-001</InvoiceNumber>', $xmlContent);

        $xmlPath = tempnam(sys_get_temp_dir(), 'facturx-test-');
        if ($xmlPath === false) {
            self::fail('Impossible de créer un fichier temporaire pour le test Factur-X.');
        }

        file_put_contents($xmlPath, $xmlContent);

        try {
            $pdfOutput = $pdfGenerator->generatePdfOutput($invoice, embeddedXmlPath: $xmlPath);

            self::assertStringContainsString('/EmbeddedFiles', $pdfOutput);
            self::assertStringContainsString('factur-x.xml', $pdfOutput);
            self::assertStringContainsString('/AFRelationship /Alternative', $pdfOutput);
        } finally {
            @unlink($xmlPath);
        }
    }

    private function createInvoiceFixture(): Invoice
    {
        $quote = (new QuoteProposal())
            ->setPublicReference('DEV-TEST-001')
            ->setProposalNumber('DEV-2026-TEST')
            ->setTitle('Devis de test')
            ->setPrestataireCompanyName('Acme Services')
            ->setPrestataireLegalName('Acme Services SARL')
            ->setPrestataireAddress('10 rue de la Paix')
            ->setPrestatairePostalCode('75002')
            ->setPrestataireCity('Paris')
            ->setPrestataireCountry('France')
            ->setClientFullName('Jean Client')
            ->setClientCompanyName('Client Exemple')
            ->setClientEmail('client@example.test')
            ->setClientPhone('0102030405')
            ->setClientBillingAddress('20 avenue du Test')
            ->setClientBillingPostalCode('69001')
            ->setClientBillingCity('Lyon')
            ->setClientBillingCountry('France')
            ->setClientInterventionAddress('20 avenue du Test')
            ->setClientInterventionAddressComplement('Batiment B')
            ->setClientInterventionPostalCode('69001')
            ->setClientInterventionCity('Lyon')
            ->setClientInterventionCountry('France');

        $item = (new InvoiceItem())
            ->setLabel('Prestation de test')
            ->setDescription('Exemple de ligne de facture')
            ->setQuantity('2.00')
            ->setUnitPriceHt('100.00')
            ->setVatRate('20.00')
            ->setTotalHt('200.00')
            ->setPosition(1);

        return (new Invoice())
            ->setQuoteProposal($quote)
            ->setSourceType(InvoiceSourceTypeEnum::GENERATED_FROM_QUOTE)
            ->setStatus(InvoiceStatusEnum::ISSUED)
            ->setInvoiceNumber('FAC-TEST-001')
            ->setIssuedAt(new \DateTimeImmutable('2026-07-13 12:00:00'))
            ->setDueAt(new \DateTimeImmutable('2026-08-12 00:00:00'))
            ->setSubtotalHt('200.00')
            ->setTaxAmount('40.00')
            ->setTotalTtc('240.00')
            ->setTerms('Paiement à 30 jours')
            ->setNotes('Facture de test')
            ->addItem($item);
    }
}
