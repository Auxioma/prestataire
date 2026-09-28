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
final class Version20260611064202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE prestataire_intervention_zone ALTER radius_km SET NOT NULL');
        $this->addSql('ALTER TABLE prestataire_intervention_zone ALTER is_main_zone SET NOT NULL');
        $this->addSql('ALTER TABLE prestataire_intervention_zone ALTER is_active SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE prestataire_intervention_zone ALTER radius_km DROP NOT NULL');
        $this->addSql('ALTER TABLE prestataire_intervention_zone ALTER is_main_zone DROP NOT NULL');
        $this->addSql('ALTER TABLE prestataire_intervention_zone ALTER is_active DROP NOT NULL');
    }
}
