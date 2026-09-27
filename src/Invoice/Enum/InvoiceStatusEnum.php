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

namespace App\Invoice\Enum;

enum InvoiceStatusEnum: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::ISSUED => 'Émise',
        };
    }

    public function isDraft(): bool
    {
        return self::DRAFT === $this;
    }

    public function isIssued(): bool
    {
        return self::ISSUED === $this;
    }
}
