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

use App\Entity\PrestataireProfile;
use App\Search\PrestataireDocumentMapper;
use App\Search\PrestataireIndexDefinition;
use App\Search\PrestataireSearchEligibility;
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
