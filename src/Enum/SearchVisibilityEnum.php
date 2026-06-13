<?php

/**
 * Copyright(c) 2026 Trouve moi
 *
 * Ce fichier fait partie d’un projet développé par Auxioma Web Agency.
 * Tous droits réservés.
 *
 * Ce code source est la propriété exclusive de Auxioma Web Agency.
 * Toute reproduction, modification, distribution ou utilisation sans autorisation préalable est interdite.
 */

namespace App\Enum;

enum SearchVisibilityEnum: string
{
    case NORMAL = 'NORMAL';
    case BOOSTED = 'BOOSTED';
    case PREMIUM = 'PREMIUM';
    case HIDDEN = 'HIDDEN';
}
