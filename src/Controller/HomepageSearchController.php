<?php

namespace App\Controller;

use App\Entity\PrestataireProfile;
use App\Entity\ServiceCategory;
use App\Form\HomepageSearchType;
use App\Repository\PrestataireProfileRepository;
use App\Search\PrestataireSearchService;
use App\Service\ZoneGeocoder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomepageSearchController extends AbstractController
{
    #[Route('/recherche-prestataires', name: 'app_homepage_search', methods: ['GET'])]
    public function index(
        Request $request,
        PrestataireProfileRepository $prestataireProfileRepository,
        PrestataireSearchService $prestataireSearchService,
        ZoneGeocoder $zoneGeocoder,
    ): Response {
        $form = $this->createForm(HomepageSearchType::class);
        $form->handleRequest($request);

        $data = $form->isSubmitted() ? ($form->getData() ?? []) : [];

        $radiusKm = (int) ($data['radiusKm'] ?? 25);
        $radiusKm = max(5, min(100, $radiusKm));

        $query = mb_trim((string) ($data['query'] ?? ''));
        $location = mb_trim((string) ($data['location'] ?? ''));

        /** @var ServiceCategory|null $subCategory */
        $subCategory = $data['subCategory'] ?? null;

        $searchedLocation = null;
        if ('' !== $location) {
            $searchedLocation = $zoneGeocoder->geocode($location, null);
        }

        $criteria = [
            'query' => $query,
            'location' => $location,
            'subCategory' => $subCategory,
            'searchedLocation' => $searchedLocation,
            'radiusKm' => $radiusKm,
        ];

        $page = max(1, $request->query->getInt('page', 1));
        $perPage = 9;
        $from = ($page - 1) * $perPage;

        $searchResult = $prestataireSearchService->search(
            '' !== $query ? $query : null,
            '' !== $location ? $location : null,
            $subCategory?->getSlug(),
            $perPage,
            $from,
            $searchedLocation,
            $radiusKm,
        );

        $hits = $searchResult['hits'] ?? [];
        $total = (int) ($searchResult['total'] ?? 0);

        $hitIds = array_values(array_filter(array_map(
            static fn (array $hit): ?int => isset($hit['id']) ? (int) $hit['id'] : null,
            $hits
        )));

        $profilesById = [];
        if ([] !== $hitIds) {
            $profiles = $prestataireProfileRepository->createQueryBuilder('p')
                ->leftJoin('p.account', 'a')->addSelect('a')
                ->leftJoin('p.prestataireServices', 'ps')->addSelect('ps')
                ->leftJoin('ps.service', 's')->addSelect('s')
                ->leftJoin('s.category', 'c')->addSelect('c')
                ->leftJoin('c.parent', 'parent')->addSelect('parent')
                ->leftJoin('p.prestataireInterventionZones', 'z')->addSelect('z')
                ->andWhere('p.id IN (:ids)')
                ->setParameter('ids', $hitIds)
                ->getQuery()
                ->getResult();

            foreach ($profiles as $profile) {
                $profilesById[$profile->getId()] = $profile;
            }
        }

        $prestataires = [];
        foreach ($hits as $hit) {
            $id = isset($hit['id']) ? (int) $hit['id'] : null;
            if (null === $id || !isset($profilesById[$id])) {
                continue;
            }

            $profile = $profilesById[$id];

            if (null !== $searchedLocation) {
                if (!$this->isPrestataireMatchingSearchedLocation($profile, $searchedLocation, $radiusKm)) {
                    continue;
                }

                $profile->matchedDistanceKm = $this->getClosestMatchingDistanceKm($profile, $searchedLocation, $radiusKm);
            }

            $prestataires[] = $profile;
        }

        if (null !== $searchedLocation) {
            usort($prestataires, static function ($a, $b): int {
                return ($a->matchedDistanceKm ?? 999999) <=> ($b->matchedDistanceKm ?? 999999);
            });
        }

        $filteredTotal = count($prestataires);
        $totalPages = max(1, (int) ceil($filteredTotal / $perPage));
        $page = min($page, $totalPages);

        return $this->render('search/homepage_results.html.twig', [
            'searchForm' => $form->createView(),
            'results' => $prestataires,
            'query' => $query,
            'location' => $location,
            'subCategory' => $subCategory,
            'criteria' => $criteria,
            'pageTitle' => 'Résultats de recherche',
            'totalResults' => $filteredTotal,
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ]);
    }

    private function isPrestataireMatchingSearchedLocation(
        PrestataireProfile $prestataire,
        ?array $searchedLocation,
        int $radiusKm = 25,
    ): bool {
        if (null === $searchedLocation) {
            return true;
        }

        if (!isset($searchedLocation['latitude'], $searchedLocation['longitude'])) {
            return true;
        }

        $searchedLat = (float) $searchedLocation['latitude'];
        $searchedLng = (float) $searchedLocation['longitude'];

        foreach ($prestataire->getPrestataireInterventionZones() as $zone) {
            if (!$zone->isActive()) {
                continue;
            }

            if (null === $zone->getLatitude() || null === $zone->getLongitude()) {
                continue;
            }

            $distanceKm = $this->distanceKm(
                $searchedLat,
                $searchedLng,
                (float) $zone->getLatitude(),
                (float) $zone->getLongitude(),
            );

            if ($distanceKm <= $radiusKm) {
                return true;
            }
        }

        return false;
    }

    private function getClosestMatchingDistanceKm(
        PrestataireProfile $prestataire,
        ?array $searchedLocation,
        int $radiusKm = 25,
    ): ?float {
        if (null === $searchedLocation) {
            return null;
        }

        if (!isset($searchedLocation['latitude'], $searchedLocation['longitude'])) {
            return null;
        }

        $searchedLat = (float) $searchedLocation['latitude'];
        $searchedLng = (float) $searchedLocation['longitude'];

        $bestDistance = null;

        foreach ($prestataire->getPrestataireInterventionZones() as $zone) {
            if (!$zone->isActive()) {
                continue;
            }

            if (null === $zone->getLatitude() || null === $zone->getLongitude()) {
                continue;
            }

            $distanceKm = $this->distanceKm(
                $searchedLat,
                $searchedLng,
                (float) $zone->getLatitude(),
                (float) $zone->getLongitude(),
            );

            if ($distanceKm <= $radiusKm) {
                if (null === $bestDistance || $distanceKm < $bestDistance) {
                    $bestDistance = $distanceKm;
                }
            }
        }

        return $bestDistance;
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}