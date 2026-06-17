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
            ['reference' => self::PRESTA_ALAIN_REFERENCE, 'email' => 'alain.martin@bt-confort.fr', 'firstname' => 'Alain', 'lastname' => 'Martin', 'phone' => '0610010001', 'avatar' => 'https://ui-avatars.com/api/?name=Alain+Martin&background=0D6EFD&color=fff&size=150'],
            ['reference' => self::PRESTA_MARIO_REFERENCE, 'email' => 'mario.bros@plomberie.fr', 'firstname' => 'Mario', 'lastname' => 'Bros', 'phone' => '0610010002', 'avatar' => 'https://ui-avatars.com/api/?name=Mario+Bros&background=DC3545&color=fff&size=150'],
            ['reference' => self::PRESTA_CAMILLE_REFERENCE, 'email' => 'camille.verdier@jardin-design.fr', 'firstname' => 'Camille', 'lastname' => 'Verdier', 'phone' => '0610010003', 'avatar' => 'https://ui-avatars.com/api/?name=Camille+Verdier&background=198754&color=fff&size=150'],
            ['reference' => self::PRESTA_JULIE_REFERENCE, 'email' => 'julie.moreau@nature-jardins.fr', 'firstname' => 'Julie', 'lastname' => 'Moreau', 'phone' => '0610010004', 'avatar' => 'https://ui-avatars.com/api/?name=Julie+Moreau&background=20C997&color=fff&size=150'],
            ['reference' => self::PRESTA_THOMAS_REFERENCE, 'email' => 'thomas.durand@avenir-numerique.fr', 'firstname' => 'Thomas', 'lastname' => 'Durand', 'phone' => '0610010005', 'avatar' => 'https://ui-avatars.com/api/?name=Thomas+Durand&background=0DCAF0&color=fff&size=150'],
            ['reference' => self::PRESTA_SONIA_REFERENCE, 'email' => 'sonia.leroy@assistance-digital.fr', 'firstname' => 'Sonia', 'lastname' => 'Leroy', 'phone' => '0610010006', 'avatar' => 'https://ui-avatars.com/api/?name=Sonia+Leroy&background=6610F2&color=fff&size=150'],
            ['reference' => self::PRESTA_JESSICA_REFERENCE, 'email' => 'jessica.larsson@clean-services.fr', 'firstname' => 'Jessica', 'lastname' => 'Larsson', 'phone' => '0610010007', 'avatar' => 'https://ui-avatars.com/api/?name=Jessica+Larsson&background=FD7E14&color=fff&size=150'],
            ['reference' => self::PRESTA_NADIA_REFERENCE, 'email' => 'nadia.benali@aide-domicile.fr', 'firstname' => 'Nadia', 'lastname' => 'Benali', 'phone' => '0610010008', 'avatar' => 'https://ui-avatars.com/api/?name=Nadia+Benali&background=E83E8C&color=fff&size=150'],
            ['reference' => self::PRESTA_PIERRE_REFERENCE, 'email' => 'pierre.rousseau@meca-depannage.fr', 'firstname' => 'Pierre', 'lastname' => 'Rousseau', 'phone' => '0610010009', 'avatar' => 'https://ui-avatars.com/api/?name=Pierre+Rousseau&background=6C757D&color=fff&size=150'],
            ['reference' => self::PRESTA_KEVIN_REFERENCE, 'email' => 'kevin.faure@atelier-2roues.fr', 'firstname' => 'Kevin', 'lastname' => 'Faure', 'phone' => '0610010010', 'avatar' => 'https://ui-avatars.com/api/?name=Kevin+Faure&background=495057&color=fff&size=150'],
            ['reference' => self::PRESTA_CLARA_REFERENCE, 'email' => 'clara.morel@instant-fete.fr', 'firstname' => 'Clara', 'lastname' => 'Morel', 'phone' => '0610010011', 'avatar' => 'https://ui-avatars.com/api/?name=Clara+Morel&background=FD7E14&color=212529&size=150'],
            ['reference' => self::PRESTA_HUGO_REFERENCE, 'email' => 'hugo.chevalier@studio-evenement.fr', 'firstname' => 'Hugo', 'lastname' => 'Chevalier', 'phone' => '0610010012', 'avatar' => 'https://ui-avatars.com/api/?name=Hugo+Chevalier&background=FFC107&color=212529&size=150'],
            ['reference' => self::PRESTA_EMMA_REFERENCE, 'email' => 'emma.bernard@cours-plus.fr', 'firstname' => 'Emma', 'lastname' => 'Bernard', 'phone' => '0610010013', 'avatar' => 'https://ui-avatars.com/api/?name=Emma+Bernard&background=6F42C1&color=fff&size=150'],
            ['reference' => self::PRESTA_YASSINE_REFERENCE, 'email' => 'yassine.elamrani@code-academie.fr', 'firstname' => 'Yassine', 'lastname' => 'El Amrani', 'phone' => '0610010014', 'avatar' => 'https://ui-avatars.com/api/?name=Yassine+El+Amrani&background=0DCAF0&color=fff&size=150'],
            ['reference' => self::PRESTA_LAURA_REFERENCE, 'email' => 'laura.garcia@equilibre-vital.fr', 'firstname' => 'Laura', 'lastname' => 'Garcia', 'phone' => '0610010015', 'avatar' => 'https://ui-avatars.com/api/?name=Laura+Garcia&background=20C997&color=fff&size=150'],
            ['reference' => self::PRESTA_MATTHIEU_REFERENCE, 'email' => 'matthieu.robin@coach-forme.fr', 'firstname' => 'Matthieu', 'lastname' => 'Robin', 'phone' => '0610010016', 'avatar' => 'https://ui-avatars.com/api/?name=Matthieu+Robin&background=198754&color=fff&size=150'],
            ['reference' => self::PRESTA_CHLOE_REFERENCE, 'email' => 'chloe.petit@pet-services.fr', 'firstname' => 'Chloé', 'lastname' => 'Petit', 'phone' => '0610010017', 'avatar' => 'https://ui-avatars.com/api/?name=Chloe+Petit&background=FFC107&color=212529&size=150'],
            ['reference' => self::PRESTA_ENZO_REFERENCE, 'email' => 'enzo.morel@animaux-compagnie.fr', 'firstname' => 'Enzo', 'lastname' => 'Morel', 'phone' => '0610010018', 'avatar' => 'https://ui-avatars.com/api/?name=Enzo+Morel&background=FDC500&color=212529&size=150'],
            ['reference' => self::PRESTA_INES_REFERENCE, 'email' => 'ines.diallo@beaute-mobile.fr', 'firstname' => 'Inès', 'lastname' => 'Diallo', 'phone' => '0610010019', 'avatar' => 'https://ui-avatars.com/api/?name=Ines+Diallo&background=D63384&color=fff&size=150'],
            ['reference' => self::PRESTA_SARAH_REFERENCE, 'email' => 'sarah.lemaire@glam-service.fr', 'firstname' => 'Sarah', 'lastname' => 'Lemaire', 'phone' => '0610010020', 'avatar' => 'https://ui-avatars.com/api/?name=Sarah+Lemaire&background=C2185B&color=fff&size=150'],
        ];

        foreach ($prestataires as $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setRoles(['ROLE_PRESTATAIRE']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            $user->setFirstName($data['firstname']);
            $user->setLastName($data['lastname']);
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