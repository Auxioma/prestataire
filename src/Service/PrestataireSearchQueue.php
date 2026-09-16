<?php

/**
 * Copyright(c) 2026 Trouve moi
 *
 * Ce fichier fait partie d’un projet développé par Auxioma Web Agency.
 * Tous droits réservés.
 *
 * Ce code source est la propriété exclusive de Auxioma Web Agency.
 * Toute reproduction, modification, distribution ou utilisation sans autorisation préalable est interdite.
 */

namespace App\Service;

use App\Repository\PrestataireSearchReadRepository;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

final class PrestataireSearchQueue
{
    public function __construct(
        private readonly Connection $connection,
        private readonly PrestataireSearchReadRepository $profiles,
        private readonly PrestataireSearchIndexer $indexer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function enqueue(string $profileId): void
    {
        $this->connection->executeStatement(
            'INSERT INTO prestataire_search_job (profile_id, attempts, available_at) VALUES (?, 0, date_trunc(\'second\', CURRENT_TIMESTAMP)) ON CONFLICT (profile_id) DO UPDATE SET attempts = 0, available_at = date_trunc(\'second\', CURRENT_TIMESTAMP), last_error = NULL',
            [$profileId],
        );
    }

    /** @return array{processed: int, failed: int} */
    public function process(int $limit = 100, ?array $profileIds = null): array
    {
        $result = ['processed' => 0, 'failed' => 0];
        // An outer transaction has not committed yet: only the background worker may process it later.
        if ($this->connection->isTransactionActive() || [] === $profileIds) {
            return $result;
        }

        for ($i = 0; $i < $limit; ++$i) {
            $handled = $this->connection->transactional(function (Connection $connection) use ($profileIds, &$result): bool {
                // Rebuilding holds the exclusive counterpart; leave jobs pending until the alias is ready.
                if (!$connection->fetchOne('SELECT pg_try_advisory_xact_lock_shared(842610)')) {
                    return false;
                }
                $sql = 'SELECT profile_id, attempts FROM prestataire_search_job WHERE available_at <= CURRENT_TIMESTAMP';
                $params = [];
                if (null !== $profileIds) {
                    $sql .= ' AND profile_id IN ('.implode(',', array_fill(0, \count($profileIds), '?')).')';
                    $params = $profileIds;
                }
                // Serialize each profile, while allowing several workers on different profiles.
                $job = $connection->fetchAssociative($sql.' ORDER BY available_at, profile_id LIMIT 1 FOR UPDATE SKIP LOCKED', $params);
                if (false === $job) {
                    return false;
                }

                $id = (string) $job['profile_id'];
                try {
                    $profile = $this->profiles->findFresh($id);
                    if (null === $profile) {
                        $this->indexer->removeProfile($id);
                    } else {
                        $this->indexer->indexProfile($profile);
                    }
                    $connection->delete('prestataire_search_job', ['profile_id' => $id]);
                    ++$result['processed'];
                } catch (\Throwable $exception) {
                    $attempts = (int) $job['attempts'] + 1;
                    $delay = min(3600, 5 * (2 ** min($attempts - 1, 10)));
                    $connection->executeStatement(
                        'UPDATE prestataire_search_job SET attempts = ?, available_at = date_trunc(\'second\', CURRENT_TIMESTAMP) + (CAST(? AS INTEGER) * INTERVAL \'1 second\'), last_error = ? WHERE profile_id = ?',
                        [$attempts, $delay, $exception::class, $id],
                    );
                    // Exception messages can contain endpoint credentials: retain only the class.
                    $this->logger->error('Échec de synchronisation Elasticsearch ; reprise programmée.', ['profile_id' => $id, 'attempts' => $attempts, 'error_type' => $exception::class]);
                    ++$result['failed'];
                }

                return true;
            });
            if (!$handled) {
                break;
            }
        }

        return $result;
    }
}
