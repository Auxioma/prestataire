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

enum VerificationStatusEnum: string
{
    case NOT_VERIFIED = 'NOT_VERIFIED';
    case EMAIL_VERIFIED = 'EMAIL_VERIFIED';
    case PHONE_VERIFIED = 'PHONE_VERIFIED';
    case COMPANY_VERIFIED = 'COMPANY_VERIFIED';
    case DOCUMENTS_VERIFIED = 'DOCUMENTS_VERIFIED';
    case MANUALLY_VERIFIED = 'MANUALLY_VERIFIED';
}
