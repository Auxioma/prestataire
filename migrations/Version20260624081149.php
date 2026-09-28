<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260624081149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE prestataire_appointment ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D1819BA8989D9B62 ON prestataire_appointment (slug)');
        $this->addSql('ALTER TABLE prestataire_profile_service ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7ACDE71F989D9B62 ON prestataire_profile_service (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_D1819BA8989D9B62');
        $this->addSql('ALTER TABLE prestataire_appointment DROP slug');
        $this->addSql('DROP INDEX UNIQ_7ACDE71F989D9B62');
        $this->addSql('ALTER TABLE prestataire_profile_service DROP slug');
    }
}
