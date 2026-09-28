<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le statut de vacances des prestataires.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE prestataire_profile ADD is_on_vacation BOOLEAN DEFAULT FALSE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE prestataire_profile DROP is_on_vacation');
    }
}
