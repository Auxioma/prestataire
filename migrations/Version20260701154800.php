<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260701154800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remplace la contrainte unique globale quote_request_id + prestataire_id par un index unique partiel sur les devis non supprimés';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quote_proposal DROP CONSTRAINT IF EXISTS uniq_quote_proposal_request_prestataire');
        $this->addSql('DROP INDEX IF EXISTS uniq_quote_proposal_request_prestataire');
        $this->addSql('CREATE UNIQUE INDEX uniq_quote_proposal_request_prestataire_active ON quote_proposal (quote_request_id, prestataire_id) WHERE deleted_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_quote_proposal_request_prestataire_active');
        $this->addSql('ALTER TABLE quote_proposal ADD CONSTRAINT uniq_quote_proposal_request_prestataire UNIQUE (quote_request_id, prestataire_id)');
    }
}