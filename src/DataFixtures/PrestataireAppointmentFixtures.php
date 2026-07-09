<?php

namespace App\DataFixtures;

use App\Entity\ClientProfile;
use App\Entity\PrestataireAppointment;
use App\Entity\PrestataireProfile;
use App\Entity\PrestataireService;
use App\Enum\PrestataireAppointmentStatusEnum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PrestataireAppointmentFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $statuses = PrestataireAppointmentStatusEnum::cases();

        for ($i = 1; $i <= 18; ++$i) {
            /** @var PrestataireProfile $prestataire */
            $prestataire = $this->getReference(sprintf('prestataire_profile_%d', (($i - 1) % UserFixtures::PRESTATAIRE_COUNT) + 1), PrestataireProfile::class);
            /** @var ClientProfile $client */
            $client = $this->getReference(sprintf('client_profile_%d', (($i - 1) % UserFixtures::CLIENT_COUNT) + 1), ClientProfile::class);
            /** @var PrestataireService $prestation */
            $prestation = $this->getReference(sprintf('prestataire_service_%d', (($i - 1) % 42) + 1), PrestataireService::class);
            $startsAt = $this->faker->dateTimeBetween('-20 days', '+40 days');
            $endsAt = (clone $startsAt)->modify('+2 hours');

            $appointment = (new PrestataireAppointment())
                ->setPrestataire($prestataire)
                ->setClient($client)
                ->setPrestation($prestation)
                ->setTitle('Intervention ' . $prestation->getTitle())
                ->setDescription($this->faker->paragraph())
                ->setStartsAt($startsAt)
                ->setEndsAt($endsAt)
                ->setStatus($statuses[($i - 1) % count($statuses)])
                ->setLocationLabel($client->getDefaultAddress() . ', ' . $client->getDefaultCity())
                ->setCreatedAt($this->faker->dateTimeBetween('-3 months', '-1 month'))
                ->setUpdatedAt($this->faker->dateTimeBetween('-15 days', 'now'))
                ->setSlug($this->slugify(sprintf('intervention-%s-%d', $prestation->getTitle(), $i)));

            $manager->persist($appointment);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ClientProfileFixtures::class, PrestataireServiceFixtures::class];
    }
}
