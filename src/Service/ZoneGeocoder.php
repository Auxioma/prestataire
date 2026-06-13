<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ZoneGeocoder
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {
    }

    public function geocode(?string $city, ?string $postalCode): ?array
    {
        $city = trim((string) $city);
        $postalCode = trim((string) $postalCode);

        if ($city === '' && $postalCode === '') {
            return null;
        }

        $query = trim(sprintf('%s %s France', $postalCode, $city));

        $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
            'query' => [
                'q' => $query,
                'format' => 'jsonv2',
                'limit' => 1,
                'countrycodes' => 'fr',
                'addressdetails' => 1,
            ],
            'headers' => [
                'User-Agent' => 'TrouveMoi/1.0',
            ],
        ]);

        $data = $response->toArray(false);

        if (empty($data) || !isset($data[0]['lat'], $data[0]['lon'])) {
            return null;
        }

        $result = $data[0];

        return [
            'latitude' => (string) $result['lat'],
            'longitude' => (string) $result['lon'],
            'city' => $result['address']['city']
                ?? $result['address']['town']
                ?? $result['address']['village']
                ?? $city
                ?? null,
            'postalCode' => $result['address']['postcode'] ?? $postalCode ?: null,
            'department' => $result['address']['county'] ?? null,
            'region' => $result['address']['state'] ?? null,
        ];
    }
}