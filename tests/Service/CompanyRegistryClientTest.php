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

namespace App\Tests\Service;

use App\Service\CompanyRegistryClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class CompanyRegistryClientTest extends TestCase
{
    public function testUnrelatedSearchResultCannotVerifySiret(): void
    {
        $client = $this->client(['siren' => '999999999', 'siege' => ['siret' => '99999999900011', 'etat_administratif' => 'A']]);
        $this->expectException(\RuntimeException::class);
        $client->buildCompanyPreviewFromSiret('12345678900011');
    }

    public function testSecondaryEstablishmentUsesItsOwnAddressAndClosedStatus(): void
    {
        $client = $this->client([
            'siren' => '123456789',
            'nom_complet' => 'Entreprise exemple',
            'siege' => ['siret' => '12345678900011', 'etat_administratif' => 'A', 'libelle_commune' => 'Paris'],
            'matching_etablissements' => [['siret' => '12345678900029', 'etat_administratif' => 'F', 'libelle_commune' => 'Lille']],
        ]);
        $preview = $client->buildCompanyPreviewFromSiret('12345678900029');
        self::assertTrue($preview['isVerified']);
        self::assertFalse($preview['isActive']);
        self::assertSame('12345678900029', $preview['siret']);
        self::assertSame('Lille', $preview['fields']['city']);
    }

    public function testMatchingActiveEstablishmentIsVerified(): void
    {
        $preview = $this->client(['siren' => '123456789', 'siege' => ['siret' => '12345678900011', 'etat_administratif' => 'A']])
            ->buildCompanyPreviewFromSiret('12345678900011');
        self::assertTrue($preview['isVerified']);
        self::assertTrue($preview['isActive']);
    }

    private function client(array $result): CompanyRegistryClient
    {
        return new CompanyRegistryClient(new MockHttpClient(new MockResponse(json_encode(['results' => [$result]], \JSON_THROW_ON_ERROR))), 'https://registry.example');
    }
}
