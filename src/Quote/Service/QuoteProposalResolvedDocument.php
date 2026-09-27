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

namespace App\Quote\Service;

final class QuoteProposalResolvedDocument
{
    public function __construct(
        private readonly string $type,
        private readonly string $downloadFilename,
        private readonly bool $storedFile,
        private readonly ?string $filesystemPath = null,
        private readonly ?string $mimeType = 'application/pdf',
    ) {
    }

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
        return QuoteProposalDocumentResolver::TYPE_ACCEPTED_PDF === $this->type;
    }

    public function isExternalPdf(): bool
    {
        return QuoteProposalDocumentResolver::TYPE_EXTERNAL_PDF === $this->type;
    }

    public function isNativePdf(): bool
    {
        return QuoteProposalDocumentResolver::TYPE_NATIVE_PDF === $this->type;
    }
}
