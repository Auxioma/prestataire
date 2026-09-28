<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 AUXIOMA Web Agency.
 *
 * Projet : TrouveMoi
 *
 * Tous droits réservés.
 *
 * Ce fichier fait partie du projet TrouveMoi,
 * développé par AUXIOMA Web Agency.
 *
 * Toute reproduction, modification, distribution ou utilisation,
 * totale ou partielle, sans autorisation écrite préalable,
 * est strictement interdite.
 */

namespace App\Invoice\Service;

use App\Invoice\Entity\Invoice;
use App\Invoice\Enum\InvoiceSourceTypeEnum;

final class InvoiceDocumentManager
{
    public function __construct(
        private readonly InvoicePdfGenerator $pdfGenerator,
        private readonly FacturXXmlBuilder $xmlBuilder,
        private readonly string $projectDir,
    ) {
    }

    public function refreshGeneratedDocuments(Invoice $invoice): void
    {
        if (null === $invoice->getId() || InvoiceSourceTypeEnum::EXTERNAL_IMPORT === $invoice->getSourceType()) {
            return;
        }

        $directory = $this->getGeneratedDirectory();

        if (!is_dir($directory)) {
            mkdir($directory, 0o775, true);
        }

        $baseName = \sprintf('invoice-%s-current', $invoice->getId());
        $pdfFileName = $baseName.'.pdf';
        $xmlFileName = $baseName.'.xml';
        $pdfPath = $directory.'/'.$pdfFileName;
        $xmlPath = $directory.'/'.$xmlFileName;

        file_put_contents($xmlPath, $this->xmlBuilder->build($invoice));

        file_put_contents($pdfPath, $this->pdfGenerator->generatePdfOutput($invoice, embeddedXmlPath: $xmlPath));

        $invoice
            ->setFacturXPdfName($pdfFileName)
            ->setFacturXXmlName($xmlFileName);
    }

    private function getGeneratedDirectory(): string
    {
        return $this->projectDir.'/var/uploads/invoices/generated';
    }
}
