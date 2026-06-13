<?php

/**
 * Copyright(c) 2026 Trouve moi
 *
 * Ce fichier fait partie d’un projet développé par Auxioma Web Agency.
 * Tous droits réservés.
 *
 * Ce code source est la propriété exclusive de Auxioma Web Agency.
 * Toute reproduction, modification, distribution ou utilisation sans autorisation préalable est interdite.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260528113750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_client_user ON client_profile (user_id)');
        $this->addSql('CREATE INDEX idx_presta_user ON prestataire_profile (user_id)');
        $this->addSql('CREATE INDEX idx_presta_status ON prestataire_profile (profile_status)');
        $this->addSql('CREATE INDEX idx_presta_city ON prestataire_profile (city)');
        $this->addSql('CREATE INDEX idx_presta_zip ON prestataire_profile (postal_code)');
        $this->addSql('ALTER TABLE service ALTER id TYPE BIGINT');
        $this->addSql('ALTER TABLE service ALTER category_id TYPE BIGINT');
        $this->addSql('CREATE INDEX idx_service_active ON service (is_active)');
        $this->addSql('ALTER INDEX idx_e19d9ad212469de2 RENAME TO idx_service_category');
        $this->addSql('ALTER TABLE service_category ALTER id TYPE BIGINT');
        $this->addSql('ALTER TABLE service_category ALTER parent_id TYPE BIGINT');
        $this->addSql('CREATE INDEX idx_category_active ON service_category (is_active)');
        $this->addSql('CREATE INDEX idx_category_position ON service_category (position)');
        $this->addSql('ALTER INDEX idx_ff3a42fc727aca70 RENAME TO idx_category_parent');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_client_user');
        $this->addSql('DROP INDEX idx_presta_user');
        $this->addSql('DROP INDEX idx_presta_status');
        $this->addSql('DROP INDEX idx_presta_city');
        $this->addSql('DROP INDEX idx_presta_zip');
        $this->addSql('DROP INDEX idx_service_active');
        $this->addSql('ALTER TABLE service ALTER id TYPE INT');
        $this->addSql('ALTER TABLE service ALTER category_id TYPE INT');
        $this->addSql('ALTER INDEX idx_service_category RENAME TO idx_e19d9ad212469de2');
        $this->addSql('DROP INDEX idx_category_active');
        $this->addSql('DROP INDEX idx_category_position');
        $this->addSql('ALTER TABLE service_category ALTER id TYPE INT');
        $this->addSql('ALTER TABLE service_category ALTER parent_id TYPE INT');
        $this->addSql('ALTER INDEX idx_category_parent RENAME TO idx_ff3a42fc727aca70');
    }
}
