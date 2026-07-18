<?php

namespace App\Service;

use App\Entity\PrestataireProfile;
use App\Search\PrestataireDocumentMapper;

final class PrestataireSearchIndexer
{
    private const INDEX_NAME = 'prestataires_search_v1';

    public function __construct(
        private readonly ElasticsearchClient $elasticsearchClient,
        private readonly PrestataireDocumentMapper $prestataireDocumentMapper,
    ) {
    }

    public function indexProfile(PrestataireProfile $prestataireProfile, bool $refresh = true): void
    {
        $document = $this->prestataireDocumentMapper->map($prestataireProfile);

        $this->elasticsearchClient->getClient()->index([
            'index' => self::INDEX_NAME,
            'id' => (string) $prestataireProfile->getId(),
            'body' => $document,
            'refresh' => $refresh,
        ]);
    }
}
