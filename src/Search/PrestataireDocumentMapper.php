<?php

namespace App\Search;

use App\Entity\PrestataireProfile;
use App\Entity\PrestataireService;

final class PrestataireDocumentMapper
{
    public function map(PrestataireProfile $prestataire): array
    {
        $services = [];
        $categories = [];
        $subCategories = [];
        $zones = [];
        $searchParts = [
            $prestataire->getCompanyName(),
            $prestataire->getMetier(),
            $prestataire->getShortDescription(),
            $prestataire->getDescription(),
            $prestataire->getLongDescription(),
            $prestataire->getCity(),
            $prestataire->getPostalCode(),
        ];

        foreach ($prestataire->getPrestataireServices() as $prestataireService) {
            if (!$prestataireService instanceof PrestataireService || !$prestataireService->isActive()) {
                continue;
            }

            $service = $prestataireService->getService();
            if (null === $service || !$service->isActive()) {
                continue;
            }

            $category = $service->getCategory();
            $parentCategory = $category?->getParent();

            $services[] = [
                'id' => $prestataireService->getId(),
                'slug' => $prestataireService->getSlug(),
                'title' => $prestataireService->getDisplayTitle(),
                'shortDescription' => $prestataireService->getShortDescription(),
                'description' => $prestataireService->getDescription(),
                'pricingType' => $prestataireService->getPricingType(),
                'priceFrom' => null !== $prestataireService->getPriceFrom() ? (float) $prestataireService->getPriceFrom() : null,
                'priceTo' => null !== $prestataireService->getPriceTo() ? (float) $prestataireService->getPriceTo() : null,
                'priceUnit' => $prestataireService->getPriceUnit(),
                'service' => [
                    'id' => $service->getId(),
                    'name' => $service->getName(),
                    'slug' => $service->getSlug(),
                    'description' => $service->getDescription(),
                ],
            ];

            $searchParts[] = $prestataireService->getDisplayTitle();
            $searchParts[] = $prestataireService->getShortDescription();
            $searchParts[] = $prestataireService->getDescription();
            $searchParts[] = $service->getName();
            $searchParts[] = $service->getDescription();

            if (null !== $category && $category->isActive()) {
                $categoryItem = [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'slug' => $category->getSlug(),
                    'description' => $category->getDescription(),
                ];

                if (null !== $parentCategory && $parentCategory->isActive()) {
                    $subCategories[$category->getId()] = $categoryItem;

                    $categories[$parentCategory->getId()] = [
                        'id' => $parentCategory->getId(),
                        'name' => $parentCategory->getName(),
                        'slug' => $parentCategory->getSlug(),
                        'description' => $parentCategory->getDescription(),
                    ];

                    $searchParts[] = $category->getName();
                    $searchParts[] = $category->getDescription();
                    $searchParts[] = $parentCategory->getName();
                    $searchParts[] = $parentCategory->getDescription();
                } else {
                    $categories[$category->getId()] = $categoryItem;

                    $searchParts[] = $category->getName();
                    $searchParts[] = $category->getDescription();
                }
            }
        }

        foreach ($prestataire->getPrestataireInterventionZones() as $zone) {
            if (!$zone->isActive()) {
                continue;
            }

            $zones[] = [
                'city' => $zone->getCity(),
                'postalCode' => $zone->getPostalCode(),
                'department' => $zone->getDepartment(),
                'region' => $zone->getRegion(),
                'radiusKm' => $zone->getRadiusKm(),
                'isMainZone' => $zone->isMainZone(),
            ];

            $searchParts[] = $zone->getCity();
            $searchParts[] = $zone->getPostalCode();
            $searchParts[] = $zone->getDepartment();
            $searchParts[] = $zone->getRegion();
        }

        $searchText = implode(' ', array_filter(array_map(
            static fn (mixed $value): ?string => is_string($value) && '' !== trim($value) ? trim($value) : null,
            $searchParts
        )));

        return [
            'id' => $prestataire->getId(),
            'slug' => $prestataire->getSlug(),
            'companyName' => $prestataire->getCompanyName(),
            'metier' => $prestataire->getMetier(),
            'shortDescription' => $prestataire->getShortDescription(),
            'description' => $prestataire->getDescription(),
            'longDescription' => $prestataire->getLongDescription(),
            'city' => $prestataire->getCity(),
            'postalCode' => $prestataire->getPostalCode(),
            'averageRating' => (float) ($prestataire->getAverageRating() ?? 0),
            'reviewsCount' => $prestataire->getReviewsCount() ?? 0,
            'profileStatus' => $prestataire->getProfileStatus()?->value,
            'verificationStatus' => $prestataire->getVerificationStatus()?->value,
            'isFeatured' => $prestataire->isFeatured(),
            'categories' => array_values($categories),
            'subCategories' => array_values($subCategories),
            'services' => $services,
            'zones' => $zones,
            'searchText' => $searchText,
        ];
    }
}