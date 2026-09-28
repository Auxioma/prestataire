<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 AUXIOMA Web Agency.
 *
 * Projet : TrouveMoi
 *
 * Tous droits réservés.
 *
 * Ce fichier fait partie du projet TrouveMoi,
 * développé par AUXIOMA Web Agency.
 *
 * Toute reproduction, modification, distribution ou utilisation,
 * totale ou partielle, sans autorisation écrite préalable,
 * est strictement interdite.
 */

namespace App\Subscription\Service;

use App\Prestataire\Entity\PrestataireProfile;
use App\Subscription\Entity\SubscriptionCustomer;
use App\Subscription\Repository\SubscriptionCustomerRepository;
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
        $stripeCustomerId = mb_trim((string) ($stripeCustomer['id'] ?? ''));

        if ('' === $stripeCustomerId) {
            throw new \RuntimeException('Stripe n’a pas retourné d’identifiant client.');
        }

        $customer ??= new SubscriptionCustomer()
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

    public function syncDefaultPaymentMethod(
        SubscriptionCustomer $customer,
        ?string $paymentMethodId,
        ?string $paymentMethodType,
        bool $flush = true,
    ): void {
        $customer
            ->setStripeDefaultPaymentMethodId($paymentMethodId)
            ->setDefaultPaymentMethodType($paymentMethodType)
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($customer);

        if ($flush) {
            $this->entityManager->flush();
        }
    }
}
