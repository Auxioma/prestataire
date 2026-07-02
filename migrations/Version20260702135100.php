<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260702135100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime l’index uniq_quote_proposal_request_prestataire_active devenu hors mapping';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_quote_proposal_request_prestataire_active');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX uniq_quote_proposal_request_prestataire_active ON quote_proposal (quote_request_id, prestataire_id) WHERE (deleted_at IS NULL)');
    }
}