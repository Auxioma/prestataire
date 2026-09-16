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

namespace App\Command;

use App\Repository\PrestataireSearchReadRepository;
use App\Search\PrestataireDocumentMapper;
use App\Search\PrestataireIndexDefinition;
use App\Search\PrestataireSearchEligibility;
use App\Service\ElasticsearchClient;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:elasticsearch:reindex-prestataires', description: 'Reconstruit un index correct depuis la base et bascule atomiquement l’alias de recherche.')]
final class ElasticsearchReindexPrestatairesCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly PrestataireSearchReadRepository $profiles,
        private readonly PrestataireDocumentMapper $mapper,
        private readonly ElasticsearchClient $elasticsearchClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('replicas', null, InputOption::VALUE_REQUIRED, 'Nombre de répliques ; 0 pour une instance locale à un seul nœud.', '1');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $replicas = filter_var($input->getOption('replicas'), \FILTER_VALIDATE_INT);
        if (false === $replicas || $replicas < 0) {
            $io->error('Le nombre de répliques doit être un entier positif ou nul.');

            return Command::INVALID;
        }
        if (!$this->connection->fetchOne('SELECT pg_try_advisory_lock(842610)')) {
            $io->error('Une reconstruction ou synchronisation est déjà en cours. Réessayez.');

            return Command::FAILURE;
        }
        $index = PrestataireIndexDefinition::newIndexName();
        try {
            $client = $this->elasticsearchClient->getClient();
            $definition = PrestataireIndexDefinition::body();
            $definition['settings']['number_of_replicas'] = $replicas;
            $client->indices()->create(['index' => $index, 'body' => $definition]);
            $indexed = 0;
            $cursor = '0';
            do {
                $ids = $this->connection->fetchFirstColumn('SELECT id FROM prestataire_profile WHERE id > ? ORDER BY id LIMIT 100', [$cursor]);
                $profiles = $this->profiles->findFreshBatch($ids);
                $body = [];
                foreach ($ids as $id) {
                    $cursor = (string) $id;
                    $profile = $profiles[$cursor] ?? null;
                    if (null === $profile || !PrestataireSearchEligibility::isEligible($profile)) {
                        continue;
                    }
                    $body[] = ['index' => ['_index' => $index, '_id' => $cursor]];
                    $body[] = $this->mapper->map($profile);
                    ++$indexed;
                }
                if ([] !== $body && $client->bulk(['body' => $body])->asArray()['errors']) {
                    throw new \RuntimeException('Un document du lot n’a pas pu être indexé.');
                }
            } while (100 === \count($ids));
            $client->indices()->refresh(['index' => $index]);
            $count = $client->count(['index' => $index])->asArray()['count'];
            if ($count !== $indexed) {
                throw new \RuntimeException('Le nombre de documents indexés est incohérent.');
            }
            $actions = [];
            if ($client->indices()->existsAlias(['name' => PrestataireIndexDefinition::ALIAS])->asBool()) {
                foreach (array_keys($client->indices()->getAlias(['name' => PrestataireIndexDefinition::ALIAS])->asArray()) as $oldIndex) {
                    $actions[] = ['remove' => ['index' => $oldIndex, 'alias' => PrestataireIndexDefinition::ALIAS]];
                }
            }
            $actions[] = ['add' => ['index' => $index, 'alias' => PrestataireIndexDefinition::ALIAS, 'is_write_index' => true]];
            $client->indices()->updateAliases(['body' => ['actions' => $actions]]);
            $io->success(\sprintf('%d prestataire(s) indexé(s). Alias %s → %s. Les anciens index sont conservés.', $indexed, PrestataireIndexDefinition::ALIAS, $index));
            $io->note('Les modifications intervenues pendant la reconstruction restent dans la file. Traitez-la avec app:elasticsearch:process-queue.');

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            $io->error(\sprintf('Reconstruction échouée (%s). Les anciens index sont conservés ; vérifiez le nouvel index %s.', $exception::class, $index));

            return Command::FAILURE;
        } finally {
            $this->connection->fetchOne('SELECT pg_advisory_unlock(842610)');
        }
    }
}
