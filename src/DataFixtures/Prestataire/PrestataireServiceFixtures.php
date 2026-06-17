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

use App\DataFixtures\Catalog\ServiceFixtures;
use App\Entity\PrestataireProfile;
use App\Entity\PrestataireService;
use App\Entity\Service;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PrestataireServiceFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        $rows = [
            [PrestataireProfileFixtures::PROFILE_ALAIN_REFERENCE, 'Mise aux normes électriques', '95.00', null, null],
            [PrestataireProfileFixtures::PROFILE_ALAIN_REFERENCE, 'Installation de prises et interrupteurs', '75.00', '10.00', $now->modify('-10 days')],

            [PrestataireProfileFixtures::PROFILE_MARIO_REFERENCE, 'Dépannage de fuite d’eau', '89.00', '15.00', $now->modify('-6 days')],
            [PrestataireProfileFixtures::PROFILE_MARIO_REFERENCE, 'Installation de chauffe-eau', '590.00', null, null],
            [PrestataireProfileFixtures::PROFILE_MARIO_REFERENCE, 'Débouchage de canalisation', '120.00', '20.00', $now->modify('-2 days')],

            [PrestataireProfileFixtures::PROFILE_CAMILLE_REFERENCE, 'Tonte de pelouse', '45.00', null, null],
            [PrestataireProfileFixtures::PROFILE_CAMILLE_REFERENCE, 'Taille de haies et arbustes', '68.00', '10.00', $now->modify('-8 days')],

            [PrestataireProfileFixtures::PROFILE_JULIE_REFERENCE, 'Tonte de pelouse', '49.00', null, null],
            [PrestataireProfileFixtures::PROFILE_JULIE_REFERENCE, 'Taille de haies et arbustes', '72.00', null, null],
            [PrestataireProfileFixtures::PROFILE_JULIE_REFERENCE, 'Création de terrasse en bois ou composite', '1450.00', '12.00', $now->modify('-12 days')],

            [PrestataireProfileFixtures::PROFILE_THOMAS_REFERENCE, 'Création de site vitrine', '990.00', '18.00', $now->modify('-4 days')],
            [PrestataireProfileFixtures::PROFILE_THOMAS_REFERENCE, 'Création de boutique e-commerce', '2490.00', null, null],
            [PrestataireProfileFixtures::PROFILE_THOMAS_REFERENCE, 'Optimisation SEO et référencement Google', '420.00', '10.00', $now->modify('-15 days')],

            [PrestataireProfileFixtures::PROFILE_SONIA_REFERENCE, 'Dépannage informatique à domicile', '65.00', null, null],
            [PrestataireProfileFixtures::PROFILE_SONIA_REFERENCE, 'Configuration de réseau et Wi-Fi', '85.00', '12.00', $now->modify('-9 days')],

            [PrestataireProfileFixtures::PROFILE_JESSICA_REFERENCE, 'Nettoyage régulier du domicile', '32.00', null, null],
            [PrestataireProfileFixtures::PROFILE_JESSICA_REFERENCE, 'Grand nettoyage de printemps', '160.00', '15.00', $now->modify('-7 days')],
            [PrestataireProfileFixtures::PROFILE_JESSICA_REFERENCE, 'Lavage de vitres et baies vitrées', '70.00', null, null],

            [PrestataireProfileFixtures::PROFILE_NADIA_REFERENCE, 'Nettoyage régulier du domicile', '34.00', null, null],
            [PrestataireProfileFixtures::PROFILE_NADIA_REFERENCE, 'Grand nettoyage de printemps', '155.00', '10.00', $now->modify('-20 days')],

            [PrestataireProfileFixtures::PROFILE_PIERRE_REFERENCE, 'Vidange et remplacement de filtres', '95.00', null, null],
            [PrestataireProfileFixtures::PROFILE_PIERRE_REFERENCE, 'Changement de plaquettes et disques de frein', '210.00', '8.00', $now->modify('-11 days')],
            [PrestataireProfileFixtures::PROFILE_PIERRE_REFERENCE, 'Diagnostic de panne mécanique', '75.00', null, null],

            [PrestataireProfileFixtures::PROFILE_KEVIN_REFERENCE, 'Entretien de chaîne et vidange moto', '79.00', null, null],
            [PrestataireProfileFixtures::PROFILE_KEVIN_REFERENCE, 'Réparation et révision de vélo électrique', '110.00', '10.00', $now->modify('-5 days')],

            [PrestataireProfileFixtures::PROFILE_CLARA_REFERENCE, 'Photographe de mariage', '890.00', null, null],
            [PrestataireProfileFixtures::PROFILE_CLARA_REFERENCE, 'Traiteur et buffets', '1450.00', '12.00', $now->modify('-13 days')],

            [PrestataireProfileFixtures::PROFILE_HUGO_REFERENCE, 'Photographe de mariage', '990.00', '10.00', $now->modify('-3 days')],
            [PrestataireProfileFixtures::PROFILE_HUGO_REFERENCE, 'Animation DJ et sonorisation', '690.00', null, null],

            [PrestataireProfileFixtures::PROFILE_EMMA_REFERENCE, 'Cours particuliers de mathématiques', '38.00', null, null],
            [PrestataireProfileFixtures::PROFILE_EMMA_REFERENCE, 'Cours d’anglais et langues étrangères', '40.00', '10.00', $now->modify('-14 days')],

            [PrestataireProfileFixtures::PROFILE_YASSINE_REFERENCE, 'Initiation à la programmation informatique', '55.00', '15.00', $now->modify('-6 days')],
            [PrestataireProfileFixtures::PROFILE_YASSINE_REFERENCE, 'Création de site vitrine', '1200.00', null, null],

            [PrestataireProfileFixtures::PROFILE_LAURA_REFERENCE, 'Massages et relaxation à domicile', '75.00', '10.00', $now->modify('-4 days')],
            [PrestataireProfileFixtures::PROFILE_LAURA_REFERENCE, 'Conseils en nutrition', '60.00', null, null],

            [PrestataireProfileFixtures::PROFILE_MATTHIEU_REFERENCE, 'Coaching sportif personnel', '55.00', null, null],
            [PrestataireProfileFixtures::PROFILE_MATTHIEU_REFERENCE, 'Conseils en nutrition', '65.00', '10.00', $now->modify('-16 days')],

            [PrestataireProfileFixtures::PROFILE_CHLOE_REFERENCE, 'Garde de chiens et chats', '28.00', null, null],
            [PrestataireProfileFixtures::PROFILE_CHLOE_REFERENCE, 'Promenade de chiens', '18.00', '10.00', $now->modify('-9 days')],

            [PrestataireProfileFixtures::PROFILE_ENZO_REFERENCE, 'Toilettage à domicile', '52.00', '12.00', $now->modify('-5 days')],
            [PrestataireProfileFixtures::PROFILE_ENZO_REFERENCE, 'Garde de chiens et chats', '30.00', null, null],

            [PrestataireProfileFixtures::PROFILE_INES_REFERENCE, 'Maquillage pour événements', '85.00', '15.00', $now->modify('-7 days')],
            [PrestataireProfileFixtures::PROFILE_INES_REFERENCE, 'Manucure et onglerie', '42.00', null, null],

            [PrestataireProfileFixtures::PROFILE_SARAH_REFERENCE, 'Coiffure à domicile', '65.00', null, null],
            [PrestataireProfileFixtures::PROFILE_SARAH_REFERENCE, 'Maquillage pour événements', '90.00', '10.00', $now->modify('-18 days')],
        ];

        foreach ($rows as [$profileRef, $serviceName, $prixCatalogue, $tauxReduction, $promotionCreatedAt]) {
            /** @var PrestataireProfile $profile */
            $profile = $this->getReference($profileRef, PrestataireProfile::class);

            /** @var Service|null $service */
            $service = $manager->getRepository(Service::class)->findOneBy(['name' => $serviceName]);

            if (!$service) {
                continue;
            }

            $ps = new PrestataireService();
            $ps->setPrestataire($profile);
            $ps->setService($service);
            $ps->setPrixCatalogue($prixCatalogue);
            $ps->setTauxReduction($tauxReduction);
            $ps->setPromotionCreatedAt($promotionCreatedAt);

            $manager->persist($ps);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            PrestataireProfileFixtures::class,
            ServiceFixtures::class,
        ];
    }
}