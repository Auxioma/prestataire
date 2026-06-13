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

namespace App\DataFixtures\User;

use App\Entity\User;
use App\Enum\UserStatusEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PrestataireUserFixtures extends Fixture
{
    public const PRESTA_ALAIN_REFERENCE = 'user_prestataire_alain';
    public const PRESTA_MARIO_REFERENCE = 'user_prestataire_mario';
    public const PRESTA_CAMILLE_REFERENCE = 'user_prestataire_camille';
    public const PRESTA_JULIE_REFERENCE = 'user_prestataire_julie';
    public const PRESTA_THOMAS_REFERENCE = 'user_prestataire_thomas';
    public const PRESTA_SONIA_REFERENCE = 'user_prestataire_sonia';
    public const PRESTA_JESSICA_REFERENCE = 'user_prestataire_jessica';
    public const PRESTA_NADIA_REFERENCE = 'user_prestataire_nadia';
    public const PRESTA_PIERRE_REFERENCE = 'user_prestataire_pierre';
    public const PRESTA_KEVIN_REFERENCE = 'user_prestataire_kevin';
    public const PRESTA_CLARA_REFERENCE = 'user_prestataire_clara';
    public const PRESTA_HUGO_REFERENCE = 'user_prestataire_hugo';
    public const PRESTA_EMMA_REFERENCE = 'user_prestataire_emma';
    public const PRESTA_YASSINE_REFERENCE = 'user_prestataire_yassine';
    public const PRESTA_LAURA_REFERENCE = 'user_prestataire_laura';
    public const PRESTA_MATTHIEU_REFERENCE = 'user_prestataire_matthieu';
    public const PRESTA_CHLOE_REFERENCE = 'user_prestataire_chloe';
    public const PRESTA_ENZO_REFERENCE = 'user_prestataire_enzo';
    public const PRESTA_INES_REFERENCE = 'user_prestataire_ines';
    public const PRESTA_SARAH_REFERENCE = 'user_prestataire_sarah';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();
        $plainPassword = '123Test!';

        $prestataires = [
            [
                'reference' => self::PRESTA_ALAIN_REFERENCE,
                'email' => 'alain.martin@bt-confort.fr',
                'first_name' => 'Alain',
                'last_name' => 'Martin',
                'phone' => '0610010001',
                'avatar' => 'https://ui-avatars.com/api/?name=Alain+Martin&background=0D6EFD&color=fff&size=150',
            ],
            [
                'reference' => self::PRESTA_MARIO_REFERENCE,
                'email' => 'mario.bros@plomberie.fr',
                'first_name' => 'Mario',
                'last_name' => 'Bros',
                'phone' => '0610010002',
                'avatar' => 'https://ui-avatars.com/api/?name=Mario+Bros&background=DC3545&color=fff&size=150',
            ],
            [
                'reference' => self::PRESTA_CAMILLE_REFERENCE,
                'email' => 'camille.verdier@jardin-design.fr',
                'first_name' => 'Camille',
                'last_name' => 'Verdier',
                'phone' => '0610010003',
                'avatar' => 'https://ui-avatars.com/api/?name=Camille+Verdier&background=198754&color=fff&size=150',
            ],
            [
                'reference' => self::PRESTA_JULIE_REFERENCE,
                'email' => 'julie.moreau@nature-jardins.fr',
                'first_name' => 'Julie',
                'last_name' => 'Moreau',
                'phone' => '0610010004',
                'avatar' => 'https://ui-avatars.com/api/?name=Julie+Moreau&background=20C997&color=fff&size=150',
            ],
            [
                'reference' => self::PRESTA_THOMAS_REFERENCE,
                'email' => 'thomas.durand@avenir-numerique.fr',
                'first_name' => 'Thomas',
                'last_name' => 'Durand',
                'phone' => '0610010005',
                'avatar' => 'https://ui-avatars.com/api/?name=Thomas+Durand&background=0DCAF0&color=fff&size=150',
            ],
            [
                'reference' => self::PRESTA_SONIA_REFERENCE,
                'email' => 'sonia.leroy@assistance-digital.fr',
                'first_name' => 'Sonia',
                'last_name' => 'Leroy',
                'phone' => '0610010006',
                'avatar' => 'https://ui-avatars.com/api/?name=Sonia+Leroy&background=6610F2&color=fff&size=150',
            ],
            [
                'reference' => self::PRESTA_JESSICA_REFERENCE,
                'email' => 'jessica.larsson@clean-services.fr',
                'first_name' => 'Jessica',
                'last_name' => 'Larsson',
                'phone' => '0610010007',
                'avatar' => 'https://ui-avatars.com/api/?name=Jessica+Larsson&background=FD7E14&color=fff&size=150',
            ],
            [
                'reference' => self::PRESTA_NADIA_REFERENCE,
                'email' => 'nadia.benali@aide-domicile.fr',
                'first_name' => 'Nadia',
                'last_name' => 'Benali',
                'phone' => '0610010008',
                'avatar' => 'https://ui-avatars.com/api/?name=Nadia+Benali&background=E83E8C&color=fff&size=150',
            ],
            [
                'reference' => self::PRESTA_PIERRE_REFERENCE,
                'email' => 'pierre.rousseau@meca-depannage.fr',
                'first_name' => 'Pierre',
                'last_name' => 'Rousseau',
                'phone' => '0610010009',
                'avatar' => 'https://ui-avatars.com/api/?name=Pierre+Rousseau&background=6C757D&color=fff&size=150',
            ],
            [
                'reference' => self::PRESTA_KEVIN_REFERENCE,
                'email' => 'kevin.faure@atelier-2roues.fr',
                'first_name' => 'Kevin',
                'last_name' => 'Faure',
                'phone' => '0610010010',
                'avatar' => 'https://ui-avatars.com/api/?name=Kevin+Faure&background=495057&color=fff&size=150',
            ],
            [
                'reference' => self::PRESTA_CLARA_REFERENCE,
                'email' => 'clara.morel@instant-fete.fr',
                'first_name' => 'Clara',
                'last_name' => 'Morel',
                'phone' => '0610010011',
                'avatar' => 'https://ui-avatars.com/api/?name=Clara+Morel&background=FD7E14&color=fff&size=150',
            ],
            [
                'reference' => self::PRESTA_HUGO_REFERENCE,
                'email' => 'hugo.chevalier@studio-evenement.fr',
                'first_name' => 'Hugo',
                'last_name' => 'Chevalier',
                'phone' => '0610010012',
                'avatar' => 'https://ui-avatars.com/api/?name=Hugo+Chevalier&background=FFC107&color=212529&size=150',
            ],
            [
                'reference' => self::PRESTA_EMMA_REFERENCE,
                'email' => 'emma.bernard@cours-plus.fr',
                'first_name' => 'Emma',
                'last_name' => 'Bernard',
                'phone' => '0610010013',
                'avatar' => 'https://ui-avatars.com/api/?name=Emma+Bernard&background=6F42C1&color=fff&size=150',
            ],
            [
                'reference' => self::PRESTA_YASSINE_REFERENCE,
                'email' => 'yassine.elamrani@code-academie.fr',
                'first_name' => 'Yassine',
                'last_name' => 'El Amrani',
                'phone' => '0610010014',
                'avatar' => 'https://ui-avatars.com/api/?name=Yassine+El+Amrani&background=0DCAF0&color=fff&size=150',
            ],
            [
                'reference' => self::PRESTA_LAURA_REFERENCE,
                'email' => 'laura.garcia@equilibre-vital.fr',
                'first_name' => 'Laura',
                'last_name' => 'Garcia',
                'phone' => '0610010015',
                'avatar' => 'https://ui-avatars.com/api/?name=Laura+Garcia&background=20C997&color=fff&size=150',
            ],
            [
                'reference' => self::PRESTA_MATTHIEU_REFERENCE,
                'email' => 'matthieu.robin@coach-forme.fr',
                'first_name' => 'Matthieu',
                'last_name' => 'Robin',
                'phone' => '0610010016',
                'avatar' => 'https://ui-avatars.com/api/?name=Matthieu+Robin&background=198754&color=fff&size=150',
            ],
            [
                'reference' => self::PRESTA_CHLOE_REFERENCE,
                'email' => 'chloe.petit@pet-services.fr',
                'first_name' => 'Chloé',
                'last_name' => 'Petit',
                'phone' => '0610010017',
                'avatar' => 'https://ui-avatars.com/api/?name=Chloe+Petit&background=FFC107&color=212529&size=150',
            ],
            [
                'reference' => self::PRESTA_ENZO_REFERENCE,
                'email' => 'enzo.morel@animaux-compagnie.fr',
                'first_name' => 'Enzo',
                'last_name' => 'Morel',
                'phone' => '0610010018',
                'avatar' => 'https://ui-avatars.com/api/?name=Enzo+Morel&background=FDC500&color=212529&size=150',
            ],
            [
                'reference' => self::PRESTA_INES_REFERENCE,
                'email' => 'ines.diallo@beaute-mobile.fr',
                'first_name' => 'Inès',
                'last_name' => 'Diallo',
                'phone' => '0610010019',
                'avatar' => 'https://ui-avatars.com/api/?name=Ines+Diallo&background=D63384&color=fff&size=150',
            ],
            [
                'reference' => self::PRESTA_SARAH_REFERENCE,
                'email' => 'sarah.lemaire@glam-service.fr',
                'first_name' => 'Sarah',
                'last_name' => 'Lemaire',
                'phone' => '0610010020',
                'avatar' => 'https://ui-avatars.com/api/?name=Sarah+Lemaire&background=C2185B&color=fff&size=150',
            ],
        ];

        foreach ($prestataires as $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setRoles(['ROLE_PRESTATAIRE']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            $user->setFirstName($data['first_name']);
            $user->setLastName($data['last_name']);
            $user->setPhoneNumber($data['phone']);
            $user->setAvatar($data['avatar']);
            $user->setIsVerified(true);
            $user->setEmailVerifiedAt($now);
            $user->setStatus(UserStatusEnum::ACTIVE);
            $user->setCreatedAt($now);
            $user->setUpdatedAt($now);

            $manager->persist($user);

            $this->addReference($data['reference'], $user);
        }

        $manager->flush();
    }
}
