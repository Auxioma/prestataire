<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260702113052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // $this->addSql('DROP INDEX uniq_quote_proposal_request_prestataire_active');
        $this->addSql('ALTER TABLE quote_proposal ADD archived_by_prestataire_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE quote_request ADD archived_by_client_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quote_proposal DROP archived_by_prestataire_at');
        // $this->addSql('CREATE UNIQUE INDEX uniq_quote_proposal_request_prestataire_active ON quote_proposal (quote_request_id, prestataire_id) WHERE (deleted_at IS NULL)');
        $this->addSql('ALTER TABLE quote_request DROP archived_by_client_at');
    }
}
