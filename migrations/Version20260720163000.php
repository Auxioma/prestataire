<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table d idempotence des evenements webhook Stripe.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE stripe_webhook_event (id BIGSERIAL NOT NULL, stripe_event_id VARCHAR(255) NOT NULL, event_type VARCHAR(120) NOT NULL, processed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, payload JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_stripe_webhook_event_id ON stripe_webhook_event (stripe_event_id)');
        $this->addSql('CREATE INDEX idx_stripe_webhook_event_type ON stripe_webhook_event (event_type)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE stripe_webhook_event');
    }
}
