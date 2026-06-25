<?php

namespace App\Command;

use App\Service\ElasticsearchClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:elasticsearch:ping')]
final class ElasticsearchPingCommand extends Command
{
    public function __construct(
        private readonly ElasticsearchClient $elasticsearchClient,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $response = $this->elasticsearchClient->getClient()->info();

            $output->writeln('HTTP status: '.$response->getStatusCode());
            $output->writeln(json_encode($response->asArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('Exception: '.get_class($e));
            $output->writeln('Message: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}