<?php

namespace App\Search\DataFixtures;

use App\Review\DataFixtures\FavoriteFixtures;

use App\Core\DataFixtures\FixtureLoadContext;

use App\Messaging\DataFixtures\NotificationFixtures;

use App\Prestataire\DataFixtures\PrestationMediaFixtures;

use App\Quote\DataFixtures\QuoteProposalItemFixtures;

use App\Messaging\DataFixtures\MessageAttachmentFixtures;

use App\Prestataire\DataFixtures\PrestataireDocumentFixtures;

use App\Prestataire\DataFixtures\PrestataireAppointmentFixtures;

use App\Prestataire\DataFixtures\PrestataireAvailabilityFixtures;

use App\Subscription\DataFixtures\SubscriptionCreditMovementFixtures;

use App\Prestataire\DataFixtures\PrestataireInterventionZoneFixtures;

use App\Search\Command\ElasticsearchReindexPrestatairesCommand;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class PrestataireSearchIndexFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly ElasticsearchReindexPrestatairesCommand $reindex,
        private readonly FixtureLoadContext $context,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        if ($this->context->isDryRun()) {
            return;
        }

        $input = new ArrayInput([]);
        $input->setInteractive(false);
        $output = new BufferedOutput();

        if (Command::SUCCESS !== $this->reindex->run($input, $output)) {
            throw new \RuntimeException('Indexation Elasticsearch des fixtures échouée : '.trim($output->fetch()));
        }
    }

    public function getDependencies(): array
    {
        // The terminal fixtures cover every branch of the fixture dependency graph.
        return [
            FavoriteFixtures::class,
            MessageAttachmentFixtures::class,
            NotificationFixtures::class,
            PrestataireAppointmentFixtures::class,
            PrestataireAvailabilityFixtures::class,
            PrestataireDocumentFixtures::class,
            PrestataireInterventionZoneFixtures::class,
            PrestationMediaFixtures::class,
            QuoteProposalItemFixtures::class,
            SubscriptionCreditMovementFixtures::class,
        ];
    }
}
