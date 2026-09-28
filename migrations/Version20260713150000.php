<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create invoice and invoice_item tables for invoice V1.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE invoice (id BIGSERIAL NOT NULL, quote_proposal_id BIGINT NOT NULL, quote_request_id BIGINT NOT NULL, prestataire_id BIGINT NOT NULL, client_id BIGINT NOT NULL, status VARCHAR(30) NOT NULL, source_type VARCHAR(40) NOT NULL, invoice_number VARCHAR(50) DEFAULT NULL, issued_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, due_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, currency VARCHAR(3) NOT NULL DEFAULT \'EUR\', subtotal_ht NUMERIC(10, 2) NOT NULL DEFAULT \'0.00\', tax_amount NUMERIC(10, 2) NOT NULL DEFAULT \'0.00\', total_ttc NUMERIC(10, 2) NOT NULL DEFAULT \'0.00\', notes TEXT DEFAULT NULL, terms TEXT DEFAULT NULL, factur_x_pdf_name VARCHAR(255) DEFAULT NULL, factur_x_xml_name VARCHAR(255) DEFAULT NULL, external_pdf_name VARCHAR(255) DEFAULT NULL, external_pdf_original_name VARCHAR(255) DEFAULT NULL, external_pdf_mime_type VARCHAR(100) DEFAULT NULL, external_pdf_size INT DEFAULT NULL, external_pdf_uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_invoice_quote_proposal ON invoice (quote_proposal_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9065174494F78A7D ON invoice (invoice_number)');
        $this->addSql('CREATE INDEX IDX_90651744C7948C4F ON invoice (quote_request_id)');
        $this->addSql('CREATE INDEX IDX_90651744DDAFFA0E ON invoice (prestataire_id)');
        $this->addSql('CREATE INDEX IDX_9065174419EB6921 ON invoice (client_id)');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_90651744ED01865B FOREIGN KEY (quote_proposal_id) REFERENCES quote_proposal (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_90651744C7948C4F FOREIGN KEY (quote_request_id) REFERENCES quote_request (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_90651744DDAFFA0E FOREIGN KEY (prestataire_id) REFERENCES prestataire_profile (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_9065174419EB6921 FOREIGN KEY (client_id) REFERENCES client_profile (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE invoice_item (id SERIAL NOT NULL, invoice_id BIGINT NOT NULL, label VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, quantity NUMERIC(10, 2) NOT NULL DEFAULT \'1.00\', unit_price_ht NUMERIC(10, 2) NOT NULL DEFAULT \'0.00\', vat_rate NUMERIC(5, 2) NOT NULL DEFAULT \'20.00\', total_ht NUMERIC(10, 2) NOT NULL DEFAULT \'0.00\', position INT NOT NULL DEFAULT 0, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3B4C2B52298B9F1D ON invoice_item (invoice_id)');
        $this->addSql('CREATE INDEX idx_invoice_item_position ON invoice_item (position)');
        $this->addSql('ALTER TABLE invoice_item ADD CONSTRAINT FK_3B4C2B52298B9F1D FOREIGN KEY (invoice_id) REFERENCES invoice (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice_item DROP CONSTRAINT FK_3B4C2B52298B9F1D');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT FK_90651744ED01865B');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT FK_90651744C7948C4F');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT FK_90651744DDAFFA0E');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT FK_9065174419EB6921');
        $this->addSql('DROP TABLE invoice_item');
        $this->addSql('DROP TABLE invoice');
    }
}
