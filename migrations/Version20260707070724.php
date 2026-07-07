<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260707070724 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE prestataire_profile ADD document_verification_status VARCHAR(50) NOT NULL DEFAULT 'NOT_SUBMITTED'");
        $this->addSql('ALTER TABLE prestataire_profile ADD company_last_verification_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE prestataire_profile ADD company_verification_source VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE prestataire_profile ADD company_verification_note VARCHAR(255) DEFAULT NULL');

        $this->addSql("COMMENT ON COLUMN prestataire_profile.document_verification_status IS '(DC2Type:App\\Enum\\DocumentVerificationStatusEnum)'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE prestataire_profile DROP document_verification_status');
        $this->addSql('ALTER TABLE prestataire_profile DROP company_last_verification_at');
        $this->addSql('ALTER TABLE prestataire_profile DROP company_verification_source');
        $this->addSql('ALTER TABLE prestataire_profile DROP company_verification_note');
    }
}
