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

final class InvoiceResolvedDocument
{
    public function __construct(
        private readonly string $type,
        private readonly string $downloadFilename,
        private readonly string $filesystemPath,
        private readonly string $mimeType = 'application/pdf',
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
        return InvoiceDocumentResolver::TYPE_EXTERNAL_PDF === $this->type;
    }

    public function isGeneratedPdf(): bool
    {
        return InvoiceDocumentResolver::TYPE_GENERATED_PDF === $this->type;
    }
}
