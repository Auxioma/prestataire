<?php

namespace App\Search;

use App\Entity\ServiceCategory;
use App\Repository\ServiceCategoryRepository;
use App\Service\ElasticsearchClient;

final class CategorySearchService
{
    private const INDEX_NAME = 'prestataires_search_v1';
    private const ACTIVE_PROFILE_STATUS = 'ACTIVE';

    public function __construct(
        private readonly ElasticsearchClient $elasticsearchClient,
        private readonly ServiceCategoryRepository $categoryRepository,
    ) {
    }

    /**
     * @return array<int, array{category: ServiceCategory, providerCount: int, subCategoryCount: int}>
     */
    public function search(
        ?string $query = null,
        ?array $searchedLocation = null,
        int $radiusKm = 25,
        string $sort = 'providers',
    ): array {
        $query = null !== $query ? trim($query) : '';
        $radiusKm = max(5, min(100, $radiusKm));
        $sort = \in_array($sort, ['providers', 'alphabetical', 'recent'], true) ? $sort : 'providers';

        $categories = $this->categoryRepository->findTopLevelWithActiveSubCategories();
        $providerCounts = $this->getProviderCounts($query !== '' ? $query : null, $searchedLocation, $radiusKm);
        $hasLocationFilter = null !== $searchedLocation
            && isset($searchedLocation['latitude'], $searchedLocation['longitude']);

        $rows = [];

        foreach ($categories as $category) {
            $providerCount = $providerCounts[(string) $category->getId()] ?? 0;
            $matchesText = $this->categoryMatchesQuery($category, $query);

            if ($hasLocationFilter && $providerCount <= 0) {
                continue;
            }

            if (!$hasLocationFilter && $query !== '' && !$matchesText && $providerCount <= 0) {
                continue;
            }

            $rows[] = [
                'category' => $category,
                'providerCount' => $providerCount,
                'subCategoryCount' => $category->getSubCategories()->count(),
            ];
        }

        usort($rows, function (array $left, array $right) use ($sort): int {
            /** @var ServiceCategory $leftCategory */
            $leftCategory = $left['category'];
            /** @var ServiceCategory $rightCategory */
            $rightCategory = $right['category'];

            return match ($sort) {
                'alphabetical' => $this->compareAlphabetical($leftCategory, $rightCategory, $left, $right),
                'recent' => $this->compareRecent($leftCategory, $rightCategory, $left, $right),
                default => $this->compareProviders($leftCategory, $rightCategory, $left, $right),
            };
        });

        return $rows;
    }

    private function compareProviders(
        ServiceCategory $leftCategory,
        ServiceCategory $rightCategory,
        array $left,
        array $right,
    ): int {
        $providerComparison = $right['providerCount'] <=> $left['providerCount'];
        if (0 !== $providerComparison) {
            return $providerComparison;
        }

        $positionComparison = ($leftCategory->getPosition() ?? 0) <=> ($rightCategory->getPosition() ?? 0);
        if (0 !== $positionComparison) {
            return $positionComparison;
        }

        return $this->normalizeText($leftCategory->getName()) <=> $this->normalizeText($rightCategory->getName());
    }

    private function compareAlphabetical(
        ServiceCategory $leftCategory,
        ServiceCategory $rightCategory,
        array $left,
        array $right,
    ): int {
        $nameComparison = $this->normalizeText($leftCategory->getName()) <=> $this->normalizeText($rightCategory->getName());
        if (0 !== $nameComparison) {
            return $nameComparison;
        }

        return $right['providerCount'] <=> $left['providerCount'];
    }

    private function compareRecent(
        ServiceCategory $leftCategory,
        ServiceCategory $rightCategory,
        array $left,
        array $right,
    ): int {
        $leftDate = $leftCategory->getUpdatedAt() ?? $leftCategory->getCreatedAt();
        $rightDate = $rightCategory->getUpdatedAt() ?? $rightCategory->getCreatedAt();

        $dateComparison = ($rightDate?->getTimestamp() ?? 0) <=> ($leftDate?->getTimestamp() ?? 0);
        if (0 !== $dateComparison) {
            return $dateComparison;
        }

        return $right['providerCount'] <=> $left['providerCount'];
    }

    private function categoryMatchesQuery(ServiceCategory $category, string $query): bool
    {
        if ('' === $query) {
            return true;
        }

        $needles = [
            $category->getName(),
            $category->getSlug(),
            $category->getDescription(),
        ];

        foreach ($category->getSubCategories() as $subCategory) {
            $needles[] = $subCategory->getName();
            $needles[] = $subCategory->getSlug();
            $needles[] = $subCategory->getDescription();
        }

        $normalizedQuery = $this->normalizeText($query);

        foreach ($needles as $value) {
            if (str_contains($this->normalizeText($value), $normalizedQuery)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, int>
     */
    private function getProviderCounts(?string $query, ?array $searchedLocation, int $radiusKm): array
    {
        $filter = [
            [
                'term' => [
                    'profileStatus.keyword' => self::ACTIVE_PROFILE_STATUS,
                ],
            ],
        ];
        $must = [];

        if (
            null !== $searchedLocation
            && isset($searchedLocation['latitude'], $searchedLocation['longitude'])
        ) {
            $filter[] = [
                'nested' => [
                    'path' => 'zones',
                    'query' => [
                        'geo_distance' => [
                            'distance' => $radiusKm.'km',
                            'zones.location' => [
                                'lat' => (float) $searchedLocation['latitude'],
                                'lon' => (float) $searchedLocation['longitude'],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if (null !== $query && '' !== $query) {
            $must[] = [
                'bool' => [
                    'should' => [
                        [
                            'nested' => [
                                'path' => 'categories',
                                'score_mode' => 'max',
                                'query' => [
                                    'match' => [
                                        'categories.name' => [
                                            'query' => $query,
                                            'boost' => 8,
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
                                    'match' => [
                                        'subCategories.name' => [
                                            'query' => $query,
                                            'boost' => 10,
                                        ],
                                    ],
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
                                        'type' => 'best_fields',
                                        'fields' => [
                                            'services.title^6',
                                            'services.service.name^8',
                                        ],
                                        'operator' => 'and',
                                        'fuzziness' => 'AUTO',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'multi_match' => [
                                'query' => $query,
                                'type' => 'best_fields',
                                'fields' => [
                                    'metier^4',
                                    'searchText^3',
                                ],
                                'operator' => 'and',
                                'fuzziness' => 'AUTO',
                            ],
                        ],
                    ],
                    'minimum_should_match' => 1,
                ],
            ];
        }

        try {
            $response = $this->elasticsearchClient->getClient()->search([
                'index' => self::INDEX_NAME,
                'body' => [
                    'size' => 0,
                    'track_total_hits' => false,
                    'query' => [
                        'bool' => array_filter([
                            'filter' => $filter,
                            'must' => $must,
                        ], static fn (mixed $value): bool => [] !== $value),
                    ],
                    'aggs' => [
                        'categories_nested' => [
                            'nested' => [
                                'path' => 'categories',
                            ],
                            'aggs' => [
                                'category_ids' => [
                                    'terms' => [
                                        'field' => 'categories.id',
                                        'size' => 200,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])->asArray();
        } catch (\Throwable) {
            return [];
        }

        $buckets = $response['aggregations']['categories_nested']['category_ids']['buckets'] ?? [];
        $counts = [];

        foreach ($buckets as $bucket) {
            $key = isset($bucket['key']) ? (string) $bucket['key'] : null;
            if (null === $key || '' === $key) {
                continue;
            }

            $counts[$key] = (int) ($bucket['doc_count'] ?? 0);
        }

        return $counts;
    }

    private function normalizeText(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        if ('' === $value) {
            return '';
        }

        if (\function_exists('transliterator_transliterate')) {
            $transliterated = transliterator_transliterate('Any-Latin; Latin-ASCII;', $value);

            return is_string($transliterated) ? $transliterated : $value;
        }

        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return is_string($transliterated) ? $transliterated : $value;
    }
}
