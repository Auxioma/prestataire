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

use App\Service\PrestataireSearchQueue;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:elasticsearch:process-queue', description: 'Traite les synchronisations en attente et reprend les échecs Elasticsearch.')]
final class ElasticsearchProcessQueueCommand extends Command
{
    public function __construct(private readonly PrestataireSearchQueue $queue)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Nombre maximum de tâches.', '100');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->queue->process(max(1, min(10000, (int) $input->getOption('limit'))));
        (new SymfonyStyle($input, $output))->note(\sprintf('%d synchronisation(s) réussie(s), %d échec(s) conservé(s) pour reprise.', $result['processed'], $result['failed']));

        return $result['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
