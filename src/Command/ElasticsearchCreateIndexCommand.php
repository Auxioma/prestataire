<?php

namespace App\Command;

use App\Service\ElasticsearchClient;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:elasticsearch:create-index')]
final class ElasticsearchCreateIndexCommand extends Command
{
    private const INDEX_NAME = 'prestataires_search_v1';

    public function __construct(
        private readonly ElasticsearchClient $elasticsearchClient,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $client = $this->elasticsearchClient->getClient();

        try {
            if ($client->indices()->exists(['index' => self::INDEX_NAME])->asBool()) {
                $client->indices()->delete(['index' => self::INDEX_NAME]);
            }

            $client->indices()->create([
                'index' => self::INDEX_NAME,
                'body' => [
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
                            'id' => ['type' => 'integer'],
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
                            'verificationStatus' => ['type' => 'keyword'],
                            'isFeatured' => ['type' => 'boolean'],
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
                ],
            ]);

            $io->success(sprintf('Index %s créé avec succès.', self::INDEX_NAME));

            return Command::SUCCESS;
        } catch (ClientResponseException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}