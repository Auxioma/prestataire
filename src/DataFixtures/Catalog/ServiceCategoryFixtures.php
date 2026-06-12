<?php

namespace App\DataFixtures\Catalog;

use App\Entity\ServiceCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class ServiceCategoryFixtures extends Fixture
{
    public function __construct(
        private SluggerInterface $slugger,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        $catalogData = [
            [
                'reference' => 'cat_batiment_travaux',
                'name' => 'Bâtiment & Travaux',
                'icon' => 'fa-house',
                'color' => '#0d6efd',
                'image' => 'categories/batiment-travaux.jpg',
                'position' => 1,
                'description' => 'Tous les services liés aux travaux, à la rénovation, au dépannage et à l’entretien du bâtiment.',
                'seo_title' => 'Bâtiment et travaux : artisans et prestations | TrouveMoi',
                'seo_description' => 'Trouvez un artisan ou un professionnel du bâtiment pour vos travaux, dépannages, rénovations et aménagements.',
                'children' => [
                    [
                        'reference' => 'sub_plomberie',
                        'name' => 'Plomberie',
                        'icon' => 'fa-faucet-drip',
                        'color' => '#0d6efd',
                        'image' => 'categories/plomberie.jpg',
                        'position' => 1,
                        'description' => 'Prestations de plomberie pour installation, dépannage, rénovation et entretien.',
                        'seo_title' => 'Plomberie : dépannages et installations | TrouveMoi',
                        'seo_description' => 'Trouvez un plombier pour fuite, chauffe-eau, débouchage, rénovation de salle de bain et installation sanitaire.',
                    ],
                    [
                        'reference' => 'sub_electricite',
                        'name' => 'Électricité',
                        'icon' => 'fa-bolt',
                        'color' => '#f59e0b',
                        'image' => 'categories/electricite.jpg',
                        'position' => 2,
                        'description' => 'Interventions électriques pour dépannage, mise aux normes, installation et maintenance.',
                        'seo_title' => 'Électricité : dépannage et installation | TrouveMoi',
                        'seo_description' => 'Faites appel à un électricien pour vos pannes, tableaux électriques, prises, éclairages et mises aux normes.',
                    ],
                    [
                        'reference' => 'sub_peinture_platrerie',
                        'name' => 'Peinture & Plâtrerie',
                        'icon' => 'fa-paint-roller',
                        'color' => '#e11d48',
                        'image' => 'categories/peinture-platrerie.jpg',
                        'position' => 3,
                        'description' => 'Travaux de finition intérieure, peinture, revêtements muraux et plâtrerie.',
                        'seo_title' => 'Peinture et plâtrerie : finitions intérieures | TrouveMoi',
                        'seo_description' => 'Trouvez un professionnel pour peinture, placo, enduits, toile de verre et rénovation de murs ou plafonds.',
                    ],
                ],
            ],
            [
                'reference' => 'cat_jardin_exterieur',
                'name' => 'Jardin & Extérieur',
                'icon' => 'fa-tree',
                'color' => '#198754',
                'image' => 'categories/jardin-exterieur.jpg',
                'position' => 2,
                'description' => 'Services d’entretien, aménagement et amélioration de vos espaces extérieurs.',
                'seo_title' => 'Jardin et extérieur : entretien et aménagement | TrouveMoi',
                'seo_description' => 'Trouvez un professionnel pour entretenir, aménager ou transformer votre jardin, terrasse ou extérieur.',
                'children' => [
                    [
                        'reference' => 'sub_entretien_espaces_verts',
                        'name' => 'Entretien Espaces Verts',
                        'icon' => 'fa-leaf',
                        'color' => '#198754',
                        'image' => 'categories/entretien-espaces-verts.jpg',
                        'position' => 1,
                        'description' => 'Entretien régulier ou ponctuel des pelouses, haies, massifs et végétaux.',
                        'seo_title' => 'Entretien espaces verts | TrouveMoi',
                        'seo_description' => 'Trouvez un jardinier pour tonte, taille, désherbage, débroussaillage et entretien extérieur.',
                    ],
                    [
                        'reference' => 'sub_amenagement_exterieur',
                        'name' => 'Aménagement Extérieur',
                        'icon' => 'fa-seedling',
                        'color' => '#10b981',
                        'image' => 'categories/amenagement-exterieur.jpg',
                        'position' => 2,
                        'description' => 'Création, amélioration et pose d’équipements pour terrasses, clôtures et extérieurs.',
                        'seo_title' => 'Aménagement extérieur : terrasse, clôture, équipements | TrouveMoi',
                        'seo_description' => 'Trouvez un pro pour créer une terrasse, poser une clôture, installer un arrosage ou aménager vos extérieurs.',
                    ],
                ],
            ],
            [
                'reference' => 'cat_high_tech_informatique',
                'name' => 'High-Tech & Informatique',
                'icon' => 'fa-laptop',
                'color' => '#0dcaf0',
                'image' => 'categories/high-tech-informatique.jpg',
                'position' => 3,
                'description' => 'Prestations numériques, assistance informatique et accompagnement digital.',
                'seo_title' => 'Informatique et high-tech : assistance et services digitaux | TrouveMoi',
                'seo_description' => 'Trouvez un spécialiste informatique ou web pour dépannage, assistance, réseau, site vitrine ou e-commerce.',
                'children' => [
                    [
                        'reference' => 'sub_assistance_pc_mac',
                        'name' => 'Assistance PC & Mac',
                        'icon' => 'fa-computer',
                        'color' => '#0dcaf0',
                        'image' => 'categories/assistance-pc-mac.jpg',
                        'position' => 1,
                        'description' => 'Aide informatique à domicile ou à distance pour ordinateurs, réseaux et sécurité.',
                        'seo_title' => 'Assistance PC et Mac | TrouveMoi',
                        'seo_description' => 'Dépannage informatique, suppression de virus, installation système, optimisation et assistance réseau.',
                    ],
                    [
                        'reference' => 'sub_developpement_web',
                        'name' => 'Développement Web',
                        'icon' => 'fa-code',
                        'color' => '#6366f1',
                        'image' => 'categories/developpement-web.jpg',
                        'position' => 2,
                        'description' => 'Création et amélioration de sites web, boutiques en ligne et visibilité digitale.',
                        'seo_title' => 'Développement web et création de site | TrouveMoi',
                        'seo_description' => 'Faites appel à un développeur web pour créer un site vitrine, e-commerce ou améliorer votre référencement.',
                    ],
                ],
            ],
            [
                'reference' => 'cat_services_personne',
                'name' => 'Services à la Personne',
                'icon' => 'fa-heart-pulse',
                'color' => '#e83e8c',
                'image' => 'categories/services-personne.jpg',
                'position' => 4,
                'description' => 'Prestations d’aide au quotidien, entretien du domicile et assistance aux particuliers.',
                'seo_title' => 'Services à la personne | TrouveMoi',
                'seo_description' => 'Trouvez une aide à domicile, un professionnel du ménage ou un intervenant pour vos besoins du quotidien.',
                'children' => [
                    [
                        'reference' => 'sub_menage_entretien',
                        'name' => 'Ménage & Entretien',
                        'icon' => 'fa-soap',
                        'color' => '#e83e8c',
                        'image' => 'categories/menage-entretien.jpg',
                        'position' => 1,
                        'description' => 'Ménage courant, nettoyage en profondeur et entretien du logement.',
                        'seo_title' => 'Ménage et entretien du domicile | TrouveMoi',
                        'seo_description' => 'Réservez une prestation de ménage, nettoyage de vitres ou remise en état de votre logement.',
                    ],
                ],
            ],
            [
                'reference' => 'cat_mecanique_vehicules',
                'name' => 'Mécanique & Véhicules',
                'icon' => 'fa-car',
                'color' => '#6c757d',
                'image' => 'categories/mecanique-vehicules.jpg',
                'position' => 5,
                'description' => 'Entretien, réparation et diagnostic pour voitures, motos et vélos.',
                'seo_title' => 'Mécanique et véhicules | TrouveMoi',
                'seo_description' => 'Trouvez un mécanicien ou un spécialiste pour entretenir, réparer ou diagnostiquer votre véhicule.',
                'children' => [
                    [
                        'reference' => 'sub_entretien_auto',
                        'name' => 'Entretien Auto',
                        'icon' => 'fa-car-side',
                        'color' => '#6c757d',
                        'image' => 'categories/entretien-auto.jpg',
                        'position' => 1,
                        'description' => 'Prestations automobiles pour vidange, freins, entretien courant et diagnostic.',
                        'seo_title' => 'Entretien auto et réparations | TrouveMoi',
                        'seo_description' => 'Trouvez un professionnel pour vidange, révision, diagnostic et entretien automobile.',
                    ],
                    [
                        'reference' => 'sub_deux_roues',
                        'name' => 'Deux-roues',
                        'icon' => 'fa-motorcycle',
                        'color' => '#374151',
                        'image' => 'categories/deux-roues.jpg',
                        'position' => 2,
                        'description' => 'Réparation, entretien et réglages pour motos, scooters et vélos.',
                        'seo_title' => 'Entretien deux-roues | TrouveMoi',
                        'seo_description' => 'Trouvez un pro pour entretenir ou réparer votre moto, scooter ou vélo électrique.',
                    ],
                ],
            ],
            [
                'reference' => 'cat_evenementiel_fetes',
                'name' => 'Événementiel & Fêtes',
                'icon' => 'fa-cake-candles',
                'color' => '#fd7e14',
                'image' => 'categories/evenementiel-fetes.jpg',
                'position' => 6,
                'description' => 'Prestations pour organiser, animer et immortaliser vos événements privés ou pro.',
                'seo_title' => 'Événementiel et fêtes | TrouveMoi',
                'seo_description' => 'Trouvez des prestataires pour mariage, anniversaire, réception, animation, photo et traiteur.',
                'children' => [
                    [
                        'reference' => 'sub_organisation_evenement',
                        'name' => 'Organisation',
                        'icon' => 'fa-calendar-check',
                        'color' => '#fd7e14',
                        'image' => 'categories/organisation-evenement.jpg',
                        'position' => 1,
                        'description' => 'Organisation, coordination et logistique pour événements particuliers ou professionnels.',
                        'seo_title' => 'Organisation d’événement | TrouveMoi',
                        'seo_description' => 'Trouvez un professionnel pour planifier, coordonner et réussir votre événement.',
                    ],
                ],
            ],
            [
                'reference' => 'cat_cours_formations',
                'name' => 'Cours & Formations',
                'icon' => 'fa-graduation-cap',
                'color' => '#6f42c1',
                'image' => 'categories/cours-formations.jpg',
                'position' => 7,
                'description' => 'Apprentissage, accompagnement pédagogique et montée en compétences.',
                'seo_title' => 'Cours et formations | TrouveMoi',
                'seo_description' => 'Trouvez un professeur, formateur ou coach pour soutien scolaire, langues ou informatique.',
                'children' => [
                    [
                        'reference' => 'sub_soutien_scolaire',
                        'name' => 'Soutien Scolaire',
                        'icon' => 'fa-book-open',
                        'color' => '#6f42c1',
                        'image' => 'categories/soutien-scolaire.jpg',
                        'position' => 1,
                        'description' => 'Cours particuliers, soutien et accompagnement scolaire personnalisé.',
                        'seo_title' => 'Soutien scolaire et cours particuliers | TrouveMoi',
                        'seo_description' => 'Trouvez un intervenant pour mathématiques, langues, méthodologie ou remise à niveau.',
                    ],
                ],
            ],
            [
                'reference' => 'cat_sante_bien_etre',
                'name' => 'Santé & Bien-être',
                'icon' => 'fa-spa',
                'color' => '#20c997',
                'image' => 'categories/sante-bien-etre.jpg',
                'position' => 8,
                'description' => 'Prestations de bien-être, accompagnement personnel et soins de confort.',
                'seo_title' => 'Santé et bien-être | TrouveMoi',
                'seo_description' => 'Trouvez un intervenant pour coaching, relaxation, massage ou accompagnement bien-être.',
                'children' => [
                    [
                        'reference' => 'sub_coaching_soins',
                        'name' => 'Coaching & Soins',
                        'icon' => 'fa-hand-holding-heart',
                        'color' => '#20c997',
                        'image' => 'categories/coaching-soins.jpg',
                        'position' => 1,
                        'description' => 'Coaching personnel, remise en forme, relaxation et soins de confort.',
                        'seo_title' => 'Coaching et soins bien-être | TrouveMoi',
                        'seo_description' => 'Trouvez un coach ou praticien pour sport, détente, nutrition et accompagnement bien-être.',
                    ],
                ],
            ],
            [
                'reference' => 'cat_animaux_compagnie',
                'name' => 'Animaux & Compagnie',
                'icon' => 'fa-paw',
                'color' => '#ffc107',
                'image' => 'categories/animaux-compagnie.jpg',
                'position' => 9,
                'description' => 'Services pour la garde, le soin et le bien-être de vos animaux de compagnie.',
                'seo_title' => 'Services pour animaux | TrouveMoi',
                'seo_description' => 'Trouvez un prestataire pour garde, promenade, toilettage ou accompagnement animalier.',
                'children' => [
                    [
                        'reference' => 'sub_pet_sitting',
                        'name' => 'Pet Sitting',
                        'icon' => 'fa-dog',
                        'color' => '#ffc107',
                        'image' => 'categories/pet-sitting.jpg',
                        'position' => 1,
                        'description' => 'Garde, promenade, visites et services à domicile pour chiens et chats.',
                        'seo_title' => 'Pet sitting et garde d’animaux | TrouveMoi',
                        'seo_description' => 'Trouvez un pet-sitter pour garder, promener ou prendre soin de votre animal.',
                    ],
                ],
            ],
            [
                'reference' => 'cat_mode_beaute',
                'name' => 'Mode & Beauté',
                'icon' => 'fa-shirt',
                'color' => '#d63384',
                'image' => 'categories/mode-beaute.jpg',
                'position' => 10,
                'description' => 'Prestations de beauté, esthétique et mise en valeur à domicile ou en déplacement.',
                'seo_title' => 'Mode et beauté | TrouveMoi',
                'seo_description' => 'Trouvez un professionnel pour coiffure, maquillage, manucure et prestations beauté.',
                'children' => [
                    [
                        'reference' => 'sub_esthetique_coiffure',
                        'name' => 'Esthétique & Coiffure',
                        'icon' => 'fa-scissors',
                        'color' => '#d63384',
                        'image' => 'categories/esthetique-coiffure.jpg',
                        'position' => 1,
                        'description' => 'Prestations coiffure, maquillage, onglerie et beauté pour le quotidien ou l’événementiel.',
                        'seo_title' => 'Esthétique et coiffure | TrouveMoi',
                        'seo_description' => 'Trouvez une prestation de coiffure, maquillage ou onglerie à domicile ou pour un événement.',
                    ],
                ],
            ],
        ];

        foreach ($catalogData as $parentData) {
            $parent = new ServiceCategory();
            $parent->setName($parentData['name']);
            $parent->setSlug(strtolower($this->slugger->slug($parentData['name'])->toString()));
            $parent->setDescription($parentData['description']);
            $parent->setIcon($parentData['icon']);
            $parent->setImage($parentData['image']);
            $parent->setPosition($parentData['position']);
            $parent->setColor($parentData['color']);
            $parent->setSeoTitle($parentData['seo_title']);
            $parent->setSeoDescription($parentData['seo_description']);
            $parent->setCreatedAt($now);
            $parent->setIsActive(true);

            $manager->persist($parent);
            $this->addReference($parentData['reference'], $parent);

            foreach ($parentData['children'] as $childData) {
                $child = new ServiceCategory();
                $child->setName($childData['name']);
                $child->setSlug(strtolower($this->slugger->slug($childData['name'])->toString()));
                $child->setDescription($childData['description']);
                $child->setIcon($childData['icon']);
                $child->setImage($childData['image']);
                $child->setPosition($childData['position']);
                $child->setColor($childData['color']);
                $child->setSeoTitle($childData['seo_title']);
                $child->setSeoDescription($childData['seo_description']);
                $child->setCreatedAt($now);
                $child->setIsActive(true);
                $child->setParent($parent);

                $manager->persist($child);
                $this->addReference($childData['reference'], $child);
            }
        }

        $manager->flush();
    }
}