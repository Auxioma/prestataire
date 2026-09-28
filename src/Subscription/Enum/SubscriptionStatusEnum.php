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

namespace App\Subscription\Enum;

enum SubscriptionStatusEnum: string
{
    case INCOMPLETE = 'incomplete';
    case INCOMPLETE_EXPIRED = 'incomplete_expired';
    case TRIALING = 'trialing';
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case UNPAID = 'unpaid';
    case CANCELED = 'canceled';
    case PAUSED = 'paused';

    public function getLabel(): string
    {
        return match ($this) {
            self::INCOMPLETE => 'Création incomplète',
            self::INCOMPLETE_EXPIRED => 'Création expirée',
            self::TRIALING => 'Période d’essai',
            self::ACTIVE => 'Actif',
            self::PAST_DUE => 'Paiement en retard',
            self::UNPAID => 'Impayé',
            self::CANCELED => 'Résilié',
            self::PAUSED => 'Suspendu',
        };
    }

    public function isUsable(): bool
    {
        return \in_array($this, [self::TRIALING, self::ACTIVE], true);
    }
}
