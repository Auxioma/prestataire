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

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929213500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le suivi persistant des notifications e-mail liées aux factures d’abonnement.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subscription_invoice ADD lifecycle_notification_sent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE subscription_invoice
            SET lifecycle_notification_sent_at = COALESCE(paid_at, updated_at, created_at, CURRENT_TIMESTAMP)
            WHERE status = 'paid'
              AND billing_reason IN ('subscription_create', 'subscription_update', 'subscription_cycle')
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subscription_invoice DROP lifecycle_notification_sent_at');
    }
}
