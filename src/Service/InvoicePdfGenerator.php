<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\PrestataireProfile;
use Dompdf\Adapter\CPDF;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

final class InvoicePdfGenerator
{
    private const FACTUR_X_FILENAME = 'factur-x.xml';
    private const FACTUR_X_DOCUMENT_TYPE = 'INVOICE';
    private const FACTUR_X_VERSION = '1.0';
    private const FACTUR_X_CONFORMANCE_LEVEL = 'EN 16931';
    private const FACTUR_X_XMP_NAMESPACE = 'urn:factur-x:pdfa:CrossIndustryDocument:invoice:1p0#';

    public function __construct(
        private readonly Environment $twig,
        private readonly UploaderHelper $uploaderHelper,
        private readonly RequestStack $requestStack,
    ) {}

    public function generatePdfOutput(
        Invoice $invoice,
        string $template = 'invoice/pdf.html.twig',
        ?string $embeddedXmlPath = null,
    ): string
    {
        $html = $this->twig->render($template, [
            'invoice' => $invoice,
            'quote' => $invoice->getQuoteProposal(),
            'prestataireLogoUrl' => $this->resolvePrestataireLogoUrl($invoice),
            'prestataireSignatureUrl' => $this->resolvePrestataireSignatureUrl($invoice),
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

        if ($embeddedXmlPath !== null && is_file($embeddedXmlPath)) {
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

        return $dompdf->output();
    }

    private function applyDocumentMetadata(Dompdf $dompdf, Invoice $invoice): void
    {
        $quote = $invoice->getQuoteProposal();
        $invoiceNumber = $invoice->getInvoiceNumber() ?: 'Brouillon';
        $sellerName = $quote?->getPrestataireCompanyName() ?: $quote?->getPrestataireLegalName() ?: 'Prestataire';

        $dompdf->addInfo('Title', sprintf('Facture %s', $invoiceNumber));
        $dompdf->addInfo('Author', $sellerName);
        $dompdf->addInfo('Creator', 'TrouveMoi');
        $dompdf->addInfo('Subject', 'Facture electronique Factur-X');
        $dompdf->addInfo('Keywords', 'Factur-X, EN16931, PDF/A-3, facture electronique');
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

    private function resolvePrestataireLogoUrl(Invoice $invoice): ?string
    {
        $prestataire = $invoice->getPrestataire();

        if (!$prestataire instanceof PrestataireProfile || !$prestataire->getLogo()) {
            return null;
        }

        $relativePath = $this->uploaderHelper->asset($prestataire, 'logoFile');
        $request = $this->requestStack->getCurrentRequest();

        if ($relativePath === null || !$request instanceof Request) {
            return null;
        }

        return $request->getSchemeAndHttpHost() . $relativePath;
    }

    private function resolvePrestataireSignatureUrl(Invoice $invoice): ?string
    {
        $prestataire = $invoice->getPrestataire();

        if (!$prestataire instanceof PrestataireProfile || !$prestataire->getSignatureImage()) {
            return null;
        }

        $relativePath = $this->uploaderHelper->asset($prestataire, 'signatureImageFile');
        $request = $this->requestStack->getCurrentRequest();

        if ($relativePath === null || !$request instanceof Request) {
            return null;
        }

        return $request->getSchemeAndHttpHost() . $relativePath;
    }
}
