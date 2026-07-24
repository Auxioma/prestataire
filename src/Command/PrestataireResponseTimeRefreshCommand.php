<?php

namespace App\Command;

use App\Entity\PrestataireProfile;
use App\Repository\PrestataireProfileRepository;
use App\Service\PrestataireResponseTimeManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:prestataire:refresh-response-time',
    description: 'Recalcule le temps de reponse moyen des prestataires a partir des conversations'
)]
final class PrestataireResponseTimeRefreshCommand extends Command
{
    public function __construct(
        private readonly PrestataireProfileRepository $prestataireProfileRepository,
        private readonly PrestataireResponseTimeManager $prestataireResponseTimeManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $prestataires = $this->prestataireProfileRepository->findAll();

        if ([] === $prestataires) {
            $io->warning('Aucun prestataire a recalculer.');

            return Command::SUCCESS;
        }

        $updatedCount = 0;

        /** @var PrestataireProfile $prestataire */
        foreach ($prestataires as $prestataire) {
            $previousValue = $prestataire->getResponseTimeMinutes();

            $this->prestataireResponseTimeManager->refreshForPrestataire($prestataire);

            if ($previousValue !== $prestataire->getResponseTimeMinutes()) {
                ++$updatedCount;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            '%d prestataire(s) traite(s), %d valeur(s) mise(s) a jour.',
            count($prestataires),
            $updatedCount
        ));

        return Command::SUCCESS;
    }
}
