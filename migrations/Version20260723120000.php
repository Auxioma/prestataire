<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260723120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime les colonnes de préférences de notification devis/facture ajoutées par erreur.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP notify_on_quote_proposal_received');
        $this->addSql('ALTER TABLE "user" DROP notify_on_invoice_received');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD notify_on_quote_proposal_received BOOLEAN DEFAULT TRUE NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD notify_on_invoice_received BOOLEAN DEFAULT TRUE NOT NULL');
    }
}
