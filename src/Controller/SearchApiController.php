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

        if (mb_strlen($query) < 2) {
            return $this->json([
                'items' => [],
            ]);
        }

        $results = $prestataireSearchService->autocomplete($query, 6);

        $items = array_map(static function (array $item): array {
            $firstCategory = $item['subCategories'][0]['name'] ?? $item['categories'][0]['name'] ?? null;
            $firstService = $item['services'][0]['title'] ?? $item['services'][0]['service']['name'] ?? null;

            return [
                'id' => $item['id'] ?? null,
                'slug' => $item['slug'] ?? null,
                'companyName' => $item['companyName'] ?? '',
                'metier' => $item['metier'] ?? '',
                'city' => $item['city'] ?? '',
                'averageRating' => $item['averageRating'] ?? 0,
                'reviewsCount' => $item['reviewsCount'] ?? 0,
                'categoryLabel' => $firstCategory,
                'serviceLabel' => $firstService,
                'url' => isset($item['slug']) ? '/prestataire/'.$item['slug'] : null,
            ];
        }, $results);

        return $this->json([
            'items' => $items,
        ]);
    }
}