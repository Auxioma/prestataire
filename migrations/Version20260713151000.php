<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713151000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Lot 2: per-prestataire chronological numbering for quotes and invoices.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quote_proposal ADD proposal_sequence_number INT DEFAULT NULL');
        $this->addSql('ALTER TABLE invoice ADD invoice_sequence_number INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_quote_proposal_prestataire_sequence ON quote_proposal (prestataire_id, proposal_sequence_number)');
        $this->addSql('CREATE UNIQUE INDEX uniq_invoice_prestataire_sequence ON invoice (prestataire_id, invoice_sequence_number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_quote_proposal_prestataire_sequence');
        $this->addSql('DROP INDEX uniq_invoice_prestataire_sequence');
        $this->addSql('ALTER TABLE quote_proposal DROP proposal_sequence_number');
        $this->addSql('ALTER TABLE invoice DROP invoice_sequence_number');
    }
}
