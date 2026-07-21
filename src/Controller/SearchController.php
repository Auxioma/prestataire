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
use App\Repository\PrestataireProfileRepository;
use App\Repository\ServiceCategoryRepository;
use App\Repository\ServiceRepository;
use App\Service\ZoneGeocoder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Gère les actions liées à search.
 */
class SearchController extends AbstractController
{
    // Une seule route flexible qui accepte soit un slug de catégorie/sous-catégorie, soit un slug de service
    #[Route('/trouver-un-pro/{type}/{slug}', name: 'app_search_flow', defaults: ['type' => null, 'slug' => null], methods: ['GET'])]
    /**
     * Affiche la page principale de ce contrôleur.
     *
     * @return Response
     */
    public function index(
        Request $request,
        ?string $type,
        ?string $slug,
        ServiceCategoryRepository $categoryRepository,
        ServiceRepository $serviceRepository,
        PrestataireProfileRepository $prestataireRepository,
        ZoneGeocoder $zoneGeocoder,
    ): Response {
        // Variables d'état pour construire le fil d'ariane et les titres dans Twig
        $currentCategory = null;
        $currentSubCategory = null;
        $currentService = null;

        $subCategories = [];
        $services = [];
        $prestataires = [];
        $directPrestataires = [];
        $fallbackPrestataires = [];
        $location = trim((string) $request->query->get('location', ''));
        $radiusKm = max(5, min(100, $request->query->getInt('radiusKm', 25)));
        $sort = $this->normalizeSort((string) $request->query->get('sort', 'proximity'));
        $searchedLocation = $location !== '' ? $zoneGeocoder->geocode($location, null) : null;

        // Étape 1 & 2 : On a cliqué sur une catégorie ou une sous-catégorie
        if ('categorie' === $type && $slug) {
            $category = $categoryRepository->findOneBy(['slug' => $slug]);

            if ($category) {
                if (null === $category->getParent()) {
                    // C'est une catégorie principale -> On récupère ses sous-catégories
                    $currentCategory = $category;
                    $subCategories = $category->getSubCategories();
                } else {
                    // C'vest une sous-catégorie -> On récupère ses services actifs
                    $currentSubCategory = $category;
                    $currentCategory = $category->getParent(); // Pour remonter le fil d'ariane
                    $services = $serviceRepository->findBy(['category' => $category, 'isActive' => true]);
                }
            }
        }
        // Étape 3 : On a cliqué sur un service précis -> On cherche les prestataires associés
        elseif ('service' === $type && $slug) {
            $currentService = $serviceRepository->findOneBy(['slug' => $slug]);

            if ($currentService) {
                $currentSubCategory = $currentService->getCategory();
                if ($currentSubCategory) {
                    $currentCategory = $currentSubCategory->getParent();
                }

                // Appel au Repository avec notre méthode ManyToMany sécurisée
                $prestataires = $prestataireRepository->findByService($currentService);

                foreach ($prestataires as $prestataire) {
                    if (!$prestataire instanceof PrestataireProfile) {
                        continue;
                    }

                    if ($searchedLocation === null) {
                        $directPrestataires[] = $prestataire;
                        continue;
                    }

                    $matchType = $this->getPrestataireMatchType($prestataire, $searchedLocation, $location, $radiusKm);

                    if (null === $matchType) {
                        continue;
                    }

                    $prestataire->matchedDistanceKm = $this->getClosestReachableDistanceKm(
                        $prestataire,
                        $searchedLocation,
                        $location,
                        $radiusKm,
                    );

                    if ('direct' === $matchType) {
                        $directPrestataires[] = $prestataire;
                        continue;
                    }

                    $fallbackPrestataires[] = $prestataire;
                }

                if ($searchedLocation !== null) {
                }

                $this->sortPrestataires($directPrestataires, $sort);
                $this->sortPrestataires($fallbackPrestataires, $sort);
            }
        }
        // Par sécurité ou pour une page d'index globale (ex: /trouver-un-pro)
        else {
            $subCategories = $categoryRepository->findBy(['parent' => null, 'isActive' => true]);
        }

        return $this->render('search/search.html.twig', [
            'currentCategory' => $currentCategory,
            'currentSubCategory' => $currentSubCategory,
            'currentService' => $currentService,
            'subCategories' => $subCategories,
            'services' => $services,
            'prestataires' => $prestataires,
            'directPrestataires' => $directPrestataires,
            'fallbackPrestataires' => $fallbackPrestataires,
            'location' => $location,
            'radiusKm' => $radiusKm,
            'sort' => $sort,
        ]);
    }

    /**
     * @param list<PrestataireProfile> $prestataires
     */
    private function sortPrestataires(array &$prestataires, string $sort): void
    {
        usort($prestataires, function (PrestataireProfile $left, PrestataireProfile $right) use ($sort): int {
            return match ($sort) {
                'alphabetical' => $this->compareAlphabetical($left, $right),
                'rating' => $this->compareRating($left, $right),
                'reviews' => $this->compareReviews($left, $right),
                default => $this->compareProximity($left, $right),
            };
        });
    }

    private function compareAlphabetical(PrestataireProfile $left, PrestataireProfile $right): int
    {
        return $this->normalizeLocationValue($left->getCompanyName()) <=> $this->normalizeLocationValue($right->getCompanyName());
    }

    private function compareRating(PrestataireProfile $left, PrestataireProfile $right): int
    {
        $ratingComparison = ((float) ($right->getAverageRating() ?? 0)) <=> ((float) ($left->getAverageRating() ?? 0));
        if (0 !== $ratingComparison) {
            return $ratingComparison;
        }

        $reviewsComparison = ($right->getReviewsCount() ?? 0) <=> ($left->getReviewsCount() ?? 0);
        if (0 !== $reviewsComparison) {
            return $reviewsComparison;
        }

        return $this->compareAlphabetical($left, $right);
    }

    private function compareReviews(PrestataireProfile $left, PrestataireProfile $right): int
    {
        $reviewsComparison = ($right->getReviewsCount() ?? 0) <=> ($left->getReviewsCount() ?? 0);
        if (0 !== $reviewsComparison) {
            return $reviewsComparison;
        }

        $ratingComparison = ((float) ($right->getAverageRating() ?? 0)) <=> ((float) ($left->getAverageRating() ?? 0));
        if (0 !== $ratingComparison) {
            return $ratingComparison;
        }

        return $this->compareAlphabetical($left, $right);
    }

    private function compareProximity(PrestataireProfile $left, PrestataireProfile $right): int
    {
        $distanceComparison = (($left->matchedDistanceKm ?? 999999) <=> ($right->matchedDistanceKm ?? 999999));
        if (0 !== $distanceComparison) {
            return $distanceComparison;
        }

        return $this->compareAlphabetical($left, $right);
    }

    private function normalizeSort(string $sort): string
    {
        return \in_array($sort, ['proximity', 'alphabetical', 'rating', 'reviews'], true) ? $sort : 'proximity';
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
