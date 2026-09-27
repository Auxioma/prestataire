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

namespace App\Quote\Enum;

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
        return self::PLATFORM === $this;
    }

    public function isExternalPdf(): bool
    {
        return self::EXTERNAL_PDF === $this;
    }
}
