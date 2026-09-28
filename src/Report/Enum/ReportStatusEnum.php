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

enum ReportStatusEnum: string
{
    case NEW = 'new';
    case IN_REVIEW = 'in_review';
    case RESOLVED = 'resolved';
    case DISMISSED = 'dismissed';

    public function getLabel(): string
    {
        return match ($this) {
            self::NEW => 'Nouveau',
            self::IN_REVIEW => 'En cours',
            self::RESOLVED => 'Traité',
            self::DISMISSED => 'Classé sans suite',
        };
    }
}
