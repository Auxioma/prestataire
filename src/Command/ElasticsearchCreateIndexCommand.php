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

use App\Search\PrestataireIndexDefinition;
use App\Service\ElasticsearchClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:elasticsearch:create-index', description: 'Crée un nouvel index sans supprimer les index existants.')]
final class ElasticsearchCreateIndexCommand extends Command
{
    public function __construct(private readonly ElasticsearchClient $elasticsearchClient)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('index', null, InputOption::VALUE_REQUIRED, 'Nom du nouvel index physique.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $index = $input->getOption('index') ?? PrestataireIndexDefinition::newIndexName();
        if (!\is_string($index) || !preg_match('/^prestataires_search_v2_[a-z0-9_]+$/', $index)) {
            $io->error('Utilisez un nom commençant par prestataires_search_v2_.');

            return Command::INVALID;
        }
        try {
            $this->elasticsearchClient->getClient()->indices()->create(['index' => $index, 'body' => PrestataireIndexDefinition::body()]);
            $io->success(\sprintf('Index %s créé. Aucun index supprimé. Utilisez reindex-prestataires pour reconstruire et activer la recherche.', $index));

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            $io->error('Création impossible : '.$exception::class);

            return Command::FAILURE;
        }
    }
}
