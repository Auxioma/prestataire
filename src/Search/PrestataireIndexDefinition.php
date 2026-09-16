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

namespace App\Search;

final class PrestataireIndexDefinition
{
    public const ALIAS = 'prestataires_search';

    public static function newIndexName(): string
    {
        return 'prestataires_search_v2_'.gmdate('Ymd_His').'_'.bin2hex(random_bytes(4));
    }

    public static function body(): array
    {
        return [
            'settings' => [
                'analysis' => [
                    'normalizer' => [
                        'lowercase_normalizer' => [
                            'type' => 'custom',
                            'filter' => ['lowercase', 'asciifolding'],
                        ],
                    ],
                ],
            ],
            'mappings' => [
                'properties' => [
                    'id' => ['type' => 'long'],
                    'slug' => ['type' => 'keyword'],

                    'companyName' => [
                        'type' => 'text',
                        'fields' => [
                            'keyword' => [
                                'type' => 'keyword',
                                'normalizer' => 'lowercase_normalizer',
                            ],
                        ],
                    ],

                    'metier' => ['type' => 'text'],
                    'shortDescription' => ['type' => 'text'],
                    'description' => ['type' => 'text'],
                    'longDescription' => ['type' => 'text'],

                    'city' => [
                        'type' => 'text',
                        'fields' => [
                            'keyword' => [
                                'type' => 'keyword',
                                'normalizer' => 'lowercase_normalizer',
                            ],
                        ],
                    ],

                    'postalCode' => ['type' => 'keyword'],
                    'averageRating' => ['type' => 'float'],
                    'reviewsCount' => ['type' => 'integer'],
                    'profileStatus' => ['type' => 'keyword'],
                    'verificationStatus' => ['type' => 'keyword'],
                    'isFeatured' => ['type' => 'boolean'],
                    'searchVisibility' => ['type' => 'keyword'],
                    'legalName' => ['type' => 'text'],
                    'siret' => ['type' => 'keyword'],
                    'searchText' => ['type' => 'text'],

                    'categories' => [
                        'type' => 'nested',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'text'],
                            'slug' => ['type' => 'keyword'],
                            'description' => ['type' => 'text'],
                        ],
                    ],

                    'subCategories' => [
                        'type' => 'nested',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'text'],
                            'slug' => ['type' => 'keyword'],
                            'description' => ['type' => 'text'],
                        ],
                    ],

                    'services' => [
                        'type' => 'nested',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'slug' => ['type' => 'keyword'],
                            'title' => ['type' => 'text'],
                            'shortDescription' => ['type' => 'text'],
                            'description' => ['type' => 'text'],
                            'pricingType' => ['type' => 'keyword'],
                            'priceFrom' => ['type' => 'float'],
                            'priceTo' => ['type' => 'float'],
                            'priceUnit' => ['type' => 'keyword'],
                            'service' => [
                                'properties' => [
                                    'id' => ['type' => 'integer'],
                                    'name' => ['type' => 'text'],
                                    'slug' => ['type' => 'keyword'],
                                    'description' => ['type' => 'text'],
                                ],
                            ],
                        ],
                    ],

                    'zones' => [
                        'type' => 'nested',
                        'properties' => [
                            'city' => [
                                'type' => 'text',
                                'fields' => [
                                    'keyword' => [
                                        'type' => 'keyword',
                                        'normalizer' => 'lowercase_normalizer',
                                    ],
                                ],
                            ],
                            'postalCode' => ['type' => 'keyword'],
                            'department' => ['type' => 'text'],
                            'region' => ['type' => 'text'],
                            'radiusKm' => ['type' => 'integer'],
                            'isMainZone' => ['type' => 'boolean'],
                            'location' => ['type' => 'geo_point'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
