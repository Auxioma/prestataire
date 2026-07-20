<?php

namespace App\Command;

use App\Repository\Subscription\PrestataireSubscriptionRepository;
use App\Repository\Subscription\SubscriptionCustomerRepository;
use App\Service\Subscription\StripeApiClient;
use App\Service\Subscription\StripeCheckoutSessionSynchronizer;
use App\Service\Subscription\SubscriptionFallbackManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:subscription:daily-maintenance')]
final class SubscriptionDailyMaintenanceCommand extends Command
{
    public function __construct(
        private readonly StripeApiClient $stripeApiClient,
        private readonly StripeCheckoutSessionSynchronizer $stripeCheckoutSessionSynchronizer,
        private readonly SubscriptionCustomerRepository $subscriptionCustomerRepository,
        private readonly PrestataireSubscriptionRepository $prestataireSubscriptionRepository,
        private readonly SubscriptionFallbackManager $subscriptionFallbackManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $syncedCustomers = 0;
        $fallbacksApplied = 0;
        $syncErrors = 0;
        $fallbackErrors = 0;

        if ($this->stripeApiClient->isConfigured()) {
            foreach ($this->subscriptionCustomerRepository->findManagedStripeCustomers() as $customer) {
                $prestataireProfile = $customer->getPrestataireProfile();
                if (null === $prestataireProfile) {
                    continue;
                }

                try {
                    if ($this->stripeCheckoutSessionSynchronizer->syncLatestSubscriptionForPrestataire($prestataireProfile)) {
                        ++$syncedCustomers;
                    }
                } catch (\Throwable $exception) {
                    ++$syncErrors;
                    $io->warning(sprintf(
                        'Synchronisation Stripe échouée pour le prestataire #%s : %s',
                        $prestataireProfile->getId() ?? 'n/a',
                        $exception->getMessage()
                    ));
                }
            }
        } else {
            $io->note('Stripe n’est pas configuré sur cet environnement. La maintenance se limite au fallback local.');
        }

        foreach ($this->prestataireSubscriptionRepository->findSubscriptionsNeedingFreeFallback() as $subscription) {
            try {
                if (!$this->subscriptionFallbackManager->shouldFallbackToFree($subscription)) {
                    continue;
                }

                $prestataireProfile = $subscription->getPrestataireProfile();
                if (null === $prestataireProfile) {
                    continue;
                }

                $this->subscriptionFallbackManager->fallbackToFree(
                    $prestataireProfile,
                    $subscription,
                    'daily_maintenance'
                );
                ++$fallbacksApplied;
            } catch (\Throwable $exception) {
                ++$fallbackErrors;
                $io->warning(sprintf(
                    'Fallback gratuit échoué pour la souscription #%s : %s',
                    $subscription->getId() ?? 'n/a',
                    $exception->getMessage()
                ));
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Maintenance terminée. Sync Stripe: %d, fallbacks gratuits: %d, erreurs sync: %d, erreurs fallback: %d.',
            $syncedCustomers,
            $fallbacksApplied,
            $syncErrors,
            $fallbackErrors
        ));

        return Command::SUCCESS;
    }
}
