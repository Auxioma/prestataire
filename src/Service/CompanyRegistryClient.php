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

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CompanyRegistryClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $companyRegistryBaseUrl,
    ) {
    }

    // récupération des données entreprise via l'API ouverte recherche-entreprises.api.gouv.fr
    public function getEtablissementBySiret(string $siret): array
    {
        $normalizedSiret = preg_replace('/\D+/', '', $siret ?? '');

        if (!$normalizedSiret || 14 !== mb_strlen($normalizedSiret)) {
            throw new \InvalidArgumentException('Le SIRET doit contenir 14 chiffres.');
        }

        $response = $this->httpClient->request('GET', \sprintf('%s/search', mb_rtrim($this->companyRegistryBaseUrl, '/')), [
            'query' => [
                'q' => $normalizedSiret,
                'per_page' => 1,
                'page' => 1,
            ],
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'TrouveMoiPrestataires/1.0 (+company-verification)',
            ],
        ]);

        $statusCode = $response->getStatusCode();

        if (200 !== $statusCode) {
            throw new \RuntimeException(\sprintf('Erreur API entreprise.data.gouv.fr (HTTP %d).', $statusCode));
        }

        $data = $response->toArray(false);
        $results = $data['results'] ?? [];

        if (!\is_array($results) || [] === $results) {
            throw new \RuntimeException('Aucun établissement trouvé pour ce SIRET.');
        }

        foreach ($results as $result) {
            if (!\is_array($result) || (string) ($result['siren'] ?? '') !== mb_substr($normalizedSiret, 0, 9)) {
                continue;
            }
            if (($result['siege']['siret'] ?? null) === $normalizedSiret) {
                return $result;
            }
            foreach ($result['matching_etablissements'] ?? [] as $etablissement) {
                if (\is_array($etablissement) && ($etablissement['siret'] ?? null) === $normalizedSiret) {
                    // Use the requested establishment's address and status, not those of its headquarters.
                    $result['siege'] = $etablissement;

                    return $result;
                }
            }
        }

        throw new \RuntimeException('L’API ne confirme pas cet établissement exact. Vérifiez le SIRET.');
    }

    // mapping simplifié des champs utiles pour le formulaire entreprise
    public function buildCompanyPreviewFromSiret(string $siret): array
    {
        $company = $this->getEtablissementBySiret($siret);
        $normalizedSiret = preg_replace('/\D+/', '', $siret);
        $siege = \is_array($company['siege'] ?? null) ? $company['siege'] : [];
        $siren = $company['siren']
            ?? (\is_string($normalizedSiret) && mb_strlen($normalizedSiret) >= 9 ? mb_substr($normalizedSiret, 0, 9) : null);

        $etablissementStatus = $siege['etat_administratif'] ?? null;
        $isVerified = ($siege['siret'] ?? null) === $normalizedSiret;
        $isActive = 'A' === $etablissementStatus;

        $companyName = $this->firstNonEmptyString([
            $company['nom_complet'] ?? null,
            $company['nom_raison_sociale'] ?? null,
            $company['enseigne'] ?? null,
            $company['sigle'] ?? null,
        ]);

        $legalName = $this->firstNonEmptyString([
            $company['nom_raison_sociale'] ?? null,
            $company['nom_complet'] ?? null,
        ]);

        $fullAddress = $this->firstNonEmptyString([
            $siege['adresse'] ?? null,
            $company['adresse'] ?? null,
        ]);

        $postalCode = $this->firstNonEmptyString([
            $siege['code_postal'] ?? null,
            $company['code_postal'] ?? null,
        ]);

        $city = $this->firstNonEmptyString([
            $siege['libelle_commune'] ?? null,
            $siege['commune'] ?? null,
            $company['ville'] ?? null,
        ]);

        $structureType = $this->firstNonEmptyString([
            $company['forme_juridique'] ?? null,
            $company['nature_juridique'] ?? null,
        ]);

        return [
            'siret' => $siege['siret'] ?? $siret,
            'siren' => $siren,
            'etablissementStatus' => $etablissementStatus,
            'isVerified' => $isVerified,
            'isActive' => $isActive,
            'fields' => [
                'siren' => $siren,
                'companyName' => $companyName ?: null,
                'legalName' => $legalName ?: null,
                'structureType' => $structureType,
                'address' => $fullAddress ?: null,
                'postalCode' => $postalCode,
                'city' => $city,
                'country' => 'France',
            ],
            'display' => [
                [
                    'label' => 'Numéro SIREN',
                    'current' => null,
                    'incoming' => $siren,
                ],
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
                    'incoming' => $structureType,
                ],
                [
                    'label' => 'Adresse',
                    'current' => null,
                    'incoming' => $fullAddress ?: null,
                ],
                [
                    'label' => 'Code postal',
                    'current' => null,
                    'incoming' => $postalCode,
                ],
                [
                    'label' => 'Ville',
                    'current' => null,
                    'incoming' => $city,
                ],
                [
                    'label' => 'Pays',
                    'current' => null,
                    'incoming' => 'France',
                ],
            ],
        ];
    }

    /**
     * @param list<mixed> $values
     */
    private function firstNonEmptyString(array $values): ?string
    {
        foreach ($values as $value) {
            if (!\is_string($value)) {
                continue;
            }

            $trimmedValue = mb_trim($value);

            if ('' !== $trimmedValue) {
                return $trimmedValue;
            }
        }

        return null;
    }
}
