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

class AdminFixtures extends Fixture
{
    public const ADMIN_REFERENCE = 'user_admin';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        $admin = new User();
        $admin->setEmail('admin@trouvemoi.fr');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin123!'));
        $admin->setFirstName('Admin');
        $admin->setLastName('TrouveMoi');
        $admin->setPhoneNumber('0600000000');
        $admin->setAvatar('https://ui-avatars.com/api/?name=Admin+TrouveMoi&background=212529&color=fff&size=150');
        $admin->setIsVerified(true);
        $admin->setEmailVerifiedAt($now);
        $admin->setStatus(UserStatusEnum::ACTIVE);
        $admin->setCreatedAt($now);
        $admin->setUpdatedAt($now);

        $manager->persist($admin);

        $this->addReference(self::ADMIN_REFERENCE, $admin);

        $manager->flush();
    }
}
