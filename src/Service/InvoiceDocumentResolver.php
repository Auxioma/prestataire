<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Invoice;
use Vich\UploaderBundle\Storage\StorageInterface;

final class InvoiceDocumentResolver
{
    public const TYPE_EXTERNAL_PDF = 'external_pdf';
    public const TYPE_GENERATED_PDF = 'generated_pdf';

    public function __construct(
        private readonly StorageInterface $storage,
        private readonly string $projectDir,
    ) {}

    public function resolve(Invoice $invoice): ?InvoiceResolvedDocument
    {
        if ($invoice->isExternalImport() && $invoice->hasExternalPdf()) {
            $path = $this->storage->resolvePath($invoice, 'externalPdfFile');

            if ($path !== null && is_file($path)) {
                return new InvoiceResolvedDocument(
                    self::TYPE_EXTERNAL_PDF,
                    $this->buildDownloadFilename($invoice, 'externe'),
                    $path,
                    $invoice->getExternalPdfMimeType() ?: 'application/pdf',
                );
            }
        }

        if ($invoice->hasGeneratedPdf()) {
            $path = sprintf(
                '%s/var/uploads/invoices/generated/%s',
                $this->projectDir,
                $invoice->getFacturXPdfName()
            );

            if (is_file($path)) {
                return new InvoiceResolvedDocument(
                    self::TYPE_GENERATED_PDF,
                    $this->buildDownloadFilename($invoice, null),
                    $path,
                    'application/pdf',
                );
            }
        }

        return null;
    }

    public function getXmlPath(Invoice $invoice): ?string
    {
        if (!$invoice->getFacturXXmlName()) {
            return null;
        }

        $path = sprintf(
            '%s/var/uploads/invoices/generated/%s',
            $this->projectDir,
            $invoice->getFacturXXmlName()
        );

        return is_file($path) ? $path : null;
    }

    private function buildDownloadFilename(Invoice $invoice, ?string $suffix): string
    {
        $base = $invoice->getInvoiceNumber() ?: $invoice->getQuoteProposal()?->getPublicReference() ?: 'facture';

        return $suffix !== null
            ? sprintf('%s-%s.pdf', $base, $suffix)
            : sprintf('%s.pdf', $base);
    }
}
