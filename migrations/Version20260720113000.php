<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Active la reponse aux devis sur le plan gratuit tout en gardant la messagerie instantanee desactivee.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE subscription_plan SET quote_responses_enabled = true, instant_messaging_enabled = false, description = 'Réponse aux demandes de devis selon les crédits disponibles, sans messagerie instantanée ni accès premium.' WHERE code = 'free'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE subscription_plan SET quote_responses_enabled = false, instant_messaging_enabled = false, description = 'Consultation limitée des demandes de devis, sans réponse possible ni accès aux coordonnées visiteurs.' WHERE code = 'free'");
    }
}
