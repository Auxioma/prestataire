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

namespace App\DataFixtures\Catalog;

use App\Entity\Service;
use App\Entity\ServiceCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class ServiceFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private SluggerInterface $slugger,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        $servicesBySubCategory = [
            'sub_plomberie' => [
                ['name' => 'Dépannage de fuite d’eau', 'icon' => 'fa-faucet', 'price_min' => '90.00', 'price_max' => '180.00', 'description' => 'Intervention rapide pour localiser et réparer une fuite d’eau sur canalisation, robinetterie ou évacuation.'],
                ['name' => 'Installation de chauffe-eau', 'icon' => 'fa-temperature-high', 'price_min' => '350.00', 'price_max' => '1200.00', 'description' => 'Pose ou remplacement de chauffe-eau électrique ou thermodynamique avec mise en service.'],
                ['name' => 'Rénovation complète de salle de bain', 'icon' => 'fa-bath', 'price_min' => '2500.00', 'price_max' => '12000.00', 'description' => 'Rénovation globale de salle de bain comprenant plomberie, équipements sanitaires et finitions.'],
                ['name' => 'Débouchage de canalisation', 'icon' => 'fa-toilet', 'price_min' => '80.00', 'price_max' => '220.00', 'description' => 'Débouchage de canalisations, éviers, lavabos, WC ou évacuations extérieures.'],
            ],
            'sub_electricite' => [
                ['name' => 'Mise aux normes électriques', 'icon' => 'fa-shield-halved', 'price_min' => '300.00', 'price_max' => '2500.00', 'description' => 'Vérification et remise en conformité d’une installation électrique existante.'],
                ['name' => 'Installation de prises et interrupteurs', 'icon' => 'fa-plug-circle-plus', 'price_min' => '70.00', 'price_max' => '350.00', 'description' => 'Pose, remplacement ou déplacement de prises, interrupteurs et points électriques.'],
                ['name' => 'Dépannage de tableau électrique', 'icon' => 'fa-bolt', 'price_min' => '120.00', 'price_max' => '450.00', 'description' => 'Diagnostic et réparation de tableau électrique, disjoncteurs et protections.'],
                ['name' => 'Pose de motorisation de portail', 'icon' => 'fa-warehouse', 'price_min' => '500.00', 'price_max' => '1800.00', 'description' => 'Installation d’un système de motorisation pour portail battant ou coulissant.'],
            ],
            'sub_peinture_platrerie' => [
                ['name' => 'Peinture de murs et plafonds', 'icon' => 'fa-paint-roller', 'price_min' => '180.00', 'price_max' => '2500.00', 'description' => 'Travaux de peinture intérieure pour rafraîchissement, rénovation ou finition complète.'],
                ['name' => 'Pose de toile de verre ou papier peint', 'icon' => 'fa-layer-group', 'price_min' => '150.00', 'price_max' => '1600.00', 'description' => 'Pose de revêtements muraux décoratifs ou techniques pour rénover vos surfaces.'],
                ['name' => 'Rénovation de plaques de plâtre', 'icon' => 'fa-hammer', 'price_min' => '250.00', 'price_max' => '3500.00', 'description' => 'Réparation, remplacement ou création d’ouvrages en plaques de plâtre et cloisons.'],
            ],
            'sub_entretien_espaces_verts' => [
                ['name' => 'Tonte de pelouse', 'icon' => 'fa-leaf', 'price_min' => '35.00', 'price_max' => '120.00', 'description' => 'Tonte régulière ou ponctuelle de pelouse avec finition soignée.'],
                ['name' => 'Taille de haies et arbustes', 'icon' => 'fa-tree', 'price_min' => '60.00', 'price_max' => '280.00', 'description' => 'Taille d’entretien, mise en forme et remise à niveau de haies et arbustes.'],
                ['name' => 'Débroussaillage et désherbage', 'icon' => 'fa-seedling', 'price_min' => '70.00', 'price_max' => '350.00', 'description' => 'Nettoyage de terrain, désherbage et débroussaillage pour entretien extérieur.'],
                ['name' => 'Élagage et abattage d’arbres', 'icon' => 'fa-tree-city', 'price_min' => '180.00', 'price_max' => '1800.00', 'description' => 'Interventions d’élagage, taille en hauteur ou abattage d’arbres en sécurité.'],
            ],
            'sub_amenagement_exterieur' => [
                ['name' => 'Création de terrasse en bois ou composite', 'icon' => 'fa-border-all', 'price_min' => '1500.00', 'price_max' => '9500.00', 'description' => 'Conception et pose de terrasse extérieure en bois naturel ou composite.'],
                ['name' => 'Pose de clôtures et grillages', 'icon' => 'fa-road-barrier', 'price_min' => '300.00', 'price_max' => '4500.00', 'description' => 'Installation de clôtures, grillages et solutions de délimitation extérieure.'],
                ['name' => 'Installation d’arrosage automatique', 'icon' => 'fa-droplet', 'price_min' => '250.00', 'price_max' => '2500.00', 'description' => 'Installation de systèmes d’arrosage automatique pour jardins et massifs.'],
            ],
            'sub_assistance_pc_mac' => [
                ['name' => 'Dépannage informatique à domicile', 'icon' => 'fa-laptop-medical', 'price_min' => '50.00', 'price_max' => '150.00', 'description' => 'Diagnostic et réparation de problèmes informatiques sur ordinateur fixe ou portable.'],
                ['name' => 'Suppression de virus et malwares', 'icon' => 'fa-shield-virus', 'price_min' => '60.00', 'price_max' => '180.00', 'description' => 'Nettoyage complet de l’ordinateur et sécurisation après infection ou logiciel malveillant.'],
                ['name' => 'Réinstallation de système d’exploitation', 'icon' => 'fa-rotate', 'price_min' => '80.00', 'price_max' => '220.00', 'description' => 'Réinstallation propre du système, configuration initiale et remise en état de l’appareil.'],
                ['name' => 'Configuration de réseau et Wi-Fi', 'icon' => 'fa-wifi', 'price_min' => '70.00', 'price_max' => '250.00', 'description' => 'Installation et optimisation du réseau domestique, Wi-Fi, imprimantes et périphériques.'],
            ],
            'sub_developpement_web' => [
                ['name' => 'Création de site vitrine', 'icon' => 'fa-globe', 'price_min' => '900.00', 'price_max' => '3500.00', 'description' => 'Conception d’un site vitrine professionnel pour présenter une activité ou une entreprise.'],
                ['name' => 'Création de boutique e-commerce', 'icon' => 'fa-cart-shopping', 'price_min' => '1800.00', 'price_max' => '8500.00', 'description' => 'Développement d’une boutique en ligne avec catalogue, paiement et gestion des commandes.'],
                ['name' => 'Optimisation SEO et référencement Google', 'icon' => 'fa-magnifying-glass-chart', 'price_min' => '250.00', 'price_max' => '2500.00', 'description' => 'Amélioration de la visibilité d’un site sur les moteurs de recherche et audit SEO.'],
            ],
            'sub_menage_entretien' => [
                ['name' => 'Nettoyage régulier du domicile', 'icon' => 'fa-broom', 'price_min' => '25.00', 'price_max' => '45.00', 'description' => 'Prestation de ménage régulier pour entretenir proprement les pièces de vie.'],
                ['name' => 'Grand nettoyage de printemps', 'icon' => 'fa-soap', 'price_min' => '90.00', 'price_max' => '320.00', 'description' => 'Nettoyage en profondeur du logement, zones difficiles et remise à neuf des espaces.'],
                ['name' => 'Lavage de vitres et baies vitrées', 'icon' => 'fa-spray-can-sparkles', 'price_min' => '40.00', 'price_max' => '160.00', 'description' => 'Nettoyage intérieur et extérieur des vitres, verrières et baies vitrées.'],
            ],
            'sub_entretien_auto' => [
                ['name' => 'Vidange et remplacement de filtres', 'icon' => 'fa-oil-can', 'price_min' => '80.00', 'price_max' => '220.00', 'description' => 'Entretien courant automobile avec vidange moteur et remplacement des filtres.'],
                ['name' => 'Changement de plaquettes et disques de frein', 'icon' => 'fa-car-burst', 'price_min' => '120.00', 'price_max' => '480.00', 'description' => 'Remplacement des organes de freinage pour assurer sécurité et performance.'],
                ['name' => 'Diagnostic de panne mécanique', 'icon' => 'fa-screwdriver-wrench', 'price_min' => '60.00', 'price_max' => '180.00', 'description' => 'Analyse des symptômes et recherche de panne sur véhicule léger.'],
            ],
            'sub_deux_roues' => [
                ['name' => 'Entretien de chaîne et vidange moto', 'icon' => 'fa-motorcycle', 'price_min' => '70.00', 'price_max' => '220.00', 'description' => 'Entretien courant moto avec contrôle, lubrification, tension et vidange.'],
                ['name' => 'Réparation et révision de vélo électrique', 'icon' => 'fa-bicycle', 'price_min' => '45.00', 'price_max' => '180.00', 'description' => 'Révision, réglage et réparation de vélos électriques ou classiques.'],
            ],
            'sub_organisation_evenement' => [
                ['name' => 'Photographe de mariage', 'icon' => 'fa-camera', 'price_min' => '600.00', 'price_max' => '2500.00', 'description' => 'Prestation photo pour mariage, cérémonie, couple et reportage événementiel.'],
                ['name' => 'Traiteur et buffets', 'icon' => 'fa-utensils', 'price_min' => '250.00', 'price_max' => '5000.00', 'description' => 'Service traiteur pour événements privés ou professionnels avec buffet ou repas servi.'],
                ['name' => 'Animation DJ et sonorisation', 'icon' => 'fa-music', 'price_min' => '350.00', 'price_max' => '1800.00', 'description' => 'Animation musicale et installation son/lumière pour mariage, soirée ou fête.'],
            ],
            'sub_soutien_scolaire' => [
                ['name' => 'Cours particuliers de mathématiques', 'icon' => 'fa-square-root-variable', 'price_min' => '20.00', 'price_max' => '50.00', 'description' => 'Accompagnement scolaire personnalisé en mathématiques, collège, lycée ou supérieur.'],
                ['name' => 'Cours d’anglais et langues étrangères', 'icon' => 'fa-language', 'price_min' => '20.00', 'price_max' => '55.00', 'description' => 'Cours de langues pour enfants, étudiants, adultes ou besoins professionnels.'],
                ['name' => 'Initiation à la programmation informatique', 'icon' => 'fa-code', 'price_min' => '25.00', 'price_max' => '70.00', 'description' => 'Cours d’initiation au développement web, aux bases du code et à la logique informatique.'],
            ],
            'sub_coaching_soins' => [
                ['name' => 'Coaching sportif personnel', 'icon' => 'fa-dumbbell', 'price_min' => '35.00', 'price_max' => '90.00', 'description' => 'Séances personnalisées de coaching sportif à domicile, en extérieur ou à distance.'],
                ['name' => 'Massages et relaxation à domicile', 'icon' => 'fa-spa', 'price_min' => '50.00', 'price_max' => '120.00', 'description' => 'Prestations de bien-être et relaxation à domicile pour réduire stress et tensions.'],
                ['name' => 'Conseils en nutrition', 'icon' => 'fa-apple-whole', 'price_min' => '40.00', 'price_max' => '100.00', 'description' => 'Accompagnement alimentaire et conseils personnalisés pour mieux manger au quotidien.'],
            ],
            'sub_pet_sitting' => [
                ['name' => 'Garde de chiens et chats', 'icon' => 'fa-paw', 'price_min' => '15.00', 'price_max' => '35.00', 'description' => 'Garde ponctuelle ou régulière de chiens et chats à domicile ou chez le pet-sitter.'],
                ['name' => 'Promenade de chiens', 'icon' => 'fa-dog', 'price_min' => '12.00', 'price_max' => '25.00', 'description' => 'Promenades adaptées au rythme et aux besoins de votre chien.'],
                ['name' => 'Toilettage à domicile', 'icon' => 'fa-shower', 'price_min' => '30.00', 'price_max' => '90.00', 'description' => 'Toilettage de confort pour animaux de compagnie directement à domicile.'],
            ],
            'sub_esthetique_coiffure' => [
                ['name' => 'Coiffure à domicile', 'icon' => 'fa-scissors', 'price_min' => '25.00', 'price_max' => '90.00', 'description' => 'Coupe, brushing, coiffage ou prestation coiffure réalisée à domicile.'],
                ['name' => 'Manucure et onglerie', 'icon' => 'fa-hand-sparkles', 'price_min' => '20.00', 'price_max' => '60.00', 'description' => 'Pose, entretien et embellissement des ongles pour le quotidien ou un événement.'],
                ['name' => 'Maquillage pour événements', 'icon' => 'fa-face-smile', 'price_min' => '35.00', 'price_max' => '120.00', 'description' => 'Maquillage professionnel pour mariage, soirée, shooting ou événement spécial.'],
            ],
        ];

        foreach ($servicesBySubCategory as $subCategoryReference => $services) {
            /** @var ServiceCategory $subCategory */
            $subCategory = $this->getReference($subCategoryReference, ServiceCategory::class);
            $position = 1;

            foreach ($services as $serviceData) {
                $service = new Service();
                $service->setName($serviceData['name']);
                $service->setSlug(mb_strtolower($this->slugger->slug($serviceData['name'])->toString()));
                $service->setDescription($serviceData['description']);
                $service->setIcon($serviceData['icon']);
                $service->setAveragePriceMin($serviceData['price_min']);
                $service->setAveragePriceMax($serviceData['price_max']);
                $service->setPosition($position);
                $service->setIsActive(true);
                $service->setCreatedAt($now);
                $service->setCategory($subCategory);

                $manager->persist($service);

                $referenceKey = 'service_' . mb_strtolower($this->slugger->slug($serviceData['name'])->toString());
                $this->addReference($referenceKey, $service);

                ++$position;
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ServiceCategoryFixtures::class,
        ];
    }
}