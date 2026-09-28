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

use App\Quote\Entity\QuoteProposal;
use Vich\UploaderBundle\Storage\StorageInterface;

final class QuoteProposalDocumentResolver
{
    public const TYPE_ACCEPTED_PDF = 'accepted_pdf';
    public const TYPE_EXTERNAL_PDF = 'external_pdf';
    public const TYPE_NATIVE_PDF = 'native_pdf';

    public function __construct(
        private readonly StorageInterface $storage,
        private readonly string $projectDir,
    ) {
    }

    public function resolve(QuoteProposal $proposal): QuoteProposalResolvedDocument
    {
        if ($proposal->hasAcceptedPdf()) {
            return new QuoteProposalResolvedDocument(
                self::TYPE_ACCEPTED_PDF,
                $this->buildDownloadFilename($proposal, 'accepte'),
                true,
                $this->getAcceptedPdfPath($proposal),
                $proposal->getAcceptedPdfMimeType() ?: 'application/pdf',
            );
        }

        if ($proposal->usesExternalPdfDocument()) {
            return new QuoteProposalResolvedDocument(
                self::TYPE_EXTERNAL_PDF,
                $this->buildDownloadFilename($proposal, 'externe'),
                true,
                $this->getExternalPdfPath($proposal),
                $proposal->getExternalPdfMimeType() ?: 'application/pdf',
            );
        }

        return new QuoteProposalResolvedDocument(
            self::TYPE_NATIVE_PDF,
            $this->buildDownloadFilename($proposal, null),
            false,
            null,
            'application/pdf',
        );
    }

    public function shouldRenderNativeDetails(QuoteProposal $proposal): bool
    {
        return !$proposal->getDocumentMode()->isExternalPdf();
    }

    public function getExternalPdfPath(QuoteProposal $proposal): ?string
    {
        if (!$proposal->hasExternalPdf()) {
            return null;
        }

        return $this->storage->resolvePath($proposal, 'externalPdfFile');
    }

    public function getAcceptedPdfPath(QuoteProposal $proposal): ?string
    {
        if (!$proposal->hasAcceptedPdf()) {
            return null;
        }

        return \sprintf(
            '%s/var/uploads/quote-proposals/accepted/%s',
            $this->projectDir,
            $proposal->getAcceptedPdfName()
        );
    }

    private function buildDownloadFilename(QuoteProposal $proposal, ?string $suffix): string
    {
        $base = $proposal->getProposalNumber() ?: $proposal->getPublicReference() ?: 'devis';

        return null !== $suffix
            ? \sprintf('%s-%s.pdf', $base, $suffix)
            : \sprintf('%s.pdf', $base);
    }
}
