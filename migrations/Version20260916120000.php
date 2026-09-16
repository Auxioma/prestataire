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

final class Version20260916120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute une file transactionnelle persistante de synchronisation Elasticsearch.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE prestataire_search_job (profile_id BIGINT NOT NULL, attempts INT DEFAULT 0 NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_error VARCHAR(255) DEFAULT NULL, PRIMARY KEY(profile_id))');
        $this->addSql('CREATE INDEX idx_search_job_available ON prestataire_search_job (available_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE prestataire_search_job');
    }
}
