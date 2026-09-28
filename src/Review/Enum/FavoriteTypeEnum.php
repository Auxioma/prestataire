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

namespace App\Review\Enum;

enum FavoriteTypeEnum: string
{
    case PRESTATAIRE = 'prestataire';
    case PRESTATION = 'prestation';
    case BON_PLAN = 'bon_plan';

    public function getLabel(): string
    {
        return match ($this) {
            self::PRESTATAIRE => 'Prestataire',
            self::PRESTATION => 'Prestation',
            self::BON_PLAN => 'Bon plan',
        };
    }
}
