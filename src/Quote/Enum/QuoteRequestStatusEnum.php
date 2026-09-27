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

namespace App\Quote\Enum;

enum QuoteRequestStatusEnum: string
{
    case SUBMITTED = 'submitted';
    case ACCEPTED = 'accepted';
    case ANSWERED = 'answered';
    case DENIED = 'denied';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::SUBMITTED => 'Demande envoyée',
            self::ACCEPTED => 'Étude de la demande acceptée',
            self::ANSWERED => 'Réponse reçue',
            self::DENIED => 'Demande refusée',
            self::CLOSED => 'Clôturée',
            self::CANCELLED => 'Annulée',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::SUBMITTED => 'warning',
            self::ACCEPTED => 'primary',
            self::ANSWERED => 'info',
            self::DENIED => 'secondary',
            self::CLOSED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
