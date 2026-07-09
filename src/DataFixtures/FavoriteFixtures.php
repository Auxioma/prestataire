<?php

namespace App\DataFixtures;

use App\Entity\Favorite;
use App\Entity\PrestataireProfile;
use App\Entity\PrestataireService;
use App\Entity\User;
use App\Enum\FavoriteTypeEnum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class FavoriteFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 30; ++$i) {
            /** @var User $user */
            $user = $this->getReference(sprintf('user_client_%d', (($i - 1) % UserFixtures::CLIENT_COUNT) + 1), User::class);
            $type = $i % 3 === 0 ? FavoriteTypeEnum::BON_PLAN : ($i % 2 === 0 ? FavoriteTypeEnum::PRESTATION : FavoriteTypeEnum::PRESTATAIRE);

            $favorite = (new Favorite())
                ->setUser($user)
                ->setType($type)
                ->setCreatedAt($this->faker->dateTimeBetween('-5 months', '-1 day'));

            if ($type === FavoriteTypeEnum::PRESTATAIRE) {
                /** @var PrestataireProfile $prestataire */
                $prestataire = $this->getReference(sprintf('prestataire_profile_%d', (($i - 1) % UserFixtures::PRESTATAIRE_COUNT) + 1), PrestataireProfile::class);
                $favorite->setTargetId($prestataire->getId());
            } else {
                /** @var PrestataireService $prestation */
                $prestation = $this->getReference(sprintf('prestataire_service_%d', (($i - 1) % 42) + 1), PrestataireService::class);
                $favorite->setTargetId($prestation->getId());
            }

            $manager->persist($favorite);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class, PrestataireProfileFixtures::class, PrestataireServiceFixtures::class];
    }
}
