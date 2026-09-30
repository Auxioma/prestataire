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

namespace App\Website\Enum;

enum ContactHelpCategoryEnum: string
{
    case TECHNICAL_SUPPORT = 'technical_support';
    case SERVICE_OR_OFFER = 'service_or_offer';
    case MALFUNCTION_OR_ERROR = 'malfunction_or_error';
    case IMPROVEMENT_SUGGESTION = 'improvement_suggestion';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::TECHNICAL_SUPPORT => 'Support technique (bug, problème d’affichage, connexion)',
            self::SERVICE_OR_OFFER => 'Question sur un service / une offre',
            self::MALFUNCTION_OR_ERROR => 'Signaler un dysfonctionnement / une erreur',
            self::IMPROVEMENT_SUGGESTION => 'Suggestion d’amélioration',
            self::OTHER => 'Autre demande',
        };
    }
}
