<?php

declare(strict_types=1);

namespace App\Service\Subscription;

use App\Entity\Subscription\SubscriptionInvoice;
use Dompdf\Adapter\CPDF;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

final class SubscriptionInvoicePdfGenerator
{
    private const FACTUR_X_FILENAME = 'factur-x.xml';
    private const FACTUR_X_DOCUMENT_TYPE = 'INVOICE';
    private const FACTUR_X_VERSION = '1.0';
    private const FACTUR_X_CONFORMANCE_LEVEL = 'EN 16931';
    private const FACTUR_X_XMP_NAMESPACE = 'urn:factur-x:pdfa:CrossIndustryDocument:invoice:1p0#';

    public function __construct(
        private readonly Environment $twig,
        private readonly SubscriptionFacturXXmlBuilder $xmlBuilder,
        private readonly KernelInterface $kernel,
    ) {}

    public function generatePdfOutput(
        SubscriptionInvoice $invoice,
        string $template = 'subscription/invoice_pdf.html.twig',
    ): string {
        $embeddedXmlPath = $this->createEmbeddedXmlFile($invoice);

        $html = $this->twig->render($template, [
            'invoice' => $invoice,
            'subscription' => $invoice->getSubscription(),
            'prestataire' => $invoice->getSubscription()?->getPrestataireProfile(),
            'customer' => $invoice->getSubscription()?->getCustomer(),
            'platform' => [
                'name' => 'TrouveMoi',
                'legal_name' => 'TrouveMoi SAS',
                'email' => 'contact@trouvemoi.com',
                'website' => 'www.trouvemoi.fr',
                'address_line_1' => '15 rue de la Paix',
                'postal_code' => '75002',
                'city' => 'Paris',
                'country' => 'France',
                'rcs' => '123 456 789',
                'vat_number' => 'FR 12 123456789',
                'phone' => '01 84 80 52 98',
                'logo_data_uri' => $this->resolvePlatformLogoDataUri(),
            ],
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->setIsPdfAEnabled($embeddedXmlPath !== null);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $this->applyDocumentMetadata($dompdf, $invoice);
        $dompdf->render();

        if ($embeddedXmlPath !== null) {
            $canvas = $dompdf->getCanvas();

            if ($canvas instanceof CPDF) {
                $cpdf = $canvas->get_cpdf();
                $cpdf->setAdditionalXmpRdf($this->buildFacturXXmpExtension());
                $cpdf->addEmbeddedFile(
                    $embeddedXmlPath,
                    self::FACTUR_X_FILENAME,
                    'Donnees XML Factur-X',
                    'application/xml',
                    [$cpdf->catalogId => 'Alternative']
                );
            }
        }

        $output = $dompdf->output();

        if ($embeddedXmlPath !== null && is_file($embeddedXmlPath)) {
            @unlink($embeddedXmlPath);
        }

        return $output;
    }

    private function applyDocumentMetadata(Dompdf $dompdf, SubscriptionInvoice $invoice): void
    {
        $invoiceNumber = $invoice->getInvoiceNumber() ?: ('ABO-' . ($invoice->getId() ?? 'draft'));

        $dompdf->addInfo('Title', sprintf('Facture abonnement %s', $invoiceNumber));
        $dompdf->addInfo('Author', 'TrouveMoi');
        $dompdf->addInfo('Creator', 'TrouveMoi');
        $dompdf->addInfo('Subject', sprintf('Facture d’abonnement %s', $invoiceNumber));
        $dompdf->addInfo('Keywords', 'Factur-X, EN16931, PDF/A-3, abonnement, trouvemoi');
    }

    private function createEmbeddedXmlFile(SubscriptionInvoice $invoice): ?string
    {
        $xmlContent = $this->xmlBuilder->build($invoice);

        if ('' === trim($xmlContent)) {
            return null;
        }

        $path = tempnam(sys_get_temp_dir(), 'subscription-facturx-');
        if ($path === false) {
            return null;
        }

        if (false === @file_put_contents($path, $xmlContent)) {
            @unlink($path);

            return null;
        }

        return $path;
    }

    private function resolvePlatformLogoDataUri(): ?string
    {
        $logoPath = $this->kernel->getProjectDir() . '/assets/images/logo_trouvemoipresta.png';

        if (!is_file($logoPath)) {
            return null;
        }

        $contents = @file_get_contents($logoPath);
        if ($contents === false) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode($contents);
    }

    private function buildFacturXXmpExtension(): string
    {
        return sprintf(
            <<<'XML'

<rdf:Description xmlns:pdfaExtension="http://www.aiim.org/pdfa/ns/extension/" xmlns:pdfaSchema="http://www.aiim.org/pdfa/ns/schema#" xmlns:pdfaProperty="http://www.aiim.org/pdfa/ns/property#" rdf:about="">
<pdfaExtension:schemas>
<rdf:Bag>
<rdf:li rdf:parseType="Resource">
<pdfaSchema:schema>Factur-X PDFA Extension Schema</pdfaSchema:schema>
<pdfaSchema:namespaceURI>%s</pdfaSchema:namespaceURI>
<pdfaSchema:prefix>fx</pdfaSchema:prefix>
<pdfaSchema:property>
<rdf:Seq>
<rdf:li rdf:parseType="Resource">
<pdfaProperty:name>DocumentFileName</pdfaProperty:name>
<pdfaProperty:valueType>Text</pdfaProperty:valueType>
<pdfaProperty:category>external</pdfaProperty:category>
<pdfaProperty:description>The name of the embedded XML document</pdfaProperty:description>
</rdf:li>
<rdf:li rdf:parseType="Resource">
<pdfaProperty:name>DocumentType</pdfaProperty:name>
<pdfaProperty:valueType>Text</pdfaProperty:valueType>
<pdfaProperty:category>external</pdfaProperty:category>
<pdfaProperty:description>The type of the hybrid document in capital letters, e.g. INVOICE or ORDER</pdfaProperty:description>
</rdf:li>
<rdf:li rdf:parseType="Resource">
<pdfaProperty:name>Version</pdfaProperty:name>
<pdfaProperty:valueType>Text</pdfaProperty:valueType>
<pdfaProperty:category>external</pdfaProperty:category>
<pdfaProperty:description>The Factur-X version</pdfaProperty:description>
</rdf:li>
<rdf:li rdf:parseType="Resource">
<pdfaProperty:name>ConformanceLevel</pdfaProperty:name>
<pdfaProperty:valueType>Text</pdfaProperty:valueType>
<pdfaProperty:category>external</pdfaProperty:category>
<pdfaProperty:description>The Factur-X conformance level</pdfaProperty:description>
</rdf:li>
</rdf:Seq>
</pdfaSchema:property>
</rdf:li>
</rdf:Bag>
</pdfaExtension:schemas>
</rdf:Description>
<rdf:Description xmlns:fx="%s" rdf:about="">
<fx:DocumentType>%s</fx:DocumentType>
<fx:DocumentFileName>%s</fx:DocumentFileName>
<fx:Version>%s</fx:Version>
<fx:ConformanceLevel>%s</fx:ConformanceLevel>
</rdf:Description>
XML,
            self::FACTUR_X_XMP_NAMESPACE,
            self::FACTUR_X_XMP_NAMESPACE,
            self::FACTUR_X_DOCUMENT_TYPE,
            self::FACTUR_X_FILENAME,
            self::FACTUR_X_VERSION,
            self::FACTUR_X_CONFORMANCE_LEVEL,
        );
    }
}
