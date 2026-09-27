<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260927095243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ALTER phone_verified_at DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER email_verified_at DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER last_login_at DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER reset_token_expires_at DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER updated_at DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER deleted_at DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER phone_verified_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING phone_verified_at::TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE "user" ALTER email_verified_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING email_verified_at::TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE "user" ALTER last_login_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING last_login_at::TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE "user" ALTER reset_token_expires_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING reset_token_expires_at::TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE "user" ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING created_at::TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE "user" ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING updated_at::TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE "user" ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING deleted_at::TIMESTAMP(0) WITHOUT TIME ZONE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ALTER phone_verified_at TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE "user" ALTER email_verified_at TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE "user" ALTER last_login_at TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE "user" ALTER reset_token_expires_at TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE "user" ALTER created_at TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE "user" ALTER updated_at TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE "user" ALTER deleted_at TYPE VARCHAR(255)');
    }
}
