<?php

namespace App\Service\Subscription;

use App\Entity\PrestataireProfile;
use App\Entity\Subscription\SubscriptionCustomer;
use App\Repository\Subscription\SubscriptionCustomerRepository;
use Doctrine\ORM\EntityManagerInterface;

final class StripeCustomerManager
{
    public function __construct(
        private readonly SubscriptionCustomerRepository $subscriptionCustomerRepository,
        private readonly StripeApiClient $stripeApiClient,
        private readonly StripeReferenceHelper $stripeReferenceHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getManagedCustomerForPrestataire(PrestataireProfile $prestataireProfile): ?SubscriptionCustomer
    {
        $customer = $this->subscriptionCustomerRepository->findOneByPrestataire($prestataireProfile);

        return $this->stripeReferenceHelper->isManagedCustomer($customer) ? $customer : null;
    }

    public function findOrCreateForPrestataire(PrestataireProfile $prestataireProfile): SubscriptionCustomer
    {
        $customer = $this->subscriptionCustomerRepository->findOneByPrestataire($prestataireProfile);
        if ($this->stripeReferenceHelper->isManagedCustomer($customer)) {
            return $customer;
        }

        $stripeCustomer = $this->stripeApiClient->createCustomer($prestataireProfile);
        $stripeCustomerId = trim((string) ($stripeCustomer['id'] ?? ''));

        if ('' === $stripeCustomerId) {
            throw new \RuntimeException('Stripe n’a pas retourné d’identifiant client.');
        }

        $customer ??= (new SubscriptionCustomer())
            ->setPrestataireProfile($prestataireProfile);

        $customer
            ->setStripeCustomerId($stripeCustomerId)
            ->setBillingEmail($prestataireProfile->getAccount()?->getEmail())
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $customer;
    }

    public function isManagedStripeCustomer(?SubscriptionCustomer $customer): bool
    {
        return $this->stripeReferenceHelper->isManagedCustomer($customer);
    }
}
