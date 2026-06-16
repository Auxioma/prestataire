<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260616063145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs V1.1 sur prestataire_profile_service et contrainte d’unicité prestataire/service';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE prestataire_profile_service ADD is_active BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE prestataire_profile_service ADD title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE prestataire_profile_service ADD description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE prestataire_profile_service ADD price_from NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE prestataire_profile_service ADD price_to NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE prestataire_profile_service ADD price_unit VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE prestataire_profile_service ADD position INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE prestataire_profile_service ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE prestataire_profile_service ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE prestataire_profile_service DROP is_active');
        $this->addSql('ALTER TABLE prestataire_profile_service DROP title');
        $this->addSql('ALTER TABLE prestataire_profile_service DROP description');
        $this->addSql('ALTER TABLE prestataire_profile_service DROP price_from');
        $this->addSql('ALTER TABLE prestataire_profile_service DROP price_to');
        $this->addSql('ALTER TABLE prestataire_profile_service DROP price_unit');
        $this->addSql('ALTER TABLE prestataire_profile_service DROP position');
        $this->addSql('ALTER TABLE prestataire_profile_service DROP created_at');
        $this->addSql('ALTER TABLE prestataire_profile_service DROP updated_at');
    }
}