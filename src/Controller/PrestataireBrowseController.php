<?php

/**
 * Copyright(c) 2026 Trouve moi
 *
 * Ce fichier fait partie d’un projet développé par Auxioma Web Agency.
 * Tous droits réservés.
 *
 * Ce code source est la propriété exclusive de Auxioma Web Agency.
 * Toute reproduction, modification, distribution ou utilisation sans autorisation préalable est interdite.
 */

namespace App\Controller;

use App\Entity\PrestataireProfile;
use App\Entity\ServiceCategory;
use App\Form\PrestataireBrowseFilterType;
use App\Repository\PrestataireProfileRepository;
use App\Repository\ServiceCategoryRepository;
use App\Search\PrestataireSearchService;
use App\Service\ZoneGeocoder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Gère les actions liées à prestataire browse.
 */
class PrestataireBrowseController extends AbstractController
{
    #[Route('/prestataires', name: 'app_prestataire_browse', methods: ['GET'])]
    /**
     * Affiche la page principale de ce contrôleur.
     *
     * @return Response
     */
    public function index(
        Request $request,
        PrestataireProfileRepository $profileRepository,
        ServiceCategoryRepository $categoryRepository,
        PrestataireSearchService $prestataireSearchService,
        ZoneGeocoder $zoneGeocoder,
    ): Response {
        $selectedCategory = null;
        $categoryId = $request->query->get('category');
        if (null !== $categoryId && '' !== (string) $categoryId) {
            $candidate = $categoryRepository->find($categoryId);
            if ($candidate instanceof ServiceCategory && null === $candidate->getParent() && $candidate->isActive()) {
                $selectedCategory = $candidate;
            }
        }

        $form = $this->createForm(PrestataireBrowseFilterType::class, [
            'query' => '',
            'location' => '',
            'radiusKm' => 25,
            'category' => $selectedCategory,
            'subCategory' => null,
            'sort' => 'relevance',
        ], [
            'method' => 'GET',
            'selected_category' => $selectedCategory,
        ]);

        $form->handleRequest($request);

        $data = ($form->isSubmitted() && $form->isValid())
            ? ($form->getData() ?? [])
            : [];

        $query = trim((string) ($data['query'] ?? ''));
        $location = trim((string) ($data['location'] ?? ''));
        $radiusKm = max(5, min(100, (int) ($data['radiusKm'] ?? 25)));
        /** @var ServiceCategory|null $category */
        $category = $data['category'] ?? $selectedCategory;
        /** @var ServiceCategory|null $subCategory */
        $subCategory = $data['subCategory'] ?? null;
        $sort = (string) ($data['sort'] ?? 'relevance');
        $searchedLocation = $location !== '' ? $zoneGeocoder->geocode($location, null) : null;

        if ($subCategory instanceof ServiceCategory) {
            if (!$subCategory->isActive() || null === $subCategory->getParent()) {
                $subCategory = null;
            } elseif (null === $category || $subCategory->getParent()?->getId() !== $category->getId()) {
                $category = $subCategory->getParent();
            }
        }

        $searchResponse = $prestataireSearchService->browseSearch(
            $query !== '' ? $query : null,
            $location !== '' ? $location : null,
            $category?->getSlug(),
            $subCategory?->getSlug(),
            $sort,
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

        $profiles = [];

        if ($hitIds !== []) {
            $fetchedProfiles = $profileRepository->createQueryBuilder('p')
                ->leftJoin('p.account', 'a')->addSelect('a')
                ->andWhere('p.id IN (:ids)')
                ->setParameter('ids', $hitIds)
                ->getQuery()
                ->getResult();

            $profilesById = [];
            foreach ($fetchedProfiles as $profile) {
                $profilesById[(int) $profile->getId()] = $profile;
            }

            foreach ($hitIds as $id) {
                if (isset($profilesById[$id])) {
                    $profiles[] = $profilesById[$id];
                }
            }
        }

        $directResults = [];
        $fallbackResults = [];

        foreach ($profiles as $profile) {
            if (!$profile instanceof PrestataireProfile) {
                continue;
            }

            if ($searchedLocation === null) {
                $directResults[] = $profile;
                continue;
            }

            $matchType = $this->getPrestataireMatchType($profile, $searchedLocation, $location, $radiusKm);

            if (null === $matchType) {
                continue;
            }

            $profile->matchedDistanceKm = $this->getClosestReachableDistanceKm($profile, $searchedLocation, $location, $radiusKm);

            if ('direct' === $matchType) {
                $directResults[] = $profile;
                continue;
            }

            $fallbackResults[] = $profile;
        }

        if ($searchedLocation !== null) {
            usort(
                $directResults,
                static fn ($a, $b): int => ($a->matchedDistanceKm ?? 999999) <=> ($b->matchedDistanceKm ?? 999999)
            );

            usort(
                $fallbackResults,
                static fn ($a, $b): int => ($a->matchedDistanceKm ?? 999999) <=> ($b->matchedDistanceKm ?? 999999)
            );
        }

        $page = max(1, $request->query->getInt('page', 1));
        $perPage = 9;
        $totalResults = count($directResults);
        $totalPages = max(1, (int) ceil($totalResults / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $pagedProfiles = array_slice($directResults, $offset, $perPage);

        $pageTitle = $query !== ''
            ? 'Résultats pour "'.$query.'"'
            : 'Tous nos prestataires';

        return $this->render('prestataire_browse/prestataire_browse.html.twig', [
            'browseForm' => $form->createView(),
            'profiles' => $pagedProfiles,
            'directResults' => $pagedProfiles,
            'fallbackResults' => $fallbackResults,
            'query' => $query,
            'location' => $location,
            'radiusKm' => $radiusKm,
            'selectedCategory' => $category,
            'selectedSubCategory' => $subCategory,
            'current_sort' => $sort,
            'page_title' => $pageTitle,
            'totalResults' => $totalResults,
            'totalFallbackResults' => count($fallbackResults),
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    private function getPrestataireMatchType(
        PrestataireProfile $prestataire,
        ?array $searchedLocation,
        ?string $location,
        int $radiusKm = 25,
    ): ?string {
        $normalizedLocation = $this->normalizeLocationValue($location);

        if ('' !== $normalizedLocation && $this->matchesLocationText($prestataire, $normalizedLocation, $searchedLocation)) {
            return 'direct';
        }

        if ($searchedLocation === null || !isset($searchedLocation['latitude'], $searchedLocation['longitude'])) {
            return 'direct';
        }

        $searchedLat = (float) $searchedLocation['latitude'];
        $searchedLng = (float) $searchedLocation['longitude'];
        $hasFallbackMatch = false;

        foreach ($prestataire->getPrestataireInterventionZones() as $zone) {
            if (!$zone->isActive() || null === $zone->getLatitude() || null === $zone->getLongitude()) {
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
        ?string $location,
        int $radiusKm = 25,
    ): ?float {
        $normalizedLocation = $this->normalizeLocationValue($location);

        if ('' !== $normalizedLocation && $this->matchesLocationText($prestataire, $normalizedLocation, $searchedLocation)) {
            return 0.0;
        }

        if ($searchedLocation === null || !isset($searchedLocation['latitude'], $searchedLocation['longitude'])) {
            return null;
        }

        $searchedLat = (float) $searchedLocation['latitude'];
        $searchedLng = (float) $searchedLocation['longitude'];
        $bestDistance = null;

        foreach ($prestataire->getPrestataireInterventionZones() as $zone) {
            if (!$zone->isActive() || null === $zone->getLatitude() || null === $zone->getLongitude()) {
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

    private function matchesLocationText(
        PrestataireProfile $prestataire,
        string $normalizedLocation,
        ?array $searchedLocation = null,
    ): bool {
        $candidates = [
            $prestataire->getCity(),
            $prestataire->getPostalCode(),
        ];

        if ($searchedLocation !== null) {
            $candidates[] = $searchedLocation['city'] ?? null;
            $candidates[] = $searchedLocation['postalCode'] ?? null;
            $candidates[] = $searchedLocation['department'] ?? null;
            $candidates[] = $searchedLocation['region'] ?? null;
        }

        foreach ($prestataire->getPrestataireInterventionZones() as $zone) {
            if (!$zone->isActive()) {
                continue;
            }

            $candidates[] = $zone->getCity();
            $candidates[] = $zone->getPostalCode();
            $candidates[] = $zone->getDepartment();
            $candidates[] = $zone->getRegion();
        }

        foreach ($candidates as $candidate) {
            $normalizedCandidate = $this->normalizeLocationValue($candidate);

            if ('' === $normalizedCandidate) {
                continue;
            }

            if (
                $normalizedCandidate === $normalizedLocation
                || str_contains($normalizedCandidate, $normalizedLocation)
                || str_contains($normalizedLocation, $normalizedCandidate)
            ) {
                return true;
            }
        }

        return false;
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

    private function normalizeLocationValue(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));

        if ('' === $value) {
            return '';
        }

        if (\function_exists('transliterator_transliterate')) {
            $value = transliterator_transliterate('Any-Latin; Latin-ASCII;', $value);
        }

        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
