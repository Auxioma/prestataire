<?php

namespace App\Command;

use App\Repository\Subscription\SubscriptionPlanPriceRepository;
use App\Service\Subscription\StripeApiClient;
use App\Service\Subscription\StripeCatalogManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:stripe:sync-subscription-prices')]
final class StripeSyncSubscriptionPricesCommand extends Command
{
    public function __construct(
        private readonly StripeApiClient $stripeApiClient,
        private readonly SubscriptionPlanPriceRepository $subscriptionPlanPriceRepository,
        private readonly StripeCatalogManager $stripeCatalogManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->stripeApiClient->isConfigured()) {
            $io->error('Stripe n’est pas configuré sur cet environnement.');

            return Command::FAILURE;
        }

        $prices = $this->subscriptionPlanPriceRepository->findStripeSyncCandidates();
        if ([] === $prices) {
            $io->warning('Aucun tarif actif à synchroniser.');

            return Command::SUCCESS;
        }

        foreach ($prices as $price) {
            try {
                $result = $this->stripeCatalogManager->syncPlanPrice($price);
            } catch (\Throwable $exception) {
                $io->error(sprintf('%s : %s', (string) $price, $exception->getMessage()));

                return Command::FAILURE;
            }

            if (null !== $result['reason']) {
                $io->text(sprintf('%s : %s', (string) $price, $result['reason']));

                continue;
            }

            $io->success(sprintf(
                '%s synchronisé. Product: %s / Price: %s%s',
                (string) $price,
                $result['product_id'] ?? 'n/a',
                $result['price_id'] ?? 'n/a',
                $result['created'] ? ' (créé ou renouvelé)' : ' (déjà conforme)'
            ));
        }

        return Command::SUCCESS;
    }
}
