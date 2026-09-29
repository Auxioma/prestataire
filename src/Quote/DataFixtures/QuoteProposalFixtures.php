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

namespace App\Quote\DataFixtures;

use App\Core\DataFixtures\BaseFixture;
use App\Messaging\DataFixtures\ConversationFixtures;
use App\Messaging\Entity\Conversation;
use App\Quote\Entity\QuoteProposal;
use App\Quote\Entity\QuoteRequest;
use App\Quote\Enum\QuoteProposalStatusEnum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class QuoteProposalFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $statuses = [
            QuoteProposalStatusEnum::FINALIZED,
            QuoteProposalStatusEnum::ACCEPTED,
            QuoteProposalStatusEnum::DRAFT,
            QuoteProposalStatusEnum::ARCHIVED,
        ];

        for ($i = 1; $i <= 18; ++$i) {
            /** @var QuoteRequest $request */
            $request = $this->getReference(\sprintf('quote_request_%d', $i), QuoteRequest::class);
            /** @var Conversation $conversation */
            $conversation = $this->getReference(\sprintf('conversation_%d', $i), Conversation::class);
            $prestataire = $request->getPrestataire();
            $client = $request->getClient();
            $subtotal = $this->decimal(120, 2800);
            $tax = number_format((float) $subtotal * 0.2, 2, '.', '');
            $total = number_format((float) $subtotal + (float) $tax, 2, '.', '');
            $status = $statuses[($i - 1) % \count($statuses)];

            $proposal = (new QuoteProposal())
                ->setQuoteRequest($request)
                ->setPrestataire($prestataire)
                ->setClient($client)
                ->setConversation($conversation)
                ->setStatus($status)
                ->setProposalNumber(\sprintf('DEV-2026-%05d', $i))
                ->setPublicReference(mb_strtoupper($this->faker->bothify('TMDEV###??')))
                ->setTitle('Devis pour '.$request->getTitle())
                ->setIntroMessage('Merci pour votre demande. Voici une proposition adaptée à votre besoin.')
                ->setNotes('Prix indicatifs sous réserve de visite complémentaire si nécessaire.')
                ->setTerms('Paiement à 30 jours. Intervention planifiée après validation écrite du client.')
                ->setValidUntil($this->faker->dateTimeBetween('+10 days', '+45 days'))
                ->setFinalizedAt(QuoteProposalStatusEnum::DRAFT !== $status ? $this->faker->dateTimeBetween('-30 days', 'now') : null)
                ->setAcceptedAt(QuoteProposalStatusEnum::ACCEPTED === $status ? $this->randomDateTimeImmutable('-15 days', '-1 day') : null)
                ->setCurrency('EUR')
                ->setSubtotalHt($subtotal)
                ->setTaxAmount($tax)
                ->setTotalTtc($total)
                ->setPrestataireCompanyName($prestataire?->getCompanyName())
                ->setPrestataireLegalName($prestataire?->getLegalName())
                ->setPrestataireStructureType($prestataire?->getStructureType())
                ->setPrestataireSiret($prestataire?->getSiret())
                ->setPrestataireVatNumber($prestataire?->getVatNumber())
                ->setPrestataireAddress($prestataire?->getAddress())
                ->setPrestataireAddressComplement($prestataire?->getAddressComplement())
                ->setPrestatairePostalCode($prestataire?->getPostalCode())
                ->setPrestataireCity($prestataire?->getCity())
                ->setPrestataireCountry($prestataire?->getCountry())
                ->setPrestatairePhone($prestataire?->getPhonePublic())
                ->setPrestataireEmail($prestataire?->getAccount()?->getEmail())
                ->setClientTypeLabel($client?->getType()?->value)
                ->setClientFullName(mb_trim(($client?->getAccount()?->getFirstName() ?? '').' '.($client?->getAccount()?->getLastName() ?? '')))
                ->setClientCompanyName($client?->getCompanyName())
                ->setClientSiret($client?->getSiret())
                ->setClientPhone($client?->getAccount()?->getPhoneNumber())
                ->setClientEmail($client?->getAccount()?->getEmail())
                ->setClientBillingAddress($client?->getBillingAddress())
                ->setClientBillingPostalCode($client?->getBillingPostalCode())
                ->setClientBillingCity($client?->getBillingCity())
                ->setClientBillingCountry($client?->getBillingCountry())
                ->setClientInterventionAddress($client?->getDefaultAddress())
                ->setClientInterventionPostalCode($client?->getDefaultPostalCode())
                ->setClientInterventionCity($client?->getDefaultCity())
                ->setClientInterventionCountry('France')
                ->setCreatedAt($this->faker->dateTimeBetween('-3 months', '-5 days'))
                ->setUpdatedAt($this->faker->dateTimeBetween('-20 days', 'now'))
                ->setArchivedByPrestataireAt(QuoteProposalStatusEnum::ARCHIVED === $status ? $this->randomDateTimeImmutable('-10 days', '-1 day') : null);

            $manager->persist($proposal);
            $this->addReference(\sprintf('quote_proposal_%d', $i), $proposal);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ConversationFixtures::class];
    }
}
