<?php

namespace App\Search;

use App\Service\ElasticsearchClient;

final class PrestataireSearchService
{
    private const INDEX_NAME = 'prestataires_search_v1';
    private const ACTIVE_PROFILE_STATUS = 'ACTIVE';

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

        $filter[] = [
            'term' => [
                'profileStatus.keyword' => self::ACTIVE_PROFILE_STATUS,
            ],
        ];

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

        if (
            null !== $searchedLocation
            && isset($searchedLocation['latitude'], $searchedLocation['longitude'])
        ) {
            $filter[] = $location
                ? [
                    'bool' => [
                        'should' => [
                            $this->buildReachableLocationFilter($searchedLocation, $radiusKm),
                            $this->buildLocationTextFilter($location),
                        ],
                        'minimum_should_match' => 1,
                    ],
                ]
                : $this->buildReachableLocationFilter($searchedLocation, $radiusKm);

            $should[] = [
                'nested' => [
                    'path' => 'zones',
                    'score_mode' => 'max',
                    'query' => [
                        'bool' => [
                            'should' => [
                                [
                                    'term' => [
                                        'zones.isMainZone' => true,
                                    ],
                                ],
                            ],
                            'minimum_should_match' => 1,
                        ],
                    ],
                ],
            ];
        }

        if ($query) {
            $must[] = [
                'bool' => [
                    'should' => [
                        [
                            'multi_match' => [
                                'query' => $query,
                                'type' => 'best_fields',
                                'fields' => [
                                    'companyName^4',
                                    'metier^8',
                                    'searchText^6',
                                    'shortDescription^2',
                                    'description^1',
                                ],
                                'operator' => 'and',
                                'fuzziness' => 'AUTO',
                            ],
                        ],
                        [
                            'nested' => [
                                'path' => 'services',
                                'score_mode' => 'max',
                                'query' => [
                                    'multi_match' => [
                                        'query' => $query,
                                        'type' => 'best_fields',
                                        'fields' => [
                                            'services.title^5',
                                            'services.service.name^8',
                                            'services.shortDescription^2',
                                            'services.description^1',
                                        ],
                                        'operator' => 'and',
                                        'fuzziness' => 'AUTO',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'nested' => [
                                'path' => 'subCategories',
                                'score_mode' => 'max',
                                'query' => [
                                    'match' => [
                                        'subCategories.name' => [
                                            'query' => $query,
                                            'boost' => 7,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'nested' => [
                                'path' => 'categories',
                                'score_mode' => 'max',
                                'query' => [
                                    'match' => [
                                        'categories.name' => [
                                            'query' => $query,
                                            'boost' => 3,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'minimum_should_match' => 1,
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
                'term' => [
                    'postalCode' => [
                        'value' => $location,
                        'boost' => 5,
                    ],
                ],
            ];

            $should[] = [
                'nested' => [
                    'path' => 'zones',
                    'score_mode' => 'max',
                    'query' => [
                        'bool' => [
                            'should' => [
                                ['match' => ['zones.city' => ['query' => $location, 'boost' => 4]]],
                                ['match' => ['zones.region' => ['query' => $location, 'boost' => 2]]],
                                ['match' => ['zones.department' => ['query' => $location, 'boost' => 2]]],
                                ['term' => ['zones.postalCode' => ['value' => $location, 'boost' => 5]]],
                            ],
                            'minimum_should_match' => 1,
                        ],
                    ],
                ],
            ];

            if (null === $searchedLocation || !isset($searchedLocation['latitude'], $searchedLocation['longitude'])) {
                $filter[] = $this->buildLocationTextFilter($location);
            }
        }

        $boolQuery = array_filter([
            'filter' => $filter,
            'must' => $must,
            'should' => $should,
        ], static fn ($value): bool => null !== $value && [] !== $value);

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
                'profileStatus',
                'categories',
                'subCategories',
                'services',
                'zones',
            ],
            'query' => [
                'bool' => $boolQuery,
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

        $hits = array_map(
            static fn (array $hit): array => [
                'score' => $hit['_score'] ?? null,
                ...($hit['_source'] ?? []),
            ],
            $response['hits']['hits'] ?? []
        );

        $hits = array_values(array_filter($hits, static function (array $hit): bool {
            return !empty($hit['id']) && !empty($hit['slug']);
        }));

        return [
            'total' => $response['hits']['total']['value'] ?? 0,
            'hits' => $hits,
        ];
    }

    /**
     * @param array<string, mixed> $searchedLocation
     *
     * @return array<string, mixed>
     */
    private function buildReachableLocationFilter(array $searchedLocation, int $radiusKm): array
    {
        return [
            'nested' => [
                'path' => 'zones',
                'query' => [
                    'script' => [
                        'script' => [
                            'lang' => 'painless',
                            'source' => <<<'PAINLESS'
if (doc['zones.location'].empty) {
    return false;
}

double distanceMeters = doc['zones.location'].arcDistance(params.lat, params.lon);
double zoneRadiusKm = doc['zones.radiusKm'].empty ? 0 : doc['zones.radiusKm'].value;

return distanceMeters <= ((zoneRadiusKm + params.radiusKm) * 1000.0);
PAINLESS,
                            'params' => [
                                'lat' => (float) $searchedLocation['latitude'],
                                'lon' => (float) $searchedLocation['longitude'],
                                'radiusKm' => $radiusKm,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildLocationTextFilter(string $location): array
    {
        return [
            'bool' => [
                'should' => [
                    [
                        'match' => [
                            'city' => [
                                'query' => $location,
                            ],
                        ],
                    ],
                    [
                        'term' => [
                            'postalCode' => [
                                'value' => $location,
                            ],
                        ],
                    ],
                    [
                        'nested' => [
                            'path' => 'zones',
                            'query' => [
                                'bool' => [
                                    'should' => [
                                        ['match' => ['zones.city' => ['query' => $location]]],
                                        ['match' => ['zones.region' => ['query' => $location]]],
                                        ['match' => ['zones.department' => ['query' => $location]]],
                                        ['term' => ['zones.postalCode' => ['value' => $location]]],
                                    ],
                                    'minimum_should_match' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
                'minimum_should_match' => 1,
            ],
        ];
    }

    public function autocomplete(string $query, int $size = 5): array
    {
        $query = trim($query);
        $query = mb_substr($query, 0, 100);

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
                    'isFeatured',
                    'profileStatus',
                    'categories',
                    'subCategories',
                    'services',
                ],
                'query' => [
                    'bool' => [
                        'filter' => [
                            [
                                'term' => [
                                    'profileStatus.keyword' => self::ACTIVE_PROFILE_STATUS,
                                ],
                            ],
                        ],
                        'must' => [
                            [
                                'bool' => [
                                    'should' => [
                                        [
                                            'multi_match' => [
                                                'query' => $query,
                                                'type' => 'phrase_prefix',
                                                'fields' => [
                                                    'metier^8',
                                                    'companyName^6',
                                                    'city^4',
                                                    'searchText^4',
                                                ],
                                            ],
                                        ],
                                        [
                                            'nested' => [
                                                'path' => 'services',
                                                'score_mode' => 'max',
                                                'query' => [
                                                    'multi_match' => [
                                                        'query' => $query,
                                                        'type' => 'phrase_prefix',
                                                        'fields' => [
                                                            'services.title^5',
                                                            'services.service.name^7',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            'nested' => [
                                                'path' => 'subCategories',
                                                'score_mode' => 'max',
                                                'query' => [
                                                    'match_phrase_prefix' => [
                                                        'subCategories.name' => [
                                                            'query' => $query,
                                                            'boost' => 5,
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            'nested' => [
                                                'path' => 'categories',
                                                'score_mode' => 'max',
                                                'query' => [
                                                    'match_phrase_prefix' => [
                                                        'categories.name' => [
                                                            'query' => $query,
                                                            'boost' => 3,
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    'minimum_should_match' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
                'sort' => [
                    ['_score' => ['order' => 'desc']],
                    ['isFeatured' => ['order' => 'desc']],
                    ['averageRating' => ['order' => 'desc']],
                ],
            ],
        ])->asArray();

        $items = array_map(
            static fn (array $hit): array => $hit['_source'] ?? [],
            $response['hits']['hits'] ?? []
        );

        return array_values(array_filter($items, static function (array $item): bool {
            return !empty($item['id']) && !empty($item['slug']);
        }));
    }

    public function browseSearch(
        ?string $query = null,
        ?string $categorySlug = null,
        ?string $subCategorySlug = null,
        string $sort = 'relevance',
        int $size = 9,
        int $from = 0,
    ): array {
        $query = null !== $query ? trim($query) : null;
        $categorySlug = null !== $categorySlug ? trim($categorySlug) : null;
        $subCategorySlug = null !== $subCategorySlug ? trim($subCategorySlug) : null;
        $sort = \in_array($sort, ['relevance', 'rating', 'reviews', 'alphabetical'], true) ? $sort : 'relevance';

        $filter = [
            [
                'term' => [
                    'profileStatus.keyword' => self::ACTIVE_PROFILE_STATUS,
                ],
            ],
        ];
        $must = [];

        if ($categorySlug) {
            $filter[] = [
                'nested' => [
                    'path' => 'categories',
                    'query' => [
                        'term' => [
                            'categories.slug' => $categorySlug,
                        ],
                    ],
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

        if ($query) {
            $must[] = [
                'bool' => [
                    'should' => [
                        [
                            'multi_match' => [
                                'query' => $query,
                                'type' => 'best_fields',
                                'fields' => [
                                    'companyName^8',
                                    'metier^7',
                                    'searchText^5',
                                    'shortDescription^2',
                                    'description^1',
                                ],
                                'operator' => 'and',
                                'fuzziness' => 'AUTO',
                            ],
                        ],
                        [
                            'nested' => [
                                'path' => 'services',
                                'score_mode' => 'max',
                                'query' => [
                                    'multi_match' => [
                                        'query' => $query,
                                        'type' => 'best_fields',
                                        'fields' => [
                                            'services.title^6',
                                            'services.service.name^8',
                                            'services.shortDescription^2',
                                        ],
                                        'operator' => 'and',
                                        'fuzziness' => 'AUTO',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'nested' => [
                                'path' => 'subCategories',
                                'score_mode' => 'max',
                                'query' => [
                                    'match' => [
                                        'subCategories.name' => [
                                            'query' => $query,
                                            'boost' => 5,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'nested' => [
                                'path' => 'categories',
                                'score_mode' => 'max',
                                'query' => [
                                    'match' => [
                                        'categories.name' => [
                                            'query' => $query,
                                            'boost' => 4,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'minimum_should_match' => 1,
                ],
            ];
        }

        $sortDefinition = match ($sort) {
            'rating' => [
                ['averageRating' => ['order' => 'desc']],
                ['reviewsCount' => ['order' => 'desc']],
                ['companyName.keyword' => ['order' => 'asc']],
            ],
            'reviews' => [
                ['reviewsCount' => ['order' => 'desc']],
                ['averageRating' => ['order' => 'desc']],
                ['companyName.keyword' => ['order' => 'asc']],
            ],
            'alphabetical' => [
                ['companyName.keyword' => ['order' => 'asc']],
                ['averageRating' => ['order' => 'desc']],
            ],
            default => [
                ['_score' => ['order' => 'desc']],
                ['isFeatured' => ['order' => 'desc']],
                ['averageRating' => ['order' => 'desc']],
                ['reviewsCount' => ['order' => 'desc']],
            ],
        };

        $response = $this->elasticsearchClient->getClient()->search([
            'index' => self::INDEX_NAME,
            'body' => [
                'from' => $from,
                'size' => $size,
                'track_total_hits' => true,
                '_source' => [
                    'id',
                    'slug',
                    'companyName',
                    'averageRating',
                    'reviewsCount',
                ],
                'query' => [
                    'bool' => array_filter([
                        'filter' => $filter,
                        'must' => $must,
                    ], static fn (mixed $value): bool => [] !== $value),
                ],
                'sort' => $sortDefinition,
            ],
        ])->asArray();

        $hits = array_map(
            static fn (array $hit): array => [
                'score' => $hit['_score'] ?? null,
                ...($hit['_source'] ?? []),
            ],
            $response['hits']['hits'] ?? []
        );

        $hits = array_values(array_filter($hits, static function (array $hit): bool {
            return !empty($hit['id']) && !empty($hit['slug']);
        }));

        return [
            'total' => $response['hits']['total']['value'] ?? 0,
            'hits' => $hits,
        ];
    }
}
