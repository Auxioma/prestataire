<?php

declare(strict_types=1);

namespace App\Service;

final class InvoiceResolvedDocument
{
    public function __construct(
        private readonly string $type,
        private readonly string $downloadFilename,
        private readonly string $filesystemPath,
        private readonly string $mimeType = 'application/pdf',
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    public function getDownloadFilename(): string
    {
        return $this->downloadFilename;
    }

    public function getFilesystemPath(): string
    {
        return $this->filesystemPath;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function isExternalPdf(): bool
    {
        return $this->type === InvoiceDocumentResolver::TYPE_EXTERNAL_PDF;
    }

    public function isGeneratedPdf(): bool
    {
        return $this->type === InvoiceDocumentResolver::TYPE_GENERATED_PDF;
    }
}
