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
        $verification = false;

        if ($this->verifyTls) {
            $hasReadableCaBundle = null !== $this->caCertPath && is_readable($this->caCertPath);
            $hostName = parse_url($this->host, PHP_URL_HOST);
            $isLocalElasticsearch = \in_array($hostName, ['localhost', '127.0.0.1', '::1'], true);

            // Elasticsearch enables a self-signed certificate by default for a
            // local installation. A readable CA bundle is always preferred.
            // Without one, only the local development instance bypasses TLS
            // verification; remote instances keep using the system CA bundle.
            $verification = $hasReadableCaBundle
                ? $this->caCertPath
                : !$isLocalElasticsearch;
        }

        $httpClient = new GuzzleClient([
            'verify' => $verification,
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
