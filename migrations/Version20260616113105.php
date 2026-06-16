<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260616113105 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE prestataire_profile_service ADD short_description VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE prestataire_profile_service ADD pricing_type VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE prestataire_profile_service ADD additional_info TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE prestataire_profile_service DROP short_description');
        $this->addSql('ALTER TABLE prestataire_profile_service DROP pricing_type');
        $this->addSql('ALTER TABLE prestataire_profile_service DROP additional_info');
    }
}
