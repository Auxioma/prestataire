<?php

namespace App\Controller;

use App\Search\PrestataireSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class SearchApiController extends AbstractController
{
    #[Route('/api/search/autocomplete', name: 'app_search_autocomplete', methods: ['GET'])]
    public function autocomplete(
        Request $request,
        PrestataireSearchService $prestataireSearchService,
    ): JsonResponse {
        $query = trim((string) $request->query->get('q', ''));
        $query = mb_substr($query, 0, 100);

        if (mb_strlen($query) < 2) {
            return $this->json([
                'items' => [],
            ]);
        }

        $results = $prestataireSearchService->autocomplete($query, 6);

        $items = [];
        $seen = [];

        foreach ($results as $item) {
            $id = $item['id'] ?? null;
            $slug = $item['slug'] ?? null;

            if (!$id || !$slug || !$this->isValidPrestataireSlug((string) $slug)) {
                continue;
            }

            $key = $id.'|'.$slug;
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            $firstCategory = $item['subCategories'][0]['name'] ?? $item['categories'][0]['name'] ?? null;
            $firstService = $item['services'][0]['title'] ?? $item['services'][0]['service']['name'] ?? null;

            $companyName = trim((string) ($item['companyName'] ?? ''));
            $metier = trim((string) ($item['metier'] ?? ''));
            $city = trim((string) ($item['city'] ?? ''));

            if ($companyName === '' && $metier === '' && $firstCategory === null && $firstService === null) {
                continue;
            }

            $items[] = [
                'id' => $id,
                'slug' => $slug,
                'companyName' => $companyName,
                'metier' => $metier,
                'city' => $city,
                'averageRating' => (float) ($item['averageRating'] ?? 0),
                'reviewsCount' => (int) ($item['reviewsCount'] ?? 0),
                'categoryLabel' => $firstCategory,
                'serviceLabel' => $firstService,
                'url' => '/prestataire/'.$slug,
            ];
        }

        return $this->json([
            'items' => array_slice($items, 0, 6),
        ]);
    }

    private function isValidPrestataireSlug(string $slug): bool
    {
        return 1 === preg_match('/^(?!abonnements$)[a-z0-9-]+$/', $slug);
    }
}
