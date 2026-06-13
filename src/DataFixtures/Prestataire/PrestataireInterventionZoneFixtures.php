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

use App\Entity\PrestataireInterventionZone;
use App\Entity\PrestataireProfile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PrestataireInterventionZoneFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $rows = [
            [PrestataireProfileFixtures::PROFILE_ALAIN_REFERENCE, 'Bordeaux', '33000', 'Gironde', 'Nouvelle-Aquitaine', '44.8377890', '-0.5791800', 25, true, true],
            [PrestataireProfileFixtures::PROFILE_ALAIN_REFERENCE, 'Mérignac', '33700', 'Gironde', 'Nouvelle-Aquitaine', '44.8422000', '-0.6457000', 20, false, true],

            [PrestataireProfileFixtures::PROFILE_MARIO_REFERENCE, 'Bordeaux', '33000', 'Gironde', 'Nouvelle-Aquitaine', '44.8377890', '-0.5791800', 30, true, true],
            [PrestataireProfileFixtures::PROFILE_MARIO_REFERENCE, 'Pessac', '33600', 'Gironde', 'Nouvelle-Aquitaine', '44.8069000', '-0.6324000', 20, false, true],

            [PrestataireProfileFixtures::PROFILE_CAMILLE_REFERENCE, 'Cestas', '33610', 'Gironde', 'Nouvelle-Aquitaine', '44.7445000', '-0.6835000', 25, true, true],
            [PrestataireProfileFixtures::PROFILE_JULIE_REFERENCE, 'Lacanau', '33680', 'Gironde', 'Nouvelle-Aquitaine', '45.0023000', '-1.2030000', 35, true, true],

            [PrestataireProfileFixtures::PROFILE_THOMAS_REFERENCE, 'Gradignan', '33170', 'Gironde', 'Nouvelle-Aquitaine', '44.7727000', '-0.6110000', 40, true, true],
            [PrestataireProfileFixtures::PROFILE_THOMAS_REFERENCE, 'Bordeaux', '33000', 'Gironde', 'Nouvelle-Aquitaine', '44.8377890', '-0.5791800', 30, false, true],

            [PrestataireProfileFixtures::PROFILE_SONIA_REFERENCE, 'Eysines', '33320', 'Gironde', 'Nouvelle-Aquitaine', '44.8858000', '-0.6513000', 25, true, true],
            [PrestataireProfileFixtures::PROFILE_JESSICA_REFERENCE, 'Mérignac', '33700', 'Gironde', 'Nouvelle-Aquitaine', '44.8422000', '-0.6457000', 20, true, true],

            [PrestataireProfileFixtures::PROFILE_NADIA_REFERENCE, 'Bègles', '33130', 'Gironde', 'Nouvelle-Aquitaine', '44.8077000', '-0.5489000', 20, true, true],
            [PrestataireProfileFixtures::PROFILE_NADIA_REFERENCE, 'Talence', '33400', 'Gironde', 'Nouvelle-Aquitaine', '44.8089000', '-0.5890000', 15, false, true],

            [PrestataireProfileFixtures::PROFILE_PIERRE_REFERENCE, 'Arcachon', '33120', 'Gironde', 'Nouvelle-Aquitaine', '44.6615000', '-1.1725000', 30, true, true],
            [PrestataireProfileFixtures::PROFILE_KEVIN_REFERENCE, 'Gujan-Mestras', '33470', 'Gironde', 'Nouvelle-Aquitaine', '44.6356000', '-1.0710000', 25, true, true],

            [PrestataireProfileFixtures::PROFILE_CLARA_REFERENCE, 'Bordeaux', '33000', 'Gironde', 'Nouvelle-Aquitaine', '44.8446000', '-0.5753000', 50, true, true],
            [PrestataireProfileFixtures::PROFILE_HUGO_REFERENCE, 'Bordeaux', '33000', 'Gironde', 'Nouvelle-Aquitaine', '44.8481000', '-0.5665000', 50, true, true],

            [PrestataireProfileFixtures::PROFILE_EMMA_REFERENCE, 'Bordeaux', '33000', 'Gironde', 'Nouvelle-Aquitaine', '44.8408000', '-0.5859000', 20, true, true],
            [PrestataireProfileFixtures::PROFILE_YASSINE_REFERENCE, 'Bordeaux', '33000', 'Gironde', 'Nouvelle-Aquitaine', '44.8370000', '-0.5687000', 25, true, true],

            [PrestataireProfileFixtures::PROFILE_LAURA_REFERENCE, 'Mérignac', '33700', 'Gironde', 'Nouvelle-Aquitaine', '44.8385000', '-0.6337000', 25, true, true],
            [PrestataireProfileFixtures::PROFILE_MATTHIEU_REFERENCE, 'Bordeaux', '33200', 'Gironde', 'Nouvelle-Aquitaine', '44.8579000', '-0.6261000', 30, true, true],

            [PrestataireProfileFixtures::PROFILE_CHLOE_REFERENCE, 'Le Bouscat', '33110', 'Gironde', 'Nouvelle-Aquitaine', '44.8639000', '-0.5962000', 20, true, true],
            [PrestataireProfileFixtures::PROFILE_ENZO_REFERENCE, 'Talence', '33400', 'Gironde', 'Nouvelle-Aquitaine', '44.8089000', '-0.5890000', 20, true, true],

            [PrestataireProfileFixtures::PROFILE_INES_REFERENCE, 'Bordeaux', '33000', 'Gironde', 'Nouvelle-Aquitaine', '44.8373000', '-0.5717000', 25, true, true],
            [PrestataireProfileFixtures::PROFILE_SARAH_REFERENCE, 'Bordeaux', '33100', 'Gironde', 'Nouvelle-Aquitaine', '44.8474000', '-0.5519000', 25, true, true],
        ];

        foreach ($rows as [$profileRef, $city, $postalCode, $department, $region, $latitude, $longitude, $radiusKm, $isMainZone, $isActive]) {
            /** @var PrestataireProfile $profile */
            $profile = $this->getReference($profileRef, PrestataireProfile::class);

            $zone = new PrestataireInterventionZone();
            $zone->setPrestataireProfile($profile);
            $zone->setCity($city);
            $zone->setPostalCode($postalCode);
            $zone->setDepartment($department);
            $zone->setRegion($region);
            $zone->setLatitude($latitude);
            $zone->setLongitude($longitude);
            $zone->setRadiusKm($radiusKm);
            $zone->setIsMainZone($isMainZone);
            $zone->setIsActive($isActive);

            $manager->persist($zone);
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
