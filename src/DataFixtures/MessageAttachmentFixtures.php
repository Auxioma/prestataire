<?php

namespace App\DataFixtures;

use App\Entity\Message;
use App\Entity\MessageAttachment;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MessageAttachmentFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 16; ++$i) {
            /** @var Message $message */
            $message = $this->getReference(sprintf('message_%d', ($i * 3) % 72 + 1), Message::class);

            $attachment = (new MessageAttachment())
                ->setMessage($message)
                ->setPosition(0)
                ->setCreatedAt($this->randomDateTimeImmutable('-2 months', '-1 day'))
                ->setUpdatedAt($this->randomDateTimeImmutable('-10 days'));

            $this->attachRemoteImage(
                $attachment,
                'setFile',
                sprintf('https://picsum.photos/1600/1200?random=message-%d', $i)
            );
            $manager->persist($attachment);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [MessageFixtures::class];
    }
}
