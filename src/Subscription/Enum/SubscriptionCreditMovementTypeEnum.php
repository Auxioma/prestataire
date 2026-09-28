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

enum SubscriptionCreditMovementTypeEnum: string
{
    case RENEWAL_GRANT = 'renewal_grant';
    case UPGRADE_GRANT = 'upgrade_grant';
    case ADMIN_MANUAL_GRANT = 'admin_manual_grant';
    case ADMIN_MANUAL_DEBIT = 'admin_manual_debit';
    case WELCOME_GRANT = 'welcome_grant';
    case QUOTE_RESPONSE_CONSUMPTION = 'quote_response_consumption';
    case PERIOD_EXPIRATION = 'period_expiration';
    case CORRECTION = 'correction';

    public function getLabel(): string
    {
        return match ($this) {
            self::RENEWAL_GRANT => 'Attribution au renouvellement',
            self::UPGRADE_GRANT => 'Attribution après montée en gamme',
            self::ADMIN_MANUAL_GRANT => 'Ajout manuel administrateur',
            self::ADMIN_MANUAL_DEBIT => 'Retrait manuel administrateur',
            self::WELCOME_GRANT => 'Bonus de bienvenue',
            self::QUOTE_RESPONSE_CONSUMPTION => 'Consommation pour réponse à un devis',
            self::PERIOD_EXPIRATION => 'Expiration de fin de période',
            self::CORRECTION => 'Correction manuelle',
        };
    }

    public function isDebit(): bool
    {
        return \in_array($this, [
            self::ADMIN_MANUAL_DEBIT,
            self::QUOTE_RESPONSE_CONSUMPTION,
            self::PERIOD_EXPIRATION,
        ], true);
    }
}
