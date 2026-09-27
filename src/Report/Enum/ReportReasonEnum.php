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

namespace App\Report\Enum;

enum ReportReasonEnum: string
{
    case INAPPROPRIATE_BEHAVIOR = 'inappropriate_behavior';
    case HARASSMENT = 'harassment';
    case SCAM_OR_FRAUD = 'scam_or_fraud';
    case FAKE_OR_MISLEADING_CONTENT = 'fake_or_misleading_content';
    case SPAM = 'spam';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::INAPPROPRIATE_BEHAVIOR => 'Comportement inapproprié',
            self::HARASSMENT => 'Harcèlement ou propos abusifs',
            self::SCAM_OR_FRAUD => 'Arnaque ou fraude présumée',
            self::FAKE_OR_MISLEADING_CONTENT => 'Contenu faux ou trompeur',
            self::SPAM => 'Spam ou sollicitation abusive',
            self::OTHER => 'Autre motif',
        };
    }
}
