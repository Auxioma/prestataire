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

namespace App\Prestataire\Enum;

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
