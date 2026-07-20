<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les crédits de bienvenue configurables sur les plans d’abonnement.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subscription_plan ADD welcome_credits INT DEFAULT 0 NOT NULL');
        $this->addSql("UPDATE subscription_plan SET welcome_credits = 20 WHERE code = 'free'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subscription_plan DROP welcome_credits');
    }
}
