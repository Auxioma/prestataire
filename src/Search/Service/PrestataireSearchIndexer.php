<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 AUXIOMA Web Agency.
 *
 * Projet : TrouveMoi
 *
 * Tous droits réservés.
 *
 * Ce fichier fait partie du projet TrouveMoi,
 * développé par AUXIOMA Web Agency.
 *
 * Toute reproduction, modification, distribution ou utilisation,
 * totale ou partielle, sans autorisation écrite préalable,
 * est strictement interdite.
 */

namespace App\Search\Service;

use App\Prestataire\Entity\PrestataireProfile;
use App\Search\Indexing\PrestataireDocumentMapper;
use App\Search\Indexing\PrestataireIndexDefinition;
use Elastic\Elasticsearch\Exception\ClientResponseException;

final class PrestataireSearchIndexer
{
    public function __construct(
        private readonly ElasticsearchClient $elasticsearchClient,
        private readonly PrestataireDocumentMapper $prestataireDocumentMapper,
    ) {
    }

    public function indexProfile(PrestataireProfile $prestataireProfile, bool $refresh = true): void
    {
        if (null === $prestataireProfile->getId()) {
            throw new \LogicException('Le profil doit être enregistré avant indexation.');
        }

        if (!PrestataireSearchEligibility::isEligible($prestataireProfile)) {
            $this->removeProfile($prestataireProfile->getId(), $refresh);

            return;
        }

        $document = $this->prestataireDocumentMapper->map($prestataireProfile);

        $this->elasticsearchClient->getClient()->index([
            'index' => PrestataireIndexDefinition::ALIAS,
            'require_alias' => true,
            'id' => (string) $prestataireProfile->getId(),
            'body' => $document,
            'refresh' => $refresh ? 'wait_for' : false,
        ]);
    }

    public function removeProfile(string $id, bool $refresh = true): void
    {
        // Resolve the alias first: a missing alias must remain a retryable error.
        $this->elasticsearchClient->getClient()->indices()->getAlias(['name' => PrestataireIndexDefinition::ALIAS]);

        try {
            $this->elasticsearchClient->getClient()->delete([
                'index' => PrestataireIndexDefinition::ALIAS,
                'id' => $id,
                'refresh' => $refresh ? 'wait_for' : false,
            ]);
        } catch (ClientResponseException $exception) {
            if (404 !== $exception->getResponse()->getStatusCode()) {
                throw $exception;
            }
        }
    }
}
