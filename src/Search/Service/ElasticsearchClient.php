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
