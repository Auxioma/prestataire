<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713172126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill annual quote/invoice numbering and align invoice sequence with quote sequence.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS UNIQ_55DF3CE337867BE3');
        $this->addSql('DROP INDEX IF EXISTS UNIQ_9065174494F78A7D');

        $this->addSql(<<<'SQL'
            WITH ranked_quotes AS (
                SELECT
                    id,
                    ROW_NUMBER() OVER (
                        PARTITION BY prestataire_id
                        ORDER BY COALESCE(finalized_at, created_at), id
                    ) AS new_sequence
                FROM quote_proposal
            )
            UPDATE quote_proposal qp
            SET proposal_sequence_number = -ranked_quotes.new_sequence
            FROM ranked_quotes
            WHERE qp.id = ranked_quotes.id
        SQL);

        $this->addSql('UPDATE quote_proposal SET proposal_sequence_number = ABS(proposal_sequence_number) WHERE proposal_sequence_number IS NOT NULL');

        $this->addSql(<<<'SQL'
            UPDATE quote_proposal
            SET proposal_number = '__TMP_DEV__' || id
            WHERE proposal_sequence_number IS NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE quote_proposal
            SET proposal_number =
                'DEV-'
                || COALESCE(SUBSTRING(proposal_number FROM '^DEV-([0-9]{4})-[0-9]{5}$'), TO_CHAR(COALESCE(finalized_at, created_at), 'YYYY'))
                || '-'
                || LPAD(proposal_sequence_number::TEXT, 5, '0')
            WHERE proposal_sequence_number IS NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE invoice i
            SET invoice_sequence_number = -qp.proposal_sequence_number
            FROM quote_proposal qp
            WHERE i.quote_proposal_id = qp.id
        SQL);

        $this->addSql('UPDATE invoice SET invoice_sequence_number = ABS(invoice_sequence_number) WHERE invoice_sequence_number IS NOT NULL');

        $this->addSql(<<<'SQL'
            UPDATE invoice
            SET invoice_number = '__TMP_FAC__' || id
            WHERE invoice_sequence_number IS NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE invoice i
            SET invoice_number =
                'FAC-'
                || COALESCE(
                    SUBSTRING(qp.proposal_number FROM '^DEV-([0-9]{4})-[0-9]{5}$'),
                    SUBSTRING(i.invoice_number FROM '^FAC-([0-9]{4})-[0-9]{5}$'),
                    TO_CHAR(COALESCE(qp.finalized_at, qp.created_at, i.issued_at, i.created_at), 'YYYY')
                )
                || '-'
                || LPAD(i.invoice_sequence_number::TEXT, 5, '0')
            FROM quote_proposal qp
            WHERE i.quote_proposal_id = qp.id
              AND i.invoice_sequence_number IS NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'Irreversible migration.');
    }
}
