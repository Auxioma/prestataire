<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute une image de signature sur le profil prestataire pour les devis PDF natifs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE prestataire_profile ADD signature_image VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE prestataire_profile DROP signature_image');
    }
}
