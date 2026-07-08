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

use App\Entity\User;
use App\Enum\FavoriteTypeEnum;
use App\Form\HomepageSearchType;
use App\Repository\FavoriteRepository;
use App\Repository\PrestataireProfileRepository;
use App\Repository\ServiceCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(
        ServiceCategoryRepository $categoryRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
        FavoriteRepository $favoriteRepository,
    ): Response {
        $homepageSearchForm = $this->createForm(HomepageSearchType::class, null, [
            'action' => $this->generateUrl('app_homepage_search'),
            'method' => 'GET',
        ]);

        $categories = $categoryRepository->findPopularForHome(10);
        $providers = $prestataireProfileRepository->findFeaturedForHome(4);

        $homeStats = $prestataireProfileRepository->getHomeStats();
        $homeStats['categoriesCount'] = $categoryRepository->countActiveRootCategories();

        $favoriteProviderIds = [];
        $favoriteBonPlanIds = [];

        $user = $this->getUser();

        if ($user instanceof User && $this->isGranted('ROLE_CLIENT')) {
            $favoriteProviderIds = array_map(
                static fn ($favorite): string => (string) $favorite->getTargetId(),
                $favoriteRepository->findByUserAndType($user, FavoriteTypeEnum::PRESTATAIRE)
            );

            $favoriteBonPlanIds = array_map(
                static fn ($favorite): string => (string) $favorite->getTargetId(),
                $favoriteRepository->findByUserAndType($user, FavoriteTypeEnum::BON_PLAN)
            );
        }

        return $this->render('home/index.html.twig', [
            'homepageSearchForm' => $homepageSearchForm->createView(),
            'categories' => $categories,
            'providers' => $providers,
            'homeStats' => $homeStats,
            'popularCities' => $this->getPopularCities(),
            'favoriteProviderIds' => $favoriteProviderIds,
            'favoriteBonPlanIds' => $favoriteBonPlanIds,
        ]);
    }

    /**
     * Ces villes servent uniquement à alimenter les raccourcis SEO/UX de la page d’accueil.
     * Elles pourront être remplacées plus tard par une table dédiée si tu veux les piloter en back-office.
     */
    private function getPopularCities(): array
    {
        return [
            ['name' => 'Paris', 'count' => '18 400 pros'],
            ['name' => 'Lyon', 'count' => '7 900 pros'],
            ['name' => 'Marseille', 'count' => '6 800 pros'],
            ['name' => 'Toulouse', 'count' => '5 200 pros'],
            ['name' => 'Bordeaux', 'count' => '4 900 pros'],
            ['name' => 'Nantes', 'count' => '4 100 pros'],
            ['name' => 'Lille', 'count' => '3 700 pros'],
            ['name' => 'Nice', 'count' => '3 300 pros'],
            ['name' => 'Rennes', 'count' => '2 900 pros'],
            ['name' => 'Strasbourg', 'count' => '2 600 pros'],
            ['name' => 'Montpellier', 'count' => '2 500 pros'],
            ['name' => 'Le Havre', 'count' => '1 200 pros'],
        ];
    }
}
