<?php

namespace App\DataFixtures;

use App\Entity\PrestataireAvailability;
use App\Entity\PrestataireProfile;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PrestataireAvailabilityFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($prestataireIndex = 1; $prestataireIndex <= UserFixtures::PRESTATAIRE_COUNT; ++$prestataireIndex) {
            /** @var PrestataireProfile $prestataire */
            $prestataire = $this->getReference(sprintf('prestataire_profile_%d', $prestataireIndex), PrestataireProfile::class);

            for ($day = 1; $day <= 7; ++$day) {
                $availability = (new PrestataireAvailability())
                    ->setPrestataireProfile($prestataire)
                    ->setDayOfWeek($day)
                    ->setCreatedAt($this->faker->dateTimeBetween('-1 year', '-2 months'))
                    ->setUpdatedAt($this->faker->dateTimeBetween('-2 months', 'now'));

                if ($day <= 5) {
                    $availability
                        ->setMorningEnabled(true)
                        ->setMorningStart($this->time('08:30'))
                        ->setMorningEnd($this->time('12:30'))
                        ->setAfternoonEnabled(true)
                        ->setAfternoonStart($this->time('14:00'))
                        ->setAfternoonEnd($this->time('18:00'));
                } elseif (6 === $day) {
                    $availability
                        ->setMorningEnabled(true)
                        ->setMorningStart($this->time('09:00'))
                        ->setMorningEnd($this->time('12:00'))
                        ->setAfternoonEnabled($prestataireIndex % 2 === 0)
                        ->setAfternoonStart($prestataireIndex % 2 === 0 ? $this->time('14:00') : null)
                        ->setAfternoonEnd($prestataireIndex % 2 === 0 ? $this->time('17:00') : null);
                }

                $manager->persist($availability);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [PrestataireProfileFixtures::class];
    }
}
