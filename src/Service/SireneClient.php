<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SireneClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $sireneBaseUrl,
        private readonly string $sireneApiKey,
    ) {
    }

    // récupération des données officielles d'un établissement via son SIRET
    public function getEtablissementBySiret(string $siret): array
    {
        $normalizedSiret = preg_replace('/\D+/', '', $siret ?? '');

        if (!$normalizedSiret || 14 !== strlen($normalizedSiret)) {
            throw new \InvalidArgumentException('Le SIRET doit contenir 14 chiffres.');
        }

        $response = $this->httpClient->request('GET', sprintf('%s/siret/%s', rtrim($this->sireneBaseUrl, '/'), $normalizedSiret), [
            'headers' => [
                'Accept' => 'application/json',
                'X-INSEE-Api-Key-Integration' => $this->sireneApiKey,
            ],
        ]);

        $statusCode = $response->getStatusCode();

        if (404 === $statusCode) {
            throw new \RuntimeException('Aucun établissement trouvé pour ce SIRET.');
        }

        if (200 !== $statusCode) {
            throw new \RuntimeException(sprintf('Erreur API Sirene (HTTP %d).', $statusCode));
        }

        return $response->toArray(false);
    }

    // mapping simplifié des champs utiles pour ton formulaire entreprise
    public function buildCompanyPreviewFromSiret(string $siret): array
    {
        $data = $this->getEtablissementBySiret($siret);

        $etablissement = $data['etablissement'] ?? [];
        $uniteLegale = $etablissement['uniteLegale'] ?? [];
        $adresse = $etablissement['adresseEtablissement'] ?? [];

        $companyName = $uniteLegale['denominationUniteLegale']
            ?? trim(sprintf(
                '%s %s %s',
                $uniteLegale['prenom1UniteLegale'] ?? '',
                $uniteLegale['nomUsageUniteLegale'] ?? '',
                $uniteLegale['nomUniteLegale'] ?? ''
            ));

        $legalName = $uniteLegale['denominationUniteLegale']
            ?? trim(sprintf(
                '%s %s',
                $uniteLegale['prenom1UniteLegale'] ?? '',
                $uniteLegale['nomUniteLegale'] ?? ''
            ));

        $streetParts = array_filter([
            $adresse['numeroVoieEtablissement'] ?? null,
            $adresse['indiceRepetitionEtablissement'] ?? null,
            $adresse['typeVoieEtablissement'] ?? null,
            $adresse['libelleVoieEtablissement'] ?? null,
        ]);

        $fullAddress = trim(implode(' ', $streetParts));

        return [
            'siret' => $etablissement['siret'] ?? $siret,
            'fields' => [
                'companyName' => $companyName ?: null,
                'legalName' => $legalName ?: null,
                'structureType' => $uniteLegale['categorieJuridiqueUniteLegale'] ?? null,
                'address' => $fullAddress ?: null,
                'postalCode' => $adresse['codePostalEtablissement'] ?? null,
                'city' => $adresse['libelleCommuneEtablissement'] ?? null,
                'country' => 'France',
            ],
            'display' => [
                [
                    'label' => 'Nom de l’entreprise / Enseigne',
                    'current' => null,
                    'incoming' => $companyName ?: null,
                ],
                [
                    'label' => 'Raison sociale',
                    'current' => null,
                    'incoming' => $legalName ?: null,
                ],
                [
                    'label' => 'Forme juridique',
                    'current' => null,
                    'incoming' => $uniteLegale['categorieJuridiqueUniteLegale'] ?? null,
                ],
                [
                    'label' => 'Adresse',
                    'current' => null,
                    'incoming' => $fullAddress ?: null,
                ],
                [
                    'label' => 'Code postal',
                    'current' => null,
                    'incoming' => $adresse['codePostalEtablissement'] ?? null,
                ],
                [
                    'label' => 'Ville',
                    'current' => null,
                    'incoming' => $adresse['libelleCommuneEtablissement'] ?? null,
                ],
                [
                    'label' => 'Pays',
                    'current' => null,
                    'incoming' => 'France',
                ],
            ],
        ];
    }
}