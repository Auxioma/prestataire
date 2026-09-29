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

use App\Catalog\Entity\Service;
use App\Catalog\Entity\ServiceCategory;
use App\Core\DataFixtures\BaseFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ServiceFixtures extends BaseFixture implements DependentFixtureInterface
{
    /**
     * @var array<int, list<string>>
     */
    private const SERVICES_BY_CHILD = [
        ['Dépannage de fuite d’eau', 'Installation de chauffe-eau', 'Rénovation de salle de bain'],
        ['Mise aux normes électriques', 'Installation de prises', 'Dépannage de tableau électrique'],
        ['Tonte de pelouse', 'Taille de haies', 'Élagage sécurisé'],
        ['Pose de terrasse', 'Installation de clôture', 'Arrosage automatique'],
        ['Dépannage informatique à domicile', 'Suppression de virus', 'Configuration Wi-Fi'],
        ['Création de site vitrine', 'Développement e-commerce', 'Audit SEO local'],
        ['Ménage régulier', 'Nettoyage de vitres', 'Grand nettoyage'],
        ['Cours de mathématiques', 'Cours d’anglais', 'Initiation au code'],
        ['Coiffure à domicile', 'Maquillage événementiel', 'Manucure et onglerie'],
        ['Coaching sportif', 'Massage relaxant', 'Conseil en nutrition'],
    ];

    public function load(ObjectManager $manager): void
    {
        $position = 1;
        foreach (self::SERVICES_BY_CHILD as $childIndex => $serviceNames) {
            /** @var ServiceCategory $category */
            $category = $this->getReference(\sprintf('service_category_child_%d', $childIndex + 1), ServiceCategory::class);

            foreach ($serviceNames as $name) {
                $priceMin = $this->decimal(20, 250);
                $priceMax = number_format((float) $priceMin + $this->faker->numberBetween(30, 1200), 2, '.', '');

                $service = (new Service())
                    ->setCategory($category)
                    ->setName($name)
                    ->setSlug($this->slugify($name))
                    ->setDescription(\sprintf('%s réalisée par un professionnel qualifié, avec devis clair et accompagnement personnalisé.', $name))
                    ->setIcon($category->getIcon())
                    ->setAveragePriceMin($priceMin)
                    ->setAveragePriceMax($priceMax)
                    ->setPosition($position++)
                    ->setIsActive(true)
                    ->setCreatedAt($this->randomDateTimeImmutable('-18 months', '-4 months'));

                $manager->persist($service);
                $this->addReference(\sprintf('service_%d', $position - 1), $service);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ServiceCategoryFixtures::class];
    }
}
