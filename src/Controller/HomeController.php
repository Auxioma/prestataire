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

use App\Form\HomepageSearchType;
use App\Repository\PrestataireProfileRepository;
use App\Repository\PrestataireServiceRepository;
use App\Repository\ServiceCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Enum\FavoriteTypeEnum;
use App\Repository\FavoriteRepository;

/**
 * Gère les actions liées à home.
 */
class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    /**
     * Affiche la page principale de ce contrôleur.
     *
     * @return Response
     */
    public function index(
        ServiceCategoryRepository $categoryRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
        PrestataireServiceRepository $prestataireServiceRepository,
        FavoriteRepository $favoriteRepository,
    ): Response {
        $homepageSearchForm = $this->createForm(HomepageSearchType::class, null, [
            'action' => $this->generateUrl('app_homepage_search'),
            'method' => 'GET',
        ]);

        $categories = $categoryRepository->findBy([
            'isActive' => true,
            'parent' => null,
        ], [
            'position' => 'ASC',
        ]);

        $favoriteProviderIds = [];
        $favoriteBonPlanIds = [];

        $user = $this->getUser();

        if ($user instanceof User && $this->isGranted('ROLE_CLIENT')) {
            $favoriteProviderIds = array_map(
                static fn($favorite) => (string) $favorite->getTargetId(),
                $favoriteRepository->findByUserAndType($user, FavoriteTypeEnum::PRESTATAIRE)
            );
        }

        if ($user instanceof User && $this->isGranted('ROLE_CLIENT')) {
            $favoriteProviderIds = array_map(
                static fn($favorite) => (string) $favorite->getTargetId(),
                $favoriteRepository->findByUserAndType($user, FavoriteTypeEnum::PRESTATAIRE)
            );

            $favoriteBonPlanIds = array_map(
                static fn($favorite) => (string) $favorite->getTargetId(),
                $favoriteRepository->findByUserAndType($user, FavoriteTypeEnum::BON_PLAN)
            );
        }



        return $this->render('home/index.html.twig', [
            'homepageSearchForm' => $homepageSearchForm->createView(),
            'categories' => $categories,
            'providers' => $prestataireProfileRepository->findBy([], ['averageRating' => 'DESC'], 4),
            'bonsPlans' => $prestataireServiceRepository->findLatestBonsPlansForHome(4),
            'favoriteProviderIds' => $favoriteProviderIds,
            'favoriteBonPlanIds' => $favoriteBonPlanIds,
        ]);
    }
}
