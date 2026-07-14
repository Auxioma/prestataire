<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add explicit payment notice fields to quote proposals and invoices.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice ADD late_payment_penalty_terms TEXT DEFAULT NULL, ADD fixed_recovery_compensation_terms TEXT DEFAULT NULL, ADD early_payment_discount_terms TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE quote_proposal ADD late_payment_penalty_terms TEXT DEFAULT NULL, ADD fixed_recovery_compensation_terms TEXT DEFAULT NULL, ADD early_payment_discount_terms TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice DROP late_payment_penalty_terms, DROP fixed_recovery_compensation_terms, DROP early_payment_discount_terms');
        $this->addSql('ALTER TABLE quote_proposal DROP late_payment_penalty_terms, DROP fixed_recovery_compensation_terms, DROP early_payment_discount_terms');
    }
}
