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

use App\Entity\ClientProfile;
use App\Entity\User;
use App\Enum\ClientTypeEnum;
use App\Enum\UserStatusEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ClientFixtures extends Fixture
{
    public const CLIENT_JEAN_REFERENCE = 'user_client_jean';
    public const CLIENT_MARIE_REFERENCE = 'user_client_marie';
    public const CLIENT_LUCAS_REFERENCE = 'user_client_lucas';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();
        $plainPassword = '123Test!';

        $clients = [
            ['reference' => self::CLIENT_JEAN_REFERENCE, 'email' => 'jean.dupont@gmail.com', 'firstname' => 'Jean', 'lastname' => 'Dupont', 'phone' => '0612345678', 'avatar' => 'https://ui-avatars.com/api/?name=Jean+Dupont&background=0D6EFD&color=fff&size=150', 'type' => ClientTypeEnum::PARTICULIER, 'company_name' => null],
            ['reference' => self::CLIENT_MARIE_REFERENCE, 'email' => 'marie.lefevre@gmail.com', 'firstname' => 'Marie', 'lastname' => 'Lefevre', 'phone' => '0623456789', 'avatar' => 'https://ui-avatars.com/api/?name=Marie+Lefevre&background=E83E8C&color=fff&size=150', 'type' => ClientTypeEnum::PARTICULIER, 'company_name' => null],
            ['reference' => self::CLIENT_LUCAS_REFERENCE, 'email' => 'lucas.martin@gmail.com', 'firstname' => 'Lucas', 'lastname' => 'Martin', 'phone' => '0634567890', 'avatar' => 'https://ui-avatars.com/api/?name=Lucas+Martin&background=6F42C1&color=fff&size=150', 'type' => ClientTypeEnum::PROFESSIONNEL, 'company_name' => 'Martin Entreprise'],
        ];

        foreach ($clients as $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setRoles(['ROLE_CLIENT']);
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

            $profile = new ClientProfile();
            $profile->setAccount($user);
            $profile->setType($data['type']);
            $profile->setCreatedAt($now);

            if (null !== $data['company_name']) {
                $profile->setCompanyName($data['company_name']);
            }

            $manager->persist($user);
            $manager->persist($profile);
            $this->addReference($data['reference'], $user);
        }

        $manager->flush();
    }
}