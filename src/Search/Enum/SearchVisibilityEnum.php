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

namespace App\Search\Enum;

enum SearchVisibilityEnum: string
{
    case NORMAL = 'NORMAL';
    case BOOSTED = 'BOOSTED';
    case PREMIUM = 'PREMIUM';
    case HIDDEN = 'HIDDEN';
}
