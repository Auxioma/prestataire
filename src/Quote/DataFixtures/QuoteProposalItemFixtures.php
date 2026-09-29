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
use App\Quote\Entity\QuoteProposal;
use App\Quote\Entity\QuoteProposalItem;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class QuoteProposalItemFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $labels = ['Déplacement', 'Main d’œuvre', 'Fournitures', 'Mise en service', 'Option complémentaire', 'Nettoyage de fin de chantier'];

        for ($proposalIndex = 1; $proposalIndex <= 18; ++$proposalIndex) {
            /** @var QuoteProposal $proposal */
            $proposal = $this->getReference(\sprintf('quote_proposal_%d', $proposalIndex), QuoteProposal::class);

            for ($position = 0; $position < 3; ++$position) {
                $quantity = (string) $this->faker->numberBetween(1, 4);
                $unitPrice = $this->decimal(35, 850);
                $total = number_format((float) $quantity * (float) $unitPrice, 2, '.', '');

                $item = (new QuoteProposalItem())
                    ->setQuoteProposal($proposal)
                    ->setLabel($labels[($proposalIndex + $position) % \count($labels)])
                    ->setDescription($this->faker->sentence(12))
                    ->setQuantity($quantity)
                    ->setUnitPriceHt($unitPrice)
                    ->setVatRate('20.00')
                    ->setTotalHt($total)
                    ->setPosition($position)
                    ->setCreatedAt($this->faker->dateTimeBetween('-2 months', '-1 day'))
                    ->setUpdatedAt($this->faker->dateTimeBetween('-10 days', 'now'));

                $manager->persist($item);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [QuoteProposalFixtures::class];
    }
}
