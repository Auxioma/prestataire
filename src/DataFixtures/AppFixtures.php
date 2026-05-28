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
        // 2. CRÉATION DU CATALOGUE (CATÉGORIES & SERVICES)
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
                        'Dépannage de tableau électrique'
                    ]
                ]
            ],
            'Jardin & Extérieur' => [
                'icon' => 'bi-tree',
                'color' => '#198754',
                'subs' => [
                    'Entretien Espaces Verts' => [
                        'Tonte de pelouse',
                        'Taille de haies et arbustes'
                    ]
                ]
            ],
            'High-Tech & Informatique' => [
                'icon' => 'bi-laptop',
                'color' => '#0dcaf0',
                'subs' => [
                    'Assistance PC / Mac' => [
                        'Dépannage informatique à domicile',
                        'Suppression de virus et malwares'
                    ],
                    'Développement de sites' => [
                        'Création de site vitrine',
                        'Création de boutique e-commerce'
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
            $parentCategory->setIsActive(true); // Changé ici
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
                $subCategory->setIsActive(true); // Changé ici
                $subCategory->setDescription('Prestations spécialisées en ' . $subName);
                
                $manager->persist($subCategory);

                $servicePosition = 1;

                foreach ($servicesList as $serviceName) {
                    $service = new Service();
                    $service->setName($serviceName);
                    $service->setSlug(strtolower($this->slugger->slug($serviceName)));
                    $service->setCategory($subCategory);
                    $service->setPosition($servicePosition++);
                    $service->setIsActive(true); // Changé ici
                    $service->setDescription('Faites appel à un professionnel pour votre besoin : ' . $serviceName);
                    
                    $minPrice = rand(30, 80);
                    $service->setAveragePriceMin((string)$minPrice);
                    $service->setAveragePriceMax((string)($minPrice + rand(20, 50)));

                    $manager->persist($service);
                }
            }
        }

        $manager->flush();
    }
}