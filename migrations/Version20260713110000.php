<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le support des devis PDF externes et des PDF acceptes derives sur quote_proposal.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE quote_proposal ADD document_mode VARCHAR(30) DEFAULT 'platform' NOT NULL");
        $this->addSql('ALTER TABLE quote_proposal ADD external_pdf_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE quote_proposal ADD external_pdf_original_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE quote_proposal ADD external_pdf_mime_type VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE quote_proposal ADD external_pdf_size INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quote_proposal ADD external_pdf_uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE quote_proposal ADD accepted_pdf_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE quote_proposal ADD accepted_pdf_original_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE quote_proposal ADD accepted_pdf_mime_type VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE quote_proposal ADD accepted_pdf_size INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quote_proposal ADD accepted_pdf_generated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quote_proposal DROP document_mode');
        $this->addSql('ALTER TABLE quote_proposal DROP external_pdf_name');
        $this->addSql('ALTER TABLE quote_proposal DROP external_pdf_original_name');
        $this->addSql('ALTER TABLE quote_proposal DROP external_pdf_mime_type');
        $this->addSql('ALTER TABLE quote_proposal DROP external_pdf_size');
        $this->addSql('ALTER TABLE quote_proposal DROP external_pdf_uploaded_at');
        $this->addSql('ALTER TABLE quote_proposal DROP accepted_pdf_name');
        $this->addSql('ALTER TABLE quote_proposal DROP accepted_pdf_original_name');
        $this->addSql('ALTER TABLE quote_proposal DROP accepted_pdf_mime_type');
        $this->addSql('ALTER TABLE quote_proposal DROP accepted_pdf_size');
        $this->addSql('ALTER TABLE quote_proposal DROP accepted_pdf_generated_at');
    }
}
