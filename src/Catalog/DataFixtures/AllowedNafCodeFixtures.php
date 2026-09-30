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

namespace App\Catalog\DataFixtures;

use App\Catalog\Entity\AllowedNafCode;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AllowedNafCodeFixtures extends Fixture
{
    /**
     * @var array<string, string>
     */
    private const CODES = [
        '41.20A' => 'Construction de maisons individuelles',
        '41.20B' => 'Construction d’autres bâtiments',
        '43.11Z' => 'Travaux de démolition',
        '43.12A' => 'Travaux de terrassement courants',
        '43.12B' => 'Travaux de terrassement spécialisés',
        '43.13Z' => 'Forages et sondages',
        '43.21A' => 'Installation électrique dans tous locaux',
        '43.21B' => 'Installation électrique sur la voie publique',
        '43.22A' => 'Installation d’eau et de gaz',
        '43.22B' => 'Installation d’équipements thermiques et climatisation',
        '43.29A' => 'Travaux d’isolation',
        '43.29B' => 'Autres travaux d’installation',
        '43.31Z' => 'Travaux de plâtrerie',
        '43.32A' => 'Menuiserie bois et PVC',
        '43.32B' => 'Menuiserie métallique et serrurerie',
        '43.32C' => 'Agencement de lieux de vente',
        '43.33Z' => 'Revêtement des sols et murs',
        '43.34Z' => 'Peinture et vitrerie',
        '43.39Z' => 'Autres travaux de finition',
        '43.91A' => 'Travaux de charpente',
        '43.91B' => 'Travaux de couverture',
        '43.99A' => 'Travaux d’étanchéification',
        '43.99B' => 'Montage de structures métalliques',
        '43.99C' => 'Maçonnerie générale et gros œuvre',
        '43.99D' => 'Autres travaux spécialisés de construction',
        '43.99E' => 'Location avec opérateur de matériel de construction',
        '81.10Z' => 'Activités combinées de soutien lié aux bâtiments',
        '81.21Z' => 'Nettoyage courant des bâtiments',
        '81.22Z' => 'Autres activités de nettoyage des bâtiments et nettoyage industriel',
        '81.29A' => 'Désinfection, désinsectisation, dératisation',
        '81.29B' => 'Autres activités de nettoyage',
        '81.30Z' => 'Services d’aménagement paysager',
        '33.12Z' => 'Réparation de machines et équipements mécaniques',
        '33.13Z' => 'Réparation de matériels électroniques et optiques',
        '33.14Z' => 'Réparation d’équipements électriques',
        '33.19Z' => 'Réparation d’autres équipements',
        '95.11Z' => 'Réparation d’ordinateurs',
        '95.12Z' => 'Réparation d’équipements de communication',
        '95.21Z' => 'Réparation de produits électroniques grand public',
        '95.22Z' => 'Réparation d’électroménager et équipements maison/jardin',
        '95.23Z' => 'Réparation de chaussures et articles en cuir',
        '95.24Z' => 'Réparation de meubles et équipements du foyer',
        '95.25Z' => 'Réparation d’articles d’horlogerie et bijouterie',
        '95.29Z' => 'Réparation d’autres biens personnels et domestiques',
        '45.20A' => 'Entretien et réparation de véhicules automobiles légers',
        '45.20B' => 'Entretien et réparation d’autres véhicules automobiles',
        '45.40Z' => 'Commerce et réparation de motocycles',
        '62.01Z' => 'Programmation informatique',
        '62.02A' => 'Conseil en systèmes et logiciels informatiques',
        '62.02B' => 'Tierce maintenance de systèmes et applications',
        '62.03Z' => 'Gestion d’installations informatiques',
        '62.09Z' => 'Autres activités informatiques',
        '63.11Z' => 'Traitement de données et hébergement',
        '63.12Z' => 'Portails Internet',
        '74.10Z' => 'Activités spécialisées de design',
        '74.20Z' => 'Activités photographiques',
        '74.30Z' => 'Traduction et interprétation',
        '69.10Z' => 'Activités juridiques',
        '69.20Z' => 'Activités comptables',
        '70.21Z' => 'Conseil en relations publiques et communication',
        '70.22Z' => 'Conseil pour les affaires et la gestion',
        '71.11Z' => 'Activités d’architecture',
        '71.12A' => 'Activité des géomètres',
        '71.12B' => 'Ingénierie et études techniques',
        '73.11Z' => 'Activités des agences de publicité',
        '73.12Z' => 'Régie publicitaire de médias',
        '74.90A' => 'Activité des économistes de la construction',
        '74.90B' => 'Activités spécialisées, scientifiques et techniques diverses',
        '82.11Z' => 'Services administratifs combinés de bureau',
        '82.19Z' => 'Préparation de documents et activités de bureau',
        '82.20Z' => 'Activités de centres d’appels',
        '82.30Z' => 'Organisation de foires et congrès',
        '82.99Z' => 'Autres activités de soutien aux entreprises',
        '80.10Z' => 'Activités de sécurité privée',
        '80.20Z' => 'Activités liées aux systèmes de sécurité',
        '80.30Z' => 'Activités d’enquête',
        '85.51Z' => 'Enseignement de disciplines sportives',
        '85.52Z' => 'Enseignement culturel',
        '85.59A' => 'Formation continue d’adultes',
        '85.59B' => 'Autres enseignements',
        '88.10A' => 'Aide à domicile',
        '88.91A' => 'Accueil de jeunes enfants',
        '96.01A' => 'Blanchisserie-teinturerie de gros',
        '96.01B' => 'Blanchisserie-teinturerie de détail',
        '96.02A' => 'Coiffure',
        '96.02B' => 'Soins de beauté',
        '96.03Z' => 'Services funéraires',
        '96.04Z' => 'Entretien corporel',
        '96.09Z' => 'Autres services personnels',
        '49.41A' => 'Transports routiers de fret interurbains',
        '49.41B' => 'Transports routiers de fret de proximité',
        '49.42Z' => 'Services de déménagement',
        '53.20Z' => 'Autres activités de poste et de courrier',
        '56.21Z' => 'Services des traiteurs',
        '90.02Z' => 'Activités de soutien au spectacle vivant',
    ];

    public function load(ObjectManager $manager): void
    {
        /** @var array<string, AllowedNafCode> $existingByCode */
        $existingByCode = [];
        foreach ($manager->getRepository(AllowedNafCode::class)->findAll() as $existingCode) {
            if ($existingCode instanceof AllowedNafCode && null !== $existingCode->getCode()) {
                $existingByCode[$existingCode->getCode()] = $existingCode;
            }
        }

        foreach (self::CODES as $code => $label) {
            $allowedNafCode = $existingByCode[$code] ?? (new AllowedNafCode())->setCode($code);
            $allowedNafCode
                ->setLabel($label)
                ->setIsActive(true);

            $manager->persist($allowedNafCode);
        }

        $manager->flush();
    }
}
