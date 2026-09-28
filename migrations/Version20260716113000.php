<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les préférences de notifications du compte utilisateur.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD notify_on_quote_request_received BOOLEAN DEFAULT TRUE NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD notify_on_message_received BOOLEAN DEFAULT TRUE NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD notify_on_quote_request_accepted BOOLEAN DEFAULT TRUE NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD notify_on_review_received BOOLEAN DEFAULT TRUE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP notify_on_quote_request_received');
        $this->addSql('ALTER TABLE "user" DROP notify_on_message_received');
        $this->addSql('ALTER TABLE "user" DROP notify_on_quote_request_accepted');
        $this->addSql('ALTER TABLE "user" DROP notify_on_review_received');
    }
}
