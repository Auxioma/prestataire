<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Invoice;
use App\Enum\InvoiceSourceTypeEnum;

final class InvoiceDocumentManager
{
    public function __construct(
        private readonly InvoicePdfGenerator $pdfGenerator,
        private readonly FacturXXmlBuilder $xmlBuilder,
        private readonly string $projectDir,
    ) {}

    public function refreshGeneratedDocuments(Invoice $invoice): void
    {
        if ($invoice->getId() === null || $invoice->getSourceType() === InvoiceSourceTypeEnum::EXTERNAL_IMPORT) {
            return;
        }

        $directory = $this->getGeneratedDirectory();

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $baseName = sprintf('invoice-%s-current', $invoice->getId());
        $pdfFileName = $baseName . '.pdf';
        $xmlFileName = $baseName . '.xml';
        $pdfPath = $directory . '/' . $pdfFileName;
        $xmlPath = $directory . '/' . $xmlFileName;

        file_put_contents($xmlPath, $this->xmlBuilder->build($invoice));

        file_put_contents($pdfPath, $this->pdfGenerator->generatePdfOutput($invoice, embeddedXmlPath: $xmlPath));

        $invoice
            ->setFacturXPdfName($pdfFileName)
            ->setFacturXXmlName($xmlFileName);
    }

    private function getGeneratedDirectory(): string
    {
        return $this->projectDir . '/var/uploads/invoices/generated';
    }
}
