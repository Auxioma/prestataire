<?php

namespace App\Search;

use App\Service\ElasticsearchClient;

final class PrestataireSearchService
{
    private const INDEX_NAME = 'prestataires_search_v1';

    public function __construct(
        private readonly ElasticsearchClient $elasticsearchClient,
    ) {
    }

    public function search(
        ?string $query = null,
        ?string $location = null,
        ?string $subCategorySlug = null,
        int $size = 10,
        int $from = 0,
        ?array $searchedLocation = null,
        int $radiusKm = 25,
    ): array {
        $query = null !== $query ? trim($query) : null;
        $location = null !== $location ? trim($location) : null;
        $subCategorySlug = null !== $subCategorySlug ? trim($subCategorySlug) : null;
        $radiusKm = max(5, min(100, $radiusKm));

        $must = [];
        $filter = [];
        $should = [];

        if ($query) {
            $must[] = [
                'multi_match' => [
                    'query' => $query,
                    'type' => 'best_fields',
                    'fields' => [
                        'companyName^5',
                        'metier^4',
                        'searchText^3',
                        'services.title^4',
                        'services.service.name^4',
                        'categories.name^2',
                        'subCategories.name^3',
                        'city^2',
                    ],
                    'fuzziness' => 'AUTO',
                ],
            ];
        }

        if ($subCategorySlug) {
            $filter[] = [
                'nested' => [
                    'path' => 'subCategories',
                    'query' => [
                        'term' => [
                            'subCategories.slug' => $subCategorySlug,
                        ],
                    ],
                ],
            ];
        }

        if ($location) {
            $should[] = [
                'match' => [
                    'city' => [
                        'query' => $location,
                        'boost' => 4,
                    ],
                ],
            ];

            $should[] = [
                'nested' => [
                    'path' => 'zones',
                    'query' => [
                        'bool' => [
                            'should' => [
                                ['match' => ['zones.city' => ['query' => $location, 'boost' => 3]]],
                                ['match' => ['zones.region' => ['query' => $location, 'boost' => 2]]],
                                ['match' => ['zones.department' => ['query' => $location, 'boost' => 2]]],
                                ['term' => ['zones.postalCode' => $location]],
                            ],
                            'minimum_should_match' => 1,
                        ],
                    ],
                ],
            ];
        }

        $body = [
            'from' => $from,
            'size' => $size,
            'track_total_hits' => true,
            '_source' => [
                'id',
                'slug',
                'companyName',
                'metier',
                'shortDescription',
                'description',
                'city',
                'postalCode',
                'averageRating',
                'reviewsCount',
                'verificationStatus',
                'isFeatured',
                'categories',
                'subCategories',
                'services',
                'zones',
            ],
            'query' => [
                'bool' => array_filter([
                    'must' => $must,
                    'filter' => $filter,
                    'should' => $should,
                ], static fn ($value): bool => !empty($value)),
            ],
            'sort' => [
                ['_score' => ['order' => 'desc']],
                ['isFeatured' => ['order' => 'desc']],
                ['averageRating' => ['order' => 'desc']],
                ['reviewsCount' => ['order' => 'desc']],
            ],
        ];


        $response = $this->elasticsearchClient->getClient()->search([
            'index' => self::INDEX_NAME,
            'body' => $body,
        ])->asArray();

        return [
            'total' => $response['hits']['total']['value'] ?? 0,
            'hits' => array_map(
                static fn (array $hit): array => [
                    'score' => $hit['_score'] ?? null,
                    ...($hit['_source'] ?? []),
                ],
                $response['hits']['hits'] ?? []
            ),
        ];
    }

    public function autocomplete(string $query, int $size = 5): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return [];
        }

        $response = $this->elasticsearchClient->getClient()->search([
            'index' => self::INDEX_NAME,
            'body' => [
                'size' => $size,
                '_source' => [
                    'id',
                    'slug',
                    'companyName',
                    'metier',
                    'city',
                    'averageRating',
                    'reviewsCount',
                    'categories',
                    'subCategories',
                    'services',
                ],
                'query' => [
                    'bool' => [
                        'should' => [
                            [
                                'multi_match' => [
                                    'query' => $query,
                                    'type' => 'phrase_prefix',
                                    'fields' => [
                                        'metier^8',
                                        'companyName^6',
                                        'city^6',
                                        'searchText^3',
                                        'services.title^5',
                                        'services.service.name^5',
                                        'subCategories.name^4',
                                        'categories.name^3',
                                    ],
                                ],
                            ],
                            [
                                'multi_match' => [
                                    'query' => $query,
                                    'type' => 'best_fields',
                                    'fields' => [
                                        'metier^8',
                                        'companyName^6',
                                        'city^6',
                                        'searchText^2',
                                        'services.title^5',
                                        'services.service.name^5',
                                        'subCategories.name^4',
                                        'categories.name^3',
                                    ],
                                    'operator' => 'and',
                                    'fuzziness' => 'AUTO',
                                ],
                            ],
                        ],
                        'minimum_should_match' => 1,
                    ],
                ],
                'sort' => [
                    ['_score' => ['order' => 'desc']],
                    ['isFeatured' => ['order' => 'desc']],
                    ['averageRating' => ['order' => 'desc']],
                ],
            ],
        ])->asArray();

        return array_map(
            static fn (array $hit): array => $hit['_source'] ?? [],
            $response['hits']['hits'] ?? []
        );
    }
}