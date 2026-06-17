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

namespace App\DataFixtures\Prestataire;

use App\Entity\PrestataireAvailability;
use App\Entity\PrestataireProfile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PrestataireAvailabilityFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $profiles = [
            PrestataireProfileFixtures::PROFILE_ALAIN_REFERENCE,
            PrestataireProfileFixtures::PROFILE_MARIO_REFERENCE,
            PrestataireProfileFixtures::PROFILE_CAMILLE_REFERENCE,
            PrestataireProfileFixtures::PROFILE_JULIE_REFERENCE,
            PrestataireProfileFixtures::PROFILE_THOMAS_REFERENCE,
            PrestataireProfileFixtures::PROFILE_SONIA_REFERENCE,
            PrestataireProfileFixtures::PROFILE_JESSICA_REFERENCE,
            PrestataireProfileFixtures::PROFILE_NADIA_REFERENCE,
            PrestataireProfileFixtures::PROFILE_PIERRE_REFERENCE,
            PrestataireProfileFixtures::PROFILE_KEVIN_REFERENCE,
            PrestataireProfileFixtures::PROFILE_CLARA_REFERENCE,
            PrestataireProfileFixtures::PROFILE_HUGO_REFERENCE,
            PrestataireProfileFixtures::PROFILE_EMMA_REFERENCE,
            PrestataireProfileFixtures::PROFILE_YASSINE_REFERENCE,
            PrestataireProfileFixtures::PROFILE_LAURA_REFERENCE,
            PrestataireProfileFixtures::PROFILE_MATTHIEU_REFERENCE,
            PrestataireProfileFixtures::PROFILE_CHLOE_REFERENCE,
            PrestataireProfileFixtures::PROFILE_ENZO_REFERENCE,
            PrestataireProfileFixtures::PROFILE_INES_REFERENCE,
            PrestataireProfileFixtures::PROFILE_SARAH_REFERENCE,
        ];

        foreach ($profiles as $profileRef) {
            /** @var PrestataireProfile $profile */
            $profile = $this->getReference($profileRef, PrestataireProfile::class);

            for ($day = 1; $day <= 7; ++$day) {
                $availability = new PrestataireAvailability();
                $availability->setPrestataireProfile($profile);
                $availability->setDayOfWeek($day);

                if ($day <= 5) {
                    $availability->setMorningEnabled(true);
                    $availability->setMorningStart(new \DateTime('08:30'));
                    $availability->setMorningEnd(new \DateTime('12:30'));
                    $availability->setAfternoonEnabled(true);
                    $availability->setAfternoonStart(new \DateTime('14:00'));
                    $availability->setAfternoonEnd(new \DateTime('18:00'));
                } elseif (6 === $day) {
                    $availability->setMorningEnabled(true);
                    $availability->setMorningStart(new \DateTime('09:00'));
                    $availability->setMorningEnd(new \DateTime('12:00'));
                    $availability->setAfternoonEnabled(false);
                    $availability->setAfternoonStart(null);
                    $availability->setAfternoonEnd(null);
                } else {
                    $availability->setMorningEnabled(false);
                    $availability->setMorningStart(null);
                    $availability->setMorningEnd(null);
                    $availability->setAfternoonEnabled(false);
                    $availability->setAfternoonStart(null);
                    $availability->setAfternoonEnd(null);
                }

                $manager->persist($availability);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            PrestataireProfileFixtures::class,
        ];
    }
}