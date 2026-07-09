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
        $form = $this->createForm(HomepageSearchType::class, [
            'query' => '',
            'subCategory' => null,
            'location' => '',
            'radiusKm' => 25,
        ], [
            'method' => 'GET',
        ]);

        

        $form->handleRequest($request);

        $data = ($form->isSubmitted() && $form->isValid())
            ? ($form->getData() ?? [])
            : [];

        $radiusKm = max(5, min(100, (int) ($data['radiusKm'] ?? 25)));
        $query = trim((string) ($data['query'] ?? ''));
        $location = trim((string) ($data['location'] ?? ''));

        /** @var ServiceCategory|null $subCategory */
        $subCategory = $data['subCategory'] ?? null;

        $searchedLocation = $location !== '' ? $zoneGeocoder->geocode($location, null) : null;

        $criteria = [
            'query' => $query,
            'location' => $location,
            'subCategory' => $subCategory,
            'searchedLocation' => $searchedLocation,
            'radiusKm' => $radiusKm,
        ];

        $page = max(1, $request->query->getInt('page', 1));
        $perPage = 9;

        $searchResponse = $prestataireSearchService->search(
            $query !== '' ? $query : null,
            $location !== '' ? $location : null,
            $subCategory?->getSlug(),
            200,
            0,
            $searchedLocation,
            $radiusKm,
        );

        $hits = $searchResponse['hits'] ?? [];
        $hitIds = array_values(array_filter(array_map(
            static fn (array $hit): ?int => isset($hit['id']) ? (int) $hit['id'] : null,
            $hits
        )));

        $profilesById = [];

        if ($hitIds !== []) {
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

        $directResults = [];
        $fallbackResults = [];

        foreach ($hits as $hit) {
            $id = isset($hit['id']) ? (int) $hit['id'] : null;

            if ($id === null || !isset($profilesById[$id])) {
                continue;
            }

            $profile = $profilesById[$id];

            if ($searchedLocation === null) {
                $directResults[] = $profile;
                continue;
            }

            $matchType = $this->getPrestataireMatchType($profile, $searchedLocation, $radiusKm);

            if ($matchType === null) {
                continue;
            }

            $profile->matchedDistanceKm = $this->getClosestReachableDistanceKm($profile, $searchedLocation, $radiusKm);

            if ($matchType === 'direct') {
                $directResults[] = $profile;
                continue;
            }

            $fallbackResults[] = $profile;
        }

        usort(
            $directResults,
            static fn ($a, $b): int => ($a->matchedDistanceKm ?? 999999) <=> ($b->matchedDistanceKm ?? 999999)
        );

        usort(
            $fallbackResults,
            static fn ($a, $b): int => ($a->matchedDistanceKm ?? 999999) <=> ($b->matchedDistanceKm ?? 999999)
        );

        $totalPages = max(1, (int) ceil(count($directResults) / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $pagedDirectResults = array_slice($directResults, $offset, $perPage);

        return $this->render('search/homepage_results.html.twig', [
            'searchForm' => $form->createView(),
            'results' => $pagedDirectResults,
            'directResults' => $pagedDirectResults,
            'fallbackResults' => $fallbackResults,
            'query' => $query,
            'location' => $location,
            'subCategory' => $subCategory,
            'criteria' => $criteria,
            'pageTitle' => 'Résultats de recherche',
            'totalResults' => count($directResults),
            'totalFallbackResults' => count($fallbackResults),
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ]);
    }

    private function getPrestataireMatchType(
        PrestataireProfile $prestataire,
        ?array $searchedLocation,
        int $radiusKm = 25,
    ): ?string {
        if ($searchedLocation === null) {
            return 'direct';
        }

        if (!isset($searchedLocation['latitude'], $searchedLocation['longitude'])) {
            return 'direct';
        }

        $searchedLat = (float) $searchedLocation['latitude'];
        $searchedLng = (float) $searchedLocation['longitude'];

        $hasFallbackMatch = false;

        foreach ($prestataire->getPrestataireInterventionZones() as $zone) {
            if (!$zone->isActive()) {
                continue;
            }

            if ($zone->getLatitude() === null || $zone->getLongitude() === null) {
                continue;
            }

            $distanceKm = $this->distanceKm(
                $searchedLat,
                $searchedLng,
                (float) $zone->getLatitude(),
                (float) $zone->getLongitude(),
            );

            if ($distanceKm <= $radiusKm) {
                return 'direct';
            }

            $zoneRadiusKm = max(0, (int) $zone->getRadiusKm());

            if ($zoneRadiusKm > 0 && $distanceKm <= ($radiusKm + $zoneRadiusKm)) {
                $hasFallbackMatch = true;
            }
        }

        return $hasFallbackMatch ? 'fallback' : null;
    }

    private function getClosestReachableDistanceKm(
        PrestataireProfile $prestataire,
        ?array $searchedLocation,
        int $radiusKm = 25,
    ): ?float {
        if ($searchedLocation === null) {
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

            if ($zone->getLatitude() === null || $zone->getLongitude() === null) {
                continue;
            }

            $distanceKm = $this->distanceKm(
                $searchedLat,
                $searchedLng,
                (float) $zone->getLatitude(),
                (float) $zone->getLongitude(),
            );

            $zoneRadiusKm = max(0, (int) $zone->getRadiusKm());

            if ($distanceKm <= ($radiusKm + $zoneRadiusKm)) {
                if ($bestDistance === null || $distanceKm < $bestDistance) {
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