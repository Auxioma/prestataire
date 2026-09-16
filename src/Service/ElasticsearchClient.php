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

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use GuzzleHttp\Client as GuzzleClient;

final class ElasticsearchClient
{
    private Client $client;

    public function __construct(
        private readonly string $host,
        private readonly string $username,
        private readonly string $password,
        private readonly bool $verifyTls = true,
        private readonly ?string $caCertPath = null,
    ) {
        $verification = false;

        if ($this->verifyTls) {
            $hasReadableCaBundle = null !== $this->caCertPath && is_readable($this->caCertPath);
            // Never silently disable certificate verification, even on localhost.
            $verification = $hasReadableCaBundle
                ? $this->caCertPath
                : true;
        }

        $httpClient = new GuzzleClient([
            'verify' => $verification,
            'auth' => [$this->username, $this->password],
            'connect_timeout' => 2,
            'timeout' => 10,
        ]);

        $this->client = ClientBuilder::create()
            ->setHosts([$this->host])
            ->setHttpClient($httpClient)
            ->build();
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function ping(): bool
    {
        return $this->client->ping()->asBool();
    }
}
