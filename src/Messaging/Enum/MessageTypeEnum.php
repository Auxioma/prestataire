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

namespace App\Messaging\Enum;

enum MessageTypeEnum: string
{
    case SYSTEM = 'system';
    case USER = 'user';

    public function getLabel(): string
    {
        return match ($this) {
            self::SYSTEM => 'Système',
            self::USER => 'Utilisateur',
        };
    }
}
