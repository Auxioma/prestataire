<?php

namespace App\DataFixtures;

use App\Entity\ServiceCategory;
use App\Entity\Service;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private SluggerInterface $slugger,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // --------------------------------------------------------
        // 1. CRÉATION DES UTILISATEURS DE TEST
        // --------------------------------------------------------

        // Compte Administrateur
        $admin = new User();
        $admin->setEmail('admin@trouvemoi.fr');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        if (method_exists($admin, 'setIsActive')) { 
            $admin->setIsActive(true); 
        }
        $manager->persist($admin);

        // Compte Client de test
        $client = new User();
        $client->setEmail('client@gmail.com');
        $client->setRoles(['ROLE_CLIENT']);
        $client->setPassword($this->passwordHasher->hashPassword($client, 'client123'));
        if (method_exists($client, 'setIsActive')) { 
            $client->setIsActive(true); 
        }
        $manager->persist($client);

        // Comptes Prestataires de test
        $prestasData = [
            'mario@plomberie.fr' => 'ROLE_PRESTATAIRE',
            'jessica@clean.fr' => 'ROLE_PRESTATAIRE',
            'contact@dev-informatique.fr' => 'ROLE_PRESTATAIRE'
        ];

        foreach ($prestasData as $email => $role) {
            $prestaUser = new User();
            $prestaUser->setEmail($email);
            $prestaUser->setRoles([$role]);
            $prestaUser->setPassword($this->passwordHasher->hashPassword($prestaUser, 'presta123'));
            if (method_exists($prestaUser, 'setIsActive')) { 
                $prestaUser->setIsActive(true); 
            }
            $manager->persist($prestaUser);
        }

        // --------------------------------------------------------
        // 2. CRÉATION DU CATALOGUE ENRICHI (10 CATÉGORIES PRINCIPALES)
        // --------------------------------------------------------
        $catalogData = [
            'Bâtiment & Travaux' => [
                'icon' => 'bi-house-gear',
                'color' => '#0d6efd',
                'subs' => [
                    'Plomberie' => [
                        'Dépannage de fuite d\'eau',
                        'Installation de chauffe-eau',
                        'Rénovation complète de salle de bain',
                        'Débouchage de canalisation'
                    ],
                    'Électricité' => [
                        'Mise aux normes électriques',
                        'Installation de prises et interrupteurs',
                        'Dépannage de tableau électrique',
                        'Pose de motorisation de portail'
                    ],
                    'Peinture & Plâtrerie' => [
                        'Peinture de murs et plafonds',
                        'Pose de toile de verre ou papier peint',
                        'Rénovation de plaques de plâtre (Placo)'
                    ]
                ]
            ],
            'Jardin & Extérieur' => [
                'icon' => 'bi-tree',
                'color' => '#198754',
                'subs' => [
                    'Entretien Espaces Verts' => [
                        'Tonte de pelouse',
                        'Taille de haies et arbustes',
                        'Débroussaillage et désherbage',
                        'Élagage et abattage d\'arbres'
                    ],
                    'Aménagement Extérieur' => [
                        'Création de terrasse en bois ou composite',
                        'Pose de clôtures et grillages',
                        'Installation d\'arrosage automatique'
                    ]
                ]
            ],
            'High-Tech & Informatique' => [
                'icon' => 'bi-laptop',
                'color' => '#0dcaf0',
                'subs' => [
                    'Assistance PC / Mac' => [
                        'Dépannage informatique à domicile',
                        'Suppression de virus et malwares',
                        'Réinstallation de système d\'exploitation',
                        'Configuration de réseau et Wi-Fi'
                    ],
                    'Développement & Web' => [
                        'Création de site vitrine',
                        'Création de boutique e-commerce',
                        'Optimisation SEO et référencement Google'
                    ]
                ]
            ],
            'Services à la Personne' => [
                'icon' => 'bi-heart-pulse',
                'color' => '#e83e8c',
                'subs' => [
                    'Ménage & Entretien' => [
                        'Nettoyage régulier du domicile',
                        'Grand nettoyage de printemps',
                        'Lavage de vitres et baies vitrées'
                    ],
                    'Garde d\'enfants' => [
                        'Babysitting occasionnel en soirée',
                        'Sortie d\'école et aide aux devoirs',
                        'Garde d\'enfants le mercredi ou week-end'
                    ],
                    'Aide aux Seniors' => [
                        'Accompagnement aux courses',
                        'Aide à la préparation des repas',
                        'Assistance aux démarches administratives'
                    ]
                ]
            ],
            'Cours & Formations' => [
                'icon' => 'bi-book',
                'color' => '#6f42c1',
                'subs' => [
                    'Soutien Scolaire' => [
                        'Cours particuliers de Mathématiques',
                        'Cours particuliers de Français',
                        'Aide aux devoirs (Primaire/Collège)',
                        'Préparation au Bac et Brevet'
                    ],
                    'Langues Étrangères' => [
                        'Cours d\'Anglais professionnel',
                        'Cours d\'Espagnol de niveau intermédiaire',
                        'Cours de FLE (Français Langue Étrangère)'
                    ],
                    'Musique' => [
                        'Cours de guitare débutant',
                        'Cours de piano à domicile',
                        'Éveil musical pour enfants'
                    ]
                ]
            ],
            'Mécanique & Véhicules' => [
                'icon' => 'bi-nut',
                'color' => '#6c757d',
                'subs' => [
                    'Entretien Auto' => [
                        'Vidange et remplacement de filtres',
                        'Changement de plaquettes et disques de frein',
                        'Diagnostic de panne mécanique'
                    ],
                    'Deux-roues' => [
                        'Entretien de chaîne et vidange moto',
                        'Réparation et révision de vélo électrique'
                    ]
                ]
            ],
            'Événementiel & Fêtes' => [
                'icon' => 'bi-gift',
                'color' => '#fd7e14',
                'subs' => [
                    'Photographie' => [
                        'Shooting photo de mariage',
                        'Portrait de famille en extérieur',
                        'Photos professionnelles pour entreprise'
                    ],
                    'Animation & Musique' => [
                        'Prestation de DJ pour soirée',
                        'Magicien pour anniversaire d\'enfant',
                        'Location et installation de matériel de sonorisation'
                    ],
                    'Traiteur & Cuisine' => [
                        'Chef à domicile pour repas de groupe',
                        'Buffet froid pour événements familiaux'
                    ]
                ]
            ],
            'Santé & Bien-être' => [
                'icon' => 'bi-emoji-smile',
                'color' => '#20c997',
                'subs' => [
                    'Massages & Relaxation' => [
                        'Massage relaxant à domicile',
                        'Séance de sophrologie ou méditation',
                        'Réflexologie plantaire'
                    ],
                    'Coaching Sportif' => [
                        'Remise en forme personnalisée',
                        'Séance de Pilates ou Yoga individuel',
                        'Programme de perte de poids avec coach'
                    ]
                ]
            ],
            'Animaux & Compagnie' => [
                'icon' => 'bi-dog',
                'color' => '#ffc107',
                'subs' => [
                    'Garde & Visites' => [
                        'Pension canine familiale',
                        'Visite et nourriture de chat à domicile',
                        'Promenade de chiens quotidienne'
                    ],
                    'Éducation & Soins' => [
                        'Séance d\'éducation canine de base',
                        'Toilettage de chien à domicile'
                    ]
                ]
            ],
            'Déménagement & Transport' => [
                'icon' => 'bi-truck',
                'color' => '#17a2b8',
                'subs' => [
                    'Aide au Déménagement' => [
                        'Chargement et déchargement de camion',
                        'Emballage de cartons et protection d\'objets fragiles',
                        'Déménagement complet de logement'
                    ],
                    'Transport de marchandises' => [
                        'Transport de meubles encombrants achetés d\'occasion',
                        'Évacuation d\'encombrants et déchets verts en déchetterie'
                    ]
                ]
            ]
        ];

        $categoryPosition = 1;

        foreach ($catalogData as $parentName => $parentInfo) {
            $parentCategory = new ServiceCategory();
            $parentCategory->setName($parentName);
            $parentCategory->setSlug(strtolower($this->slugger->slug($parentName)));
            $parentCategory->setIcon($parentInfo['icon']);
            $parentCategory->setColor($parentInfo['color']);
            $parentCategory->setPosition($categoryPosition++);
            $parentCategory->setIsActive(true);
            $parentCategory->setDescription('Toutes nos prestations autour de la thématique ' . $parentName);
            $parentCategory->setSeoTitle($parentName . ' - Trouvez un prestataire de confiance');
            $parentCategory->setSeoDescription('Besoin d\'un pro pour ' . $parentName . ' ?');
            
            $manager->persist($parentCategory);

            $subCategoryPosition = 1;

            foreach ($parentInfo['subs'] as $subName => $servicesList) {
                $subCategory = new ServiceCategory();
                $subCategory->setName($subName);
                $subCategory->setSlug(strtolower($this->slugger->slug($subName)));
                $subCategory->setParent($parentCategory);
                $subCategory->setPosition($subCategoryPosition++);
                $subCategory->setIsActive(true);
                $subCategory->setDescription('Prestations spécialisées en ' . $subName);
                
                $manager->persist($subCategory);

                $servicePosition = 1;

                foreach ($servicesList as $serviceName) {
                    $service = new Service();
                    $service->setName($serviceName);
                    $service->setSlug(strtolower($this->slugger->slug($serviceName)));
                    $service->setCategory($subCategory);
                    $service->setPosition($servicePosition++);
                    $service->setIsActive(true);
                    $service->setDescription('Faites appel à un professionnel pour votre besoin : ' . $serviceName);
                    
                    $minPrice = rand(25, 75);
                    $service->setAveragePriceMin((string)$minPrice);
                    $service->setAveragePriceMax((string)($minPrice + rand(20, 60)));

                    $manager->persist($service);
                }
            }
        }

        $manager->flush();
    }
}