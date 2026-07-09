<?php

namespace App\DataFixtures;

use App\Entity\PrestataireDocument;
use App\Entity\PrestataireProfile;
use App\Enum\PrestataireDocumentStatusEnum;
use App\Enum\PrestataireDocumentTypeEnum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PrestataireDocumentFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $types = PrestataireDocumentTypeEnum::cases();
        $statuses = PrestataireDocumentStatusEnum::cases();

        for ($i = 1; $i <= 20; ++$i) {
            /** @var PrestataireProfile $prestataire */
            $prestataire = $this->getReference(sprintf('prestataire_profile_%d', (($i - 1) % UserFixtures::PRESTATAIRE_COUNT) + 1), PrestataireProfile::class);
            $type = $types[($i - 1) % count($types)];

            $document = (new PrestataireDocument())
                ->setPrestataireProfile($prestataire)
                ->setType($type)
                ->setStatus($statuses[($i - 1) % count($statuses)])
                ->setIsVisibleToClient($i % 2 === 0)
                ->setIssuedAt($this->faker->dateTimeBetween('-18 months', '-2 months'))
                ->setExpiresAt($this->faker->dateTimeBetween('+1 month', '+18 months'))
                ->setNotes(sprintf('Document %s transmis dans le cadre de la validation du profil.', $type->value))
                ->setCreatedAt($this->randomDateTimeImmutable('-10 months', '-2 months'))
                ->setUpdatedAt($this->randomDateTimeImmutable('-20 days'));

            $this->attachRemoteImage(
                $document,
                'setDocumentFile',
                sprintf('https://picsum.photos/800/1100?random=document-%d', $i)
            );

            $manager->persist($document);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [PrestataireProfileFixtures::class];
    }
}
