<?php

namespace App\DataFixtures;

use App\Entity\ServiceCategory;
use App\Entity\Service;
use App\Entity\User;
use App\Entity\ClientProfile;
use App\Entity\PrestataireProfile;
use App\Enum\ClientTypeEnum;
use App\Enum\PrestataireProfileStatusEnum;
use App\Enum\VerificationStatusEnum;
use App\Enum\SearchVisibilityEnum;
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
        $now = new \DateTimeImmutable();
        $commonPassword = '123Test!';
        
        // Tableau temporaire pour capturer les instances de services créées
        $createdServices = [];

        // --------------------------------------------------------
        // 1. CRÉATION DU CATALOGUE ENRICHI (10 Catégories pour le Carrousel)
        // --------------------------------------------------------
        $catalogData = [
            'Bâtiment & Travaux' => [
                'icon' => 'fa-house',
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
                'icon' => 'fa-tree',
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
                'icon' => 'fa-laptop',
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
                'icon' => 'fa-heart-pulse',
                'color' => '#e83e8c',
                'subs' => [
                    'Ménage & Entretien' => [
                        'Nettoyage régulier du domicile',
                        'Grand nettoyage de printemps',
                        'Lavage de vitres et baies vitrées'
                    ]
                ]
            ],
            'Mécanique & Véhicules' => [
                'icon' => 'fa-car',
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
            // --- NOUVELLES CATÉGORIES POUR GONFLER LE CARROUSEL ---
            'Événementiel & Fêtes' => [
                'icon' => 'fa-cake-candles',
                'color' => '#fd7e14',
                'subs' => [
                    'Organisation' => [
                        'Photographe de mariage',
                        'Traiteur et buffets',
                        'Animation DJ et sonorisation'
                    ]
                ]
            ],
            'Cours & Formations' => [
                'icon' => 'fa-graduation-cap',
                'color' => '#6f42c1',
                'subs' => [
                    'Soutien Scolaire' => [
                        'Cours particuliers de mathématiques',
                        'Cours d\'anglais et langues étrangères',
                        'Initiation à la programmation informatique'
                    ]
                ]
            ],
            'Santé & Bien-être' => [
                'icon' => 'fa-spa',
                'color' => '#20c997',
                'subs' => [
                    'Coaching & Soins' => [
                        'Coaching sportif personnel',
                        'Massages et relaxation à domicile',
                        'Conseils en nutrition'
                    ]
                ]
            ],
            'Animaux & Compagnie' => [
                'icon' => 'fa-paw',
                'color' => '#ffc107',
                'subs' => [
                    'Pet Sitting' => [
                        'Garde de chiens et chats',
                        'Promenade de chiens',
                        'Toilettage à domicile'
                    ]
                ]
            ],
            'Mode & Beauté' => [
                'icon' => 'fa-shirt',
                'color' => '#d63384',
                'subs' => [
                    'Esthétique' => [
                        'Coiffure à domicile',
                        'Manucure et onglerie',
                        'Maquillage pour événements'
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

                    // On sauvegarde la référence du service indexée par son nom normalisé
                    $createdServices[strtolower($serviceName)] = $service;
                }
            }
        }

        // --------------------------------------------------------
        // 2. COMPTE ADMINISTRATEUR
        // --------------------------------------------------------
        $admin = new User();
        $admin->setEmail('admin@trouvemoi.fr');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin123!'));
        if (method_exists($admin, 'setFirstname')) { $admin->setFirstname('Admin'); }
        if (method_exists($admin, 'setLastname')) { $admin->setLastname('TrouveMoi'); }
        if (method_exists($admin, 'setAvatar')) { 
            $admin->setAvatar('https://ui-avatars.com/api/?name=Admin+TrouveMoi&background=212529&color=fff&size=150'); 
        }
        if (method_exists($admin, 'setIsVerified')) { $admin->setIsVerified(true); }
        if (method_exists($admin, 'setEmailVerifiedAt')) { $admin->setEmailVerifiedAt($now); }
        $manager->persist($admin);

        // --------------------------------------------------------
        // 3. COMPTES CLIENTS
        // --------------------------------------------------------
        $clientsData = [
            [
                'email' => 'jean.dupont@gmail.com',
                'firstname' => 'Jean',
                'lastname' => 'Dupont',
                'phone' => '0612345678',
                'avatar' => 'https://ui-avatars.com/api/?name=Jean+Dupont&background=0D6EFD&color=fff&size=150',
                'type' => ClientTypeEnum::PARTICULIER
            ],
            [
                'email' => 'marie.lefevre@gmail.com',
                'firstname' => 'Marie',
                'lastname' => 'Lefevre',
                'phone' => '0623456789',
                'avatar' => 'https://ui-avatars.com/api/?name=Marie+Lefevre&background=E83E8C&color=fff&size=150',
                'type' => ClientTypeEnum::PARTICULIER
            ],
            [
                'email' => 'lucas.martin@gmail.com',
                'firstname' => 'Lucas',
                'lastname' => 'Martin',
                'phone' => '0634567890',
                'avatar' => 'https://ui-avatars.com/api/?name=Lucas+Martin&background=6F42C1&color=fff&size=150',
                'type' => ClientTypeEnum::PROFESSIONNEL
            ],
        ];

        foreach ($clientsData as $data) {
            $client = new User();
            $client->setEmail($data['email']);
            $client->setRoles(['ROLE_CLIENT']);
            $client->setPassword($this->passwordHasher->hashPassword($client, $commonPassword));
            if (method_exists($client, 'setFirstname')) { $client->setFirstname($data['firstname']); }
            if (method_exists($client, 'setLastname')) { $client->setLastname($data['lastname']); }
            if (method_exists($client, 'setPhone')) { $client->setPhone($data['phone']); }
            if (method_exists($client, 'setAvatar')) { $client->setAvatar($data['avatar']); }
            if (method_exists($client, 'setIsVerified')) { $client->setIsVerified(true); }
            if (method_exists($client, 'setEmailVerifiedAt')) { $client->setEmailVerifiedAt($now); }
            $manager->persist($client);

            $clientProfile = new ClientProfile();
            $clientProfile->setAccount($client); 
            $clientProfile->setCreatedAt($now);
            $clientProfile->setType($data['type']);
            if ($data['type'] === ClientTypeEnum::PROFESSIONNEL) {
                $clientProfile->setCompanyName($data['lastname'] . ' Entreprise');
            }
            $manager->persist($clientProfile);
        }

        // --------------------------------------------------------
        // 4. COMPTES PRESTATAIRES (Ciblés et mappés sur nos Services)
        // --------------------------------------------------------
        $prestasData = [
            [
                'email' => 'mario.plombier@plomberie.fr',
                'firstname' => 'Mario',
                'lastname' => 'Bros',
                'phone' => '0645678901',
                'avatar' => 'https://ui-avatars.com/api/?name=Mario+Bros&background=DC3545&color=fff&size=150',
                'company' => 'Plomberie Bros & Co',
                'metier' => 'Plombier Chauffagiste',
                'target_services' => ['dépannage de fuite d\'eau', 'installation de chauffe-eau']
            ],
            [
                'email' => 'jessica.clean@clean.fr',
                'firstname' => 'Jessica',
                'lastname' => 'Larsson',
                'phone' => '0656789012',
                'avatar' => 'https://ui-avatars.com/api/?name=Jessica+Larsson&background=FD7E14&color=fff&size=150',
                'company' => 'Jessica Nettoyage',
                'metier' => 'Agent d\'entretien',
                'target_services' => ['nettoyage régulier du domicile', 'grand nettoyage de printemps']
            ],
            [
                'email' => 'thomas.dev@dev-informatique.fr',
                'firstname' => 'Thomas',
                'lastname' => 'Durand',
                'phone' => '0667890123',
                'avatar' => 'https://ui-avatars.com/api/?name=Thomas+Durand&background=0DCAF0&color=fff&size=150',
                'company' => 'Avenir Numérique',
                'metier' => 'Développeur Web',
                'target_services' => ['création de site vitrine', 'création de boutique e-commerce']
            ],
            [
                'email' => 'julie.jardin@nature.fr',
                'firstname' => 'Julie',
                'lastname' => 'Moreau',
                'phone' => '0678901234',
                'avatar' => 'https://ui-avatars.com/api/?name=Julie+Moreau&background=198754&color=fff&size=150',
                'company' => 'Julie Jardins Éco',
                'metier' => 'Paysagiste',
                'target_services' => ['tonte de pelouse', 'taille de haies et arbustes']
            ],
            [
                'email' => 'pierre.mecanique@garage.fr',
                'firstname' => 'Pierre',
                'lastname' => 'Rousseau',
                'phone' => '0689012345',
                'avatar' => 'https://ui-avatars.com/api/?name=Pierre+Rousseau&background=6C757D&color=fff&size=150',
                'company' => 'Méca Dépannage 17',
                'metier' => 'Mécanicien Moto / Auto',
                'target_services' => ['vidange et remplacement de filtres', 'entretien de chaîne et vidange moto']
            ],
        ];

        foreach ($prestasData as $data) {
            $prestaUser = new User();
            $prestaUser->setEmail($data['email']);
            $prestaUser->setRoles(['ROLE_PRESTATAIRE']);
            $prestaUser->setPassword($this->passwordHasher->hashPassword($prestaUser, $commonPassword));
            if (method_exists($prestaUser, 'setFirstname')) { $prestaUser->setFirstname($data['firstname']); }
            if (method_exists($prestaUser, 'setLastname')) { $prestaUser->setLastname($data['lastname']); }
            if (method_exists($prestaUser, 'setPhone')) { $prestaUser->setPhone($data['phone']); }
            if (method_exists($prestaUser, 'setAvatar')) { $prestaUser->setAvatar($data['avatar']); }
            if (method_exists($prestaUser, 'setIsVerified')) { $prestaUser->setIsVerified(true); }
            if (method_exists($prestaUser, 'setEmailVerifiedAt')) { $prestaUser->setEmailVerifiedAt($now); }
            $manager->persist($prestaUser);

            $prestaProfile = new PrestataireProfile();
            $prestaProfile->setAccount($prestaUser); 
            $prestaProfile->setCompanyName($data['company']);
            $prestaProfile->setMetier($data['metier']);
            $prestaProfile->setCreatedAt($now);
            $prestaProfile->setSlug(strtolower($this->slugger->slug($data['company'])));
            
            $prestaProfile->setProfileStatus(PrestataireProfileStatusEnum::ACTIVE);
            $prestaProfile->setVerificationStatus(VerificationStatusEnum::MANUALLY_VERIFIED);
            $prestaProfile->setSearchVisibility(SearchVisibilityEnum::NORMAL);

            $manager->persist($prestaProfile);
        }

        $manager->flush();
    }
}