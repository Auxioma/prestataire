<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Enum\UserStatusEnum;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class UserFixtures extends BaseFixture
{
    public const CLIENT_COUNT = 10;
    public const PRESTATAIRE_COUNT = 14;

    public function __construct(
        SluggerInterface $slugger,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct($slugger);
    }

    public function load(ObjectManager $manager): void
    {
        $admin = (new User())
            ->setEmail('admin@trouvemoi.fr')
            ->setRoles(['ROLE_ADMIN'])
            ->setFirstName('Amandine')
            ->setLastName('Leroy')
            ->setPhoneNumber('0600000001')
            ->setLocale('fr')
            ->setTimezone('Europe/Paris')
            ->setStatus(UserStatusEnum::ACTIVE)
            ->setIsVerified(true)
            ->setEmailVerifiedAt($this->randomDateTimeImmutable('-18 months', '-10 months'))
            ->setPhoneVerifiedAt($this->randomDateTimeImmutable('-18 months', '-10 months'))
            ->setCreatedAt($this->randomDateTimeImmutable('-2 years', '-18 months'))
            ->setLastLoginAt($this->randomDateTimeImmutable('-10 days'))
            ->setLoginCount(184);

        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, '123Admin!')
        );

        $this->attachRemoteImage(
            $admin,
            'setAvatarFile',
            'https://randomuser.me/api/portraits/women/1.jpg'
        );

        $manager->persist($admin);
        $this->addReference('user_admin_1', $admin);

        for ($i = 1; $i <= self::CLIENT_COUNT; ++$i) {
            $user = (new User())
                ->setEmail(sprintf('client%d@trouvemoi.fr', $i))
                ->setRoles(['ROLE_CLIENT'])
                ->setFirstName($this->faker->firstName())
                ->setLastName($this->faker->lastName())
                ->setPhoneNumber($this->faker->numerify('06########'))
                ->setLocale('fr')
                ->setTimezone('Europe/Paris')
                ->setStatus(UserStatusEnum::ACTIVE)
                ->setIsVerified(true)
                ->setEmailVerifiedAt($this->randomDateTimeImmutable('-18 months', '-1 month'))
                ->setPhoneVerifiedAt($this->randomDateTimeImmutable('-18 months', '-1 month'))
                ->setCreatedAt($this->randomDateTimeImmutable('-18 months', '-3 months'))
                ->setLastLoginAt($this->randomDateTimeImmutable('-14 days'))
                ->setLoginCount($this->faker->numberBetween(3, 90));

            $user->setPassword(
                $this->passwordHasher->hashPassword($user, '123Client!')
            );

            $this->attachRemoteImage(
                $user,
                'setAvatarFile',
                sprintf(
                    'https://randomuser.me/api/portraits/%s/%d.jpg',
                    $i % 2 === 0 ? 'women' : 'men',
                    ($i % 99) + 1
                )
            );

            $manager->persist($user);
            $this->addReference(sprintf('user_client_%d', $i), $user);
        }

        for ($i = 1; $i <= self::PRESTATAIRE_COUNT; ++$i) {
            $user = (new User())
                ->setEmail(sprintf('prestataire%d@trouvemoi.fr', $i))
                ->setRoles(['ROLE_PRESTATAIRE'])
                ->setFirstName($this->faker->firstName())
                ->setLastName($this->faker->lastName())
                ->setPhoneNumber($this->faker->numerify('06########'))
                ->setLocale('fr')
                ->setTimezone('Europe/Paris')
                ->setStatus(UserStatusEnum::ACTIVE)
                ->setIsVerified(true)
                ->setEmailVerifiedAt($this->randomDateTimeImmutable('-18 months', '-2 months'))
                ->setPhoneVerifiedAt($this->randomDateTimeImmutable('-18 months', '-2 months'))
                ->setCreatedAt($this->randomDateTimeImmutable('-20 months', '-3 months'))
                ->setLastLoginAt($this->randomDateTimeImmutable('-7 days'))
                ->setLoginCount($this->faker->numberBetween(12, 220));

            $user->setPassword(
                $this->passwordHasher->hashPassword($user, '123Presta!')
            );

            $this->attachRemoteImage(
                $user,
                'setAvatarFile',
                sprintf(
                    'https://randomuser.me/api/portraits/%s/%d.jpg',
                    $i % 2 === 0 ? 'men' : 'women',
                    (($i + 20) % 99) + 1
                )
            );

            $manager->persist($user);
            $this->addReference(sprintf('user_prestataire_%d', $i), $user);
        }

        $manager->flush();
    }
}