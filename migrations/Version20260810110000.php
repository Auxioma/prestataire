<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260810110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la date de retour de vacances au profil prestataire.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE prestataire_profile ADD vacation_return_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE prestataire_profile DROP vacation_return_date');
    }
}
