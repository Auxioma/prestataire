<?php

namespace App\DataFixtures;

use App\Entity\PrestataireInterventionZone;
use App\Entity\PrestataireProfile;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PrestataireInterventionZoneFixtures extends BaseFixture implements DependentFixtureInterface
{
    /**
     * @var list<array{city: string, postal: string, department: string, region: string, lat: string, lng: string}>
     */
    private const ZONES = [
        ['city' => 'Bordeaux', 'postal' => '33000', 'department' => 'Gironde', 'region' => 'Nouvelle-Aquitaine', 'lat' => '44.8377890', 'lng' => '-0.5791800'],
        ['city' => 'Mérignac', 'postal' => '33700', 'department' => 'Gironde', 'region' => 'Nouvelle-Aquitaine', 'lat' => '44.8422000', 'lng' => '-0.6458000'],
        ['city' => 'Pessac', 'postal' => '33600', 'department' => 'Gironde', 'region' => 'Nouvelle-Aquitaine', 'lat' => '44.8069000', 'lng' => '-0.6318000'],
        ['city' => 'Talence', 'postal' => '33400', 'department' => 'Gironde', 'region' => 'Nouvelle-Aquitaine', 'lat' => '44.8089000', 'lng' => '-0.5895000'],
        ['city' => 'Arcachon', 'postal' => '33120', 'department' => 'Gironde', 'region' => 'Nouvelle-Aquitaine', 'lat' => '44.6589000', 'lng' => '-1.1681000'],
        ['city' => 'Libourne', 'postal' => '33500', 'department' => 'Gironde', 'region' => 'Nouvelle-Aquitaine', 'lat' => '44.9145000', 'lng' => '-0.2419000'],
        ['city' => 'Gradignan', 'postal' => '33170', 'department' => 'Gironde', 'region' => 'Nouvelle-Aquitaine', 'lat' => '44.7727000', 'lng' => '-0.6135000'],
        ['city' => 'Bruges', 'postal' => '33520', 'department' => 'Gironde', 'region' => 'Nouvelle-Aquitaine', 'lat' => '44.8826000', 'lng' => '-0.6125000'],
    ];

    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= UserFixtures::PRESTATAIRE_COUNT; ++$i) {
            /** @var PrestataireProfile $prestataire */
            $prestataire = $this->getReference(sprintf('prestataire_profile_%d', $i), PrestataireProfile::class);
            $primary = self::ZONES[($i - 1) % count(self::ZONES)];
            $secondary = self::ZONES[$i % count(self::ZONES)];

            foreach ([[$primary, true], [$secondary, false]] as [$zoneData, $isMain]) {
                $zone = (new PrestataireInterventionZone())
                    ->setPrestataireProfile($prestataire)
                    ->setCity($zoneData['city'])
                    ->setPostalCode($zoneData['postal'])
                    ->setDepartment($zoneData['department'])
                    ->setRegion($zoneData['region'])
                    ->setLatitude($zoneData['lat'])
                    ->setLongitude($zoneData['lng'])
                    ->setRadiusKm($isMain ? 20 : 35)
                    ->setIsMainZone($isMain)
                    ->setIsActive(true);

                $manager->persist($zone);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [PrestataireProfileFixtures::class];
    }
}
