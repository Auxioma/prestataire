<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260723100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le suivi de paiement des factures prestataire et les revenus externes manuels.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice ADD paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE TABLE prestataire_revenue_entry (id BIGSERIAL NOT NULL, prestataire_id BIGINT NOT NULL, prestataire_service_id INT DEFAULT NULL, label VARCHAR(255) NOT NULL, service_label VARCHAR(255) DEFAULT NULL, client_name VARCHAR(255) DEFAULT NULL, invoice_number VARCHAR(100) DEFAULT NULL, issued_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, currency VARCHAR(3) NOT NULL DEFAULT \'EUR\', subtotal_ht NUMERIC(10, 2) NOT NULL DEFAULT \'0.00\', tax_amount NUMERIC(10, 2) NOT NULL DEFAULT \'0.00\', total_ttc NUMERIC(10, 2) NOT NULL DEFAULT \'0.00\', notes TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_prestataire_revenue_entry_prestataire ON prestataire_revenue_entry (prestataire_id)');
        $this->addSql('CREATE INDEX idx_prestataire_revenue_entry_issued_at ON prestataire_revenue_entry (issued_at)');
        $this->addSql('CREATE INDEX idx_prestataire_revenue_entry_paid_at ON prestataire_revenue_entry (paid_at)');
        $this->addSql('CREATE INDEX IDX_832F9E9D1D99B071 ON prestataire_revenue_entry (prestataire_id)');
        $this->addSql('CREATE INDEX IDX_832F9E9DFD9610BA ON prestataire_revenue_entry (prestataire_service_id)');
        $this->addSql('ALTER TABLE prestataire_revenue_entry ADD CONSTRAINT FK_832F9E9D1D99B071 FOREIGN KEY (prestataire_id) REFERENCES prestataire_profile (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE prestataire_revenue_entry ADD CONSTRAINT FK_832F9E9DFD9610BA FOREIGN KEY (prestataire_service_id) REFERENCES prestataire_profile_service (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE prestataire_revenue_entry DROP CONSTRAINT FK_832F9E9D1D99B071');
        $this->addSql('ALTER TABLE prestataire_revenue_entry DROP CONSTRAINT FK_832F9E9DFD9610BA');
        $this->addSql('DROP TABLE prestataire_revenue_entry');
        $this->addSql('ALTER TABLE invoice DROP paid_at');
    }
}
