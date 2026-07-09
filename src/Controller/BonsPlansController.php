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

use App\Repository\PrestataireServiceRepository;
use App\Repository\ServiceCategoryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Gère les actions liées à bons plans.
 */
class BonsPlansController extends AbstractController
{
    #[Route('/bons-plans', name: 'app_bons_plans', methods: ['GET'])]
    /**
     * Affiche la page principale de ce contrôleur.
     *
     * @return Response
     */
    public function index(
        Request $request,
        PrestataireServiceRepository $prestataireServiceRepository,
        ServiceCategoryRepository $serviceCategoryRepository,
        PaginatorInterface $paginator,
    ): Response {
        $selectedCategorySlug = $request->query->get('category');
        $selectedSubCategorySlug = $request->query->get('subCategory');

        $categories = $serviceCategoryRepository->findBy(
            ['parent' => null, 'isActive' => true],
            ['position' => 'ASC']
        );

        $subCategories = [];
        if ($selectedCategorySlug) {
            $selectedCategory = $serviceCategoryRepository->findOneBy([
                'slug' => $selectedCategorySlug,
                'isActive' => true,
            ]);

            if ($selectedCategory) {
                $subCategories = $serviceCategoryRepository->findBy(
                    ['parent' => $selectedCategory, 'isActive' => true],
                    ['position' => 'ASC']
                );
            }
        }

        $queryBuilder = $prestataireServiceRepository->getBonsPlansQueryBuilder(
            $selectedCategorySlug ?: null,
            $selectedSubCategorySlug ?: null
        );

        $bonsPlans = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            9,
            [
                'wrap-queries' => true,
            ]
        );

        return $this->render('bons_plans/bons_plans.html.twig', [
            'bonsPlans' => $bonsPlans,
            'categories' => $categories,
            'subCategories' => $subCategories,
            'selectedCategorySlug' => $selectedCategorySlug,
            'selectedSubCategorySlug' => $selectedSubCategorySlug,
        ]);
    }
}
