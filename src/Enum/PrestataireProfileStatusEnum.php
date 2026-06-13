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

enum PrestataireProfileStatusEnum: string
{
    case DRAFT = 'DRAFT';
    case PENDING_VALIDATION = 'PENDING_VALIDATION';
    case ACTIVE = 'ACTIVE';
    case INCOMPLETE = 'INCOMPLETE';
    case SUSPENDED = 'SUSPENDED';
    case REFUSED = 'REFUSED';
    case DELETED = 'DELETED';
}
