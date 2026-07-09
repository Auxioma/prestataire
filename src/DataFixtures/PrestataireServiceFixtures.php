<?php

namespace App\DataFixtures;

use App\Entity\PrestataireProfile;
use App\Entity\PrestataireService;
use App\Entity\Service;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PrestataireServiceFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $position = 1;

        for ($i = 1; $i <= UserFixtures::PRESTATAIRE_COUNT; ++$i) {
            /** @var PrestataireProfile $prestataire */
            $prestataire = $this->getReference(sprintf('prestataire_profile_%d', $i), PrestataireProfile::class);

            for ($offset = 0; $offset < 3; ++$offset) {
                /** @var Service $service */
                $service = $this->getReference(sprintf('service_%d', (($i - 1) * 2 + $offset) % 30 + 1), Service::class);
                $catalogPrice = $this->decimal(45, 950);
                $hasPromo = $offset !== 1;

                $prestataireService = (new PrestataireService())
                    ->setPrestataire($prestataire)
                    ->setService($service)
                    ->setIsActive(true)
                    ->setTitle($service->getName())
                    ->setShortDescription($this->faker->sentence(10))
                    ->setDescription($this->faker->paragraphs(2, true))
                    ->setPricingType($this->faker->randomElement(['forfait', 'horaire', 'sur devis']))
                    ->setPriceFrom($this->decimal(30, 300))
                    ->setPriceTo($this->decimal(120, 1500))
                    ->setPriceUnit($this->faker->randomElement(['heure', 'forfait', 'intervention']))
                    ->setAdditionalInfo('Déplacement inclus dans la zone principale, devis ajusté selon la complexité.')
                    ->setPosition($offset + 1)
                    ->setPrixCatalogue($catalogPrice)
                    ->setTauxReduction($hasPromo ? $this->decimal(8, 28) : null)
                    ->setPromotionCreatedAt($hasPromo ? $this->randomDateTimeImmutable('-40 days', '-2 days') : null)
                    ->setCreatedAt($this->randomDateTimeImmutable('-10 months', '-2 months'))
                    ->setUpdatedAt($this->randomDateTimeImmutable('-20 days'))
                    ->setSlug($this->slugify(sprintf('%s-%s-%d', $prestataire->getCompanyName(), $service->getName(), $offset + 1)));

                $manager->persist($prestataireService);
                $this->addReference(sprintf('prestataire_service_%d', $position++), $prestataireService);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [PrestataireProfileFixtures::class, ServiceFixtures::class];
    }
}
