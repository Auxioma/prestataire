<?php

namespace App\Service;

use App\Entity\PrestataireAvailability;
use App\Entity\PrestataireDocument;
use App\Entity\PrestataireInterventionZone;
use App\Entity\PrestataireProfile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;

final class PrestataireProfileManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function getOrCreateProfile(User $user): PrestataireProfile
    {
        if (null === $user->getId()) {
            throw new \LogicException('Impossible de charger un utilisateur non persiste.');
        }

        $user = $this->userRepository->findOneWithProfilesById($user->getId()) ?? $user;

        if (null === $user->getPrestataireProfile()) {
            $profile = new PrestataireProfile();
            $user->setPrestataireProfile($profile);
            $profile->setAccount($user);

            $this->entityManager->persist($profile);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        return $user->getPrestataireProfile();
    }

    public function ensureDefaultAvailabilities(PrestataireProfile $prestataireProfile): void
    {
        $existingDays = [];

        foreach ($prestataireProfile->getAvailabilities() as $availability) {
            $existingDays[] = $availability->getDayOfWeek();
        }

        $hasChanges = false;

        for ($day = 1; $day <= 7; ++$day) {
            if (in_array($day, $existingDays, true)) {
                continue;
            }

            $availability = new PrestataireAvailability();
            $availability->setPrestataireProfile($prestataireProfile);
            $availability->setDayOfWeek($day);

            $prestataireProfile->addAvailability($availability);
            $this->entityManager->persist($availability);
            $hasChanges = true;
        }

        if ($hasChanges) {
            $this->entityManager->persist($prestataireProfile);
            $this->entityManager->flush();
        }
    }

    /**
     * @return list<PrestataireAvailability>
     */
    public function getSortedAvailabilities(PrestataireProfile $prestataireProfile): array
    {
        $availabilities = $prestataireProfile->getAvailabilities()->toArray();

        usort(
            $availabilities,
            static fn (PrestataireAvailability $a, PrestataireAvailability $b): int => $a->getDayOfWeek() <=> $b->getDayOfWeek()
        );

        return $availabilities;
    }

    /**
     * @return list<PrestataireDocument>
     */
    public function getSortedDocuments(PrestataireProfile $prestataireProfile): array
    {
        $documents = $prestataireProfile->getDocuments()->toArray();

        usort(
            $documents,
            static fn (PrestataireDocument $a, PrestataireDocument $b): int => ($b->getCreatedAt()?->getTimestamp() ?? 0) <=> ($a->getCreatedAt()?->getTimestamp() ?? 0)
        );

        return $documents;
    }

    public function syncSlug(PrestataireProfile $prestataireProfile): void
    {
        $companyName = trim((string) $prestataireProfile->getCompanyName());
        $baseSlug = mb_strtolower((string) $this->slugger->slug('' !== $companyName ? $companyName : 'prestataire'));

        if ('' === $baseSlug) {
            $baseSlug = 'prestataire';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while ($this->slugExistsForAnotherProfile($slug, $prestataireProfile)) {
            $slug = sprintf('%s-%d', $baseSlug, $suffix);
            ++$suffix;
        }

        $prestataireProfile->setSlug($slug);
    }

    private function slugExistsForAnotherProfile(string $slug, PrestataireProfile $prestataireProfile): bool
    {
        $existingProfile = $this->entityManager
            ->getRepository(PrestataireProfile::class)
            ->findOneBy(['slug' => $slug]);

        if (!$existingProfile instanceof PrestataireProfile) {
            return false;
        }

        return $existingProfile->getId() !== $prestataireProfile->getId();
    }

    public function buildZoneMap(iterable $zones): ?Map
    {
        $firstMappableZone = null;

        foreach ($zones as $existingZone) {
            if (
                $existingZone instanceof PrestataireInterventionZone
                && null !== $existingZone->getLatitude()
                && null !== $existingZone->getLongitude()
            ) {
                $firstMappableZone = $existingZone;
                break;
            }
        }

        if (!$firstMappableZone instanceof PrestataireInterventionZone) {
            return null;
        }

        $zoneMap = (new Map('default'))
            ->center(new Point(
                (float) $firstMappableZone->getLatitude(),
                (float) $firstMappableZone->getLongitude()
            ))
            ->zoom(8)
            ->options(
                (new LeafletOptions())
                    ->tileLayer(new TileLayer(
                        url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                        options: ['maxZoom' => 19]
                    ))
            );

        foreach ($zones as $existingZone) {
            if (
                !$existingZone instanceof PrestataireInterventionZone
                || null === $existingZone->getLatitude()
                || null === $existingZone->getLongitude()
            ) {
                continue;
            }

            $label = $existingZone->getCity() ?: 'Zone d’intervention';

            $zoneMap->addMarker(new Marker(
                position: new Point(
                    (float) $existingZone->getLatitude(),
                    (float) $existingZone->getLongitude()
                ),
                title: $label,
                infoWindow: new InfoWindow(
                    content: '<strong>' . htmlspecialchars($label) . '</strong><br>Rayon : ' . (int) $existingZone->getRadiusKm() . ' km'
                )
            ));
        }

        return $zoneMap;
    }
}
