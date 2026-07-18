<?php

namespace App\Command;

use App\Entity\PrestataireProfile;
use App\Enum\PrestataireProfileStatusEnum;
use App\Repository\PrestataireProfileRepository;
use App\Search\PrestataireDocumentMapper;
use App\Service\ElasticsearchClient;
use App\Service\PrestataireProfileManager;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:elasticsearch:reindex-prestataires',
    description: 'Réindexe les prestataires actifs dans Elasticsearch'
)]
final class ElasticsearchReindexPrestatairesCommand extends Command
{
    private const INDEX_NAME = 'prestataires_search_v1';

    public function __construct(
        private readonly PrestataireProfileRepository $prestataireProfileRepository,
        private readonly PrestataireDocumentMapper $prestataireDocumentMapper,
        private readonly ElasticsearchClient $elasticsearchClient,
        private readonly PrestataireProfileManager $prestataireProfileManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $client = $this->elasticsearchClient->getClient();

        try {
            $prestataires = $this->prestataireProfileRepository->findBy([
                'profileStatus' => PrestataireProfileStatusEnum::ACTIVE,
            ]);

            if ([] === $prestataires) {
                $io->warning('Aucun prestataire actif à indexer.');

                return Command::SUCCESS;
            }

            /** @var PrestataireProfile $prestataire */
            foreach ($prestataires as $prestataire) {
                $this->prestataireProfileManager->syncSlug($prestataire);
            }

            $this->prestataireProfileRepository->getEntityManager()->flush();

            $indexed = 0;

            /** @var PrestataireProfile $prestataire */
            foreach ($prestataires as $prestataire) {
                $document = $this->prestataireDocumentMapper->map($prestataire);

                $client->index([
                    'index' => self::INDEX_NAME,
                    'id' => (string) $prestataire->getId(),
                    'body' => $document,
                    'refresh' => false,
                ]);

                ++$indexed;
            }

            $client->indices()->refresh([
                'index' => self::INDEX_NAME,
            ]);

            $io->success(sprintf('%d prestataire(s) réindexé(s) dans "%s".', $indexed, self::INDEX_NAME));

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
