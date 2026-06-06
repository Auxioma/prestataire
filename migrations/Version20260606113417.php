<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260606113417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE prestataire_profile_service (prestataire_profile_id BIGINT NOT NULL, service_id BIGINT NOT NULL, PRIMARY KEY (prestataire_profile_id, service_id))');
        $this->addSql('CREATE INDEX IDX_7ACDE71F1AE57984 ON prestataire_profile_service (prestataire_profile_id)');
        $this->addSql('CREATE INDEX IDX_7ACDE71FED5CA9E6 ON prestataire_profile_service (service_id)');
        $this->addSql('ALTER TABLE prestataire_profile_service ADD CONSTRAINT FK_7ACDE71F1AE57984 FOREIGN KEY (prestataire_profile_id) REFERENCES prestataire_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE prestataire_profile_service ADD CONSTRAINT FK_7ACDE71FED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE prestataire_profile_service DROP CONSTRAINT FK_7ACDE71F1AE57984');
        $this->addSql('ALTER TABLE prestataire_profile_service DROP CONSTRAINT FK_7ACDE71FED5CA9E6');
        $this->addSql('DROP TABLE prestataire_profile_service');
    }
}
