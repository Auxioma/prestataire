<?php

namespace App\Enum;

enum QuoteProposalDocumentModeEnum: string
{
    case PLATFORM = 'platform';
    case EXTERNAL_PDF = 'external_pdf';

    public function getLabel(): string
    {
        return match ($this) {
            self::PLATFORM => 'Devis généré par la plateforme',
            self::EXTERNAL_PDF => 'PDF externe fourni par le prestataire',
        };
    }

    public function isPlatform(): bool
    {
        return $this === self::PLATFORM;
    }

    public function isExternalPdf(): bool
    {
        return $this === self::EXTERNAL_PDF;
    }
}
