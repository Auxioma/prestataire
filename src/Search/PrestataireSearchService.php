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
    ): array {
        $must = [];
        $filters = [
            ['term' => ['profileStatus' => 'active']],
        ];

        $should = [];

        if ($query && '' !== trim($query)) {
            $must[] = [
                'multi_match' => [
                    'query' => trim($query),
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

        if ($location && '' !== trim($location)) {
            $should[] = [
                'match' => [
                    'city' => [
                        'query' => trim($location),
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
                                [
                                    'match' => [
                                        'zones.city' => [
                                            'query' => trim($location),
                                            'boost' => 3,
                                        ],
                                    ],
                                ],
                                [
                                    'match' => [
                                        'zones.region' => [
                                            'query' => trim($location),
                                            'boost' => 2,
                                        ],
                                    ],
                                ],
                                [
                                    'match' => [
                                        'zones.department' => [
                                            'query' => trim($location),
                                            'boost' => 2,
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'zones.postalCode' => trim($location),
                                    ],
                                ],
                            ],
                            'minimum_should_match' => 1,
                        ],
                    ],
                ],
            ];
        }

        if ($subCategorySlug && '' !== trim($subCategorySlug)) {
            $filters[] = [
                'nested' => [
                    'path' => 'subCategories',
                    'query' => [
                        'term' => [
                            'subCategories.slug' => trim($subCategorySlug),
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
                    'filter' => $filters,
                    'should' => $should,
                    'minimum_should_match' => !empty($should) ? 1 : 0,
                ]),
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

    if ('' === $query || mb_strlen($query) < 3) {
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
                'shortDescription',
                'city',
                'averageRating',
                'reviewsCount',
                'categories',
                'subCategories',
                'services',
            ],
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'multi_match' => [
                                'query' => $query,
                                'type' => 'phrase_prefix',
                                'fields' => [
                                    'metier^8',
                                    'companyName^6',
                                    'searchText^2',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                ['_score' => ['order' => 'desc']],
                ['averageRating' => ['order' => 'desc']],
                ['reviewsCount' => ['order' => 'desc']],
            ],
        ],
    ])->asArray();

    return array_map(
        static fn (array $hit): array => [
            'score' => $hit['_score'] ?? null,
            ...($hit['_source'] ?? []),
        ],
        $response['hits']['hits'] ?? []
    );
}
}