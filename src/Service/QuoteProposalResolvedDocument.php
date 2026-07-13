<?php

declare(strict_types=1);

namespace App\Service;

final class QuoteProposalResolvedDocument
{
    public function __construct(
        private readonly string $type,
        private readonly string $downloadFilename,
        private readonly bool $storedFile,
        private readonly ?string $filesystemPath = null,
        private readonly ?string $mimeType = 'application/pdf',
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    public function getDownloadFilename(): string
    {
        return $this->downloadFilename;
    }

    public function isStoredFile(): bool
    {
        return $this->storedFile;
    }

    public function getFilesystemPath(): ?string
    {
        return $this->filesystemPath;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function isAcceptedDerivedPdf(): bool
    {
        return $this->type === QuoteProposalDocumentResolver::TYPE_ACCEPTED_PDF;
    }

    public function isExternalPdf(): bool
    {
        return $this->type === QuoteProposalDocumentResolver::TYPE_EXTERNAL_PDF;
    }

    public function isNativePdf(): bool
    {
        return $this->type === QuoteProposalDocumentResolver::TYPE_NATIVE_PDF;
    }
}
