<?php

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
        private readonly bool $verifyTls = false,
        private readonly ?string $caCertPath = null,
    ) {
        $httpClient = new GuzzleClient([
            'verify' => $this->verifyTls ? $this->caCertPath ?? true : false,
            'auth' => [$this->username, $this->password],
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
