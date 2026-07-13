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
        $dompdf->render();

        if ($embeddedXmlPath !== null && is_file($embeddedXmlPath)) {
            $canvas = $dompdf->getCanvas();

            if ($canvas instanceof CPDF) {
                $cpdf = $canvas->get_cpdf();
                $cpdf->addEmbeddedFile(
                    $embeddedXmlPath,
                    'factur-x.xml',
                    'Donnees XML Factur-X',
                    'application/xml',
                    [$cpdf->catalogId => 'Alternative']
                );
            }
        }

        return $dompdf->output();
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
