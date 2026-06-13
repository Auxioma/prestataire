<?php

namespace App\Controller;

use App\Entity\ServiceCategory;
use App\Form\HomepageSearchType;
use App\Repository\PrestataireProfileRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ZoneGeocoder;

class HomepageSearchController extends AbstractController
{
    #[Route('/recherche-prestataires', name: 'app_homepage_search', methods: ['GET'])]
    public function index(
        Request $request,
        PrestataireProfileRepository $prestataireProfileRepository,
        PaginatorInterface $paginator,
        ZoneGeocoder $zoneGeocoder
    ): Response {
        $form = $this->createForm(HomepageSearchType::class);
        $form->handleRequest($request);

        $data = $form->isSubmitted() ? ($form->getData() ?? []) : [];

        $query = trim((string) ($data['query'] ?? ''));
        $location = trim((string) ($data['location'] ?? ''));
        /** @var ServiceCategory|null $subCategory */
        $subCategory = $data['subCategory'] ?? null;

        $searchedLocation = null;

        if ($location !== '') {
            $searchedLocation = $zoneGeocoder->geocode($location, null);
        }


        $criteria = [
            'query' => $query,
            'location' => $location,
            'subCategory' => $subCategory,
            'searchedLocation' => $searchedLocation,
        ];

        $queryBuilder = $prestataireProfileRepository->getHomepageSearchQueryBuilder($criteria);

        $prestataires = $queryBuilder->getQuery()->getResult();

        if ($searchedLocation !== null) {
            $prestataires = array_values(array_filter(
                $prestataires,
                fn ($prestataire) => $this->isPrestataireMatchingSearchedLocation($prestataire, $searchedLocation)
            ));

            foreach ($prestataires as $prestataire) {
                $prestataire->matchedDistanceKm = $this->getClosestMatchingDistanceKm($prestataire, $searchedLocation);
            }

            usort($prestataires, function ($a, $b) {
                return ($a->matchedDistanceKm ?? 999999) <=> ($b->matchedDistanceKm ?? 999999);
            });
        }

        $pagination = $paginator->paginate(
            $prestataires,
            $request->query->getInt('page', 1),
            9
        );

        return $this->render('search/homepage_results.html.twig', [
            'searchForm' => $form->createView(),
            'pagination' => $pagination,
            'query' => $query,
            'location' => $location,
            'subCategory' => $subCategory,
            'criteria' => $criteria,
            'pageTitle' => 'Résultats de recherche',
        ]);
    }

    private function isPrestataireMatchingSearchedLocation($prestataire, ?array $searchedLocation): bool
    {
        if ($searchedLocation === null) {
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

            if ($zone->getLatitude() === null || $zone->getLongitude() === null || $zone->getRadiusKm() === null) {
                continue;
            }

            $distanceKm = $this->distanceKm(
                $searchedLat,
                $searchedLng,
                (float) $zone->getLatitude(),
                (float) $zone->getLongitude()
            );

            if ($distanceKm <= (float) $zone->getRadiusKm()) {
                return true;
            }
        }

        return false;
    }

    private function getClosestMatchingDistanceKm($prestataire, ?array $searchedLocation): ?float
    {
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

            if ($zone->getLatitude() === null || $zone->getLongitude() === null || $zone->getRadiusKm() === null) {
                continue;
            }

            $distanceKm = $this->distanceKm(
                $searchedLat,
                $searchedLng,
                (float) $zone->getLatitude(),
                (float) $zone->getLongitude()
            );

            if ($distanceKm <= (float) $zone->getRadiusKm()) {
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
