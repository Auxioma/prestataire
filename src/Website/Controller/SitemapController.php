<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 AUXIOMA Web Agency.
 *
 * Projet : TrouveMoi
 *
 * Tous droits réservés.
 *
 * Ce fichier fait partie du projet TrouveMoi,
 * développé par AUXIOMA Web Agency.
 *
 * Toute reproduction, modification, distribution ou utilisation,
 * totale ou partielle, sans autorisation écrite préalable,
 * est strictement interdite.
 */

namespace App\Website\Controller;

use App\Catalog\Repository\ServiceCategoryRepository;
use App\Prestataire\Repository\PrestataireProfileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SitemapController extends AbstractController
{
    private const STATIC_PAGE_SLUGS = [
        'about',
        'aide-faq',
        'avantages',
        'cgu',
        'comment-ca-marche',
        'demander-un-devis',
        'espace-prestataire',
        'help',
        'inscription-prestataire',
        'legal',
        'privacy',
        'ressources',
    ];

    #[Route('/sitemap.xml', name: 'app_sitemap', methods: ['GET'])]
    public function sitemap(
        UrlGeneratorInterface $urlGenerator,
        ServiceCategoryRepository $categoryRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
    ): Response {
        $urls = [];

        $addUrl = static function (
            string $route,
            array $parameters = [],
            ?\DateTimeInterface $lastModifiedAt = null,
            array $images = [],
        ) use (&$urls, $urlGenerator): void {
            $url = [
                'location' => $urlGenerator->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL),
            ];

            if (null !== $lastModifiedAt) {
                $url['lastModifiedAt'] = $lastModifiedAt->format('Y-m-d');
            }

            $images = array_values(array_unique(array_filter($images)));

            if ([] !== $images) {
                $url['images'] = $images;
            }

            $urls[] = $url;
        };

        $categories = $categoryRepository->findIndexableTreeForSitemap();
        $categoryImages = [];

        foreach ($categories as $category) {
            $categoryImages[] = self::uploadedImagePath('uploads/service-categories', $category->getImage());
        }

        $addUrl('app_home');
        $addUrl('app_category_index', images: $categoryImages);
        $addUrl('app_prestataire_browse');
        $addUrl('app_bons_plans');

        foreach (self::STATIC_PAGE_SLUGS as $slug) {
            $addUrl('app_static_page', ['slug' => $slug]);
        }

        foreach (ResourceController::slugs() as $slug) {
            $addUrl('app_resource_show', ['slug' => $slug]);
        }

        foreach ($categories as $category) {
            $subCategoryImages = [];

            foreach ($category->getSubCategories() as $subCategory) {
                if ($subCategory->isActive()) {
                    $subCategoryImages[] = self::uploadedImagePath(
                        'uploads/service-categories',
                        $subCategory->getImage(),
                    );
                }
            }

            $addUrl(
                'app_category_show',
                ['slug' => $category->getSlug()],
                $category->getUpdatedAt() ?? $category->getCreatedAt(),
                $subCategoryImages,
            );

            foreach ($category->getSubCategories() as $subCategory) {
                $serviceImages = [];

                foreach ($subCategory->getServices() as $service) {
                    if ($service->isActive()) {
                        $serviceImages[] = self::uploadedImagePath('uploads/services', $service->getImage());
                    }
                }

                $addUrl(
                    'app_subcategory_services',
                    [
                        'categorySlug' => $category->getSlug(),
                        'subCategorySlug' => $subCategory->getSlug(),
                    ],
                    $subCategory->getUpdatedAt() ?? $subCategory->getCreatedAt(),
                    $serviceImages,
                );
            }
        }

        foreach ($prestataireProfileRepository->findIndexableForSitemap() as $prestataireProfile) {
            $prestataireImages = [
                self::uploadedImagePath('uploads/covers', $prestataireProfile->getCoverImage()),
                self::uploadedImagePath('uploads/logos', $prestataireProfile->getLogo()),
            ];

            $addUrl(
                'app_prestataire_show',
                ['slug' => $prestataireProfile->getSlug()],
                $prestataireProfile->getUpdatedAt() ?? $prestataireProfile->getCreatedAt(),
                $prestataireImages,
            );
        }

        $response = $this->render('Website/sitemap.xml.twig', [
            'urls' => $urls,
        ]);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);

        return $response;
    }

    private static function uploadedImagePath(string $directory, ?string $filename): ?string
    {
        $filename = mb_trim((string) $filename);

        if ('' === $filename || basename($filename) !== $filename) {
            return null;
        }

        return trim($directory, '/').'/'.$filename;
    }

    #[Route('/robots.txt', name: 'app_robots', methods: ['GET'])]
    public function robots(UrlGeneratorInterface $urlGenerator): Response
    {
        $content = \sprintf(
            "User-agent: *\nAllow: /\n\nSitemap: %s\n",
            $urlGenerator->generate('app_sitemap', [], UrlGeneratorInterface::ABSOLUTE_URL),
        );

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);

        return $response;
    }
}
