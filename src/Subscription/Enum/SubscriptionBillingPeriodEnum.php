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

enum SubscriptionBillingPeriodEnum: string
{
    case MONTHLY = 'monthly';
    case ANNUAL = 'annual';

    public function getLabel(): string
    {
        return match ($this) {
            self::MONTHLY => 'Mensuel',
            self::ANNUAL => 'Annuel',
        };
    }

    public function getMonthsCount(): int
    {
        return match ($this) {
            self::MONTHLY => 1,
            self::ANNUAL => 12,
        };
    }
}
