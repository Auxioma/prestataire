<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713183500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop residual global unique index on invoice_number to allow per-prestataire numbering.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_906517442da68207');
        $this->addSql('DROP INDEX IF EXISTS UNIQ_9065174494F78A7D');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'Irreversible migration.');
    }
}
