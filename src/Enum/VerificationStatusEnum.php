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

enum VerificationStatusEnum: string
{
    case NOT_VERIFIED = 'NOT_VERIFIED';
    case EMAIL_VERIFIED = 'EMAIL_VERIFIED';
    case PHONE_VERIFIED = 'PHONE_VERIFIED';
    case COMPANY_VERIFIED = 'COMPANY_VERIFIED';
    case DOCUMENTS_VERIFIED = 'DOCUMENTS_VERIFIED';
    case MANUALLY_VERIFIED = 'MANUALLY_VERIFIED';
}
