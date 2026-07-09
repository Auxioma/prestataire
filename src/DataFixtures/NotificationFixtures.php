<?php

namespace App\DataFixtures;

use App\Entity\Notification;
use App\Entity\User;
use App\Enum\NotificationTypeEnum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class NotificationFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $types = NotificationTypeEnum::cases();

        for ($i = 1; $i <= 36; ++$i) {
            $userReference = $i % 2 === 0
                ? sprintf('user_prestataire_%d', (($i - 1) % UserFixtures::PRESTATAIRE_COUNT) + 1)
                : sprintf('user_client_%d', (($i - 1) % UserFixtures::CLIENT_COUNT) + 1);

            /** @var User $recipient */
            $recipient = $this->getReference($userReference, User::class);
            $type = $types[($i - 1) % count($types)];

            $notification = (new Notification())
                ->setRecipient($recipient)
                ->setType($type)
                ->setTitle($type->getLabel())
                ->setBody($this->faker->sentence(18))
                ->setTargetUrl('/tableau-de-bord')
                ->setCreatedAt($this->randomDateTimeImmutable('-3 months', 'now'))
                ->setMetadata(['fixture' => true, 'index' => $i]);

            if ($i % 4 === 0) {
                $notification->setIsRead(true);
            }

            $manager->persist($notification);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
