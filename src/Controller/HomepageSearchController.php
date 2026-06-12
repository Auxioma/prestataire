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

class HomepageSearchController extends AbstractController
{
    #[Route('/recherche-prestataires', name: 'app_homepage_search', methods: ['GET'])]
    public function index(
        Request $request,
        PrestataireProfileRepository $prestataireProfileRepository,
        PaginatorInterface $paginator
    ): Response {
        $form = $this->createForm(HomepageSearchType::class);
        $form->handleRequest($request);

        $data = $form->isSubmitted() ? ($form->getData() ?? []) : [];

        $query = trim((string) ($data['query'] ?? ''));
        $location = trim((string) ($data['location'] ?? ''));

        /** @var ServiceCategory|null $subCategory */
        $subCategory = $data['subCategory'] ?? null;

        $criteria = [
            'query' => $query,
            'location' => $location,
            'subCategory' => $subCategory,
        ];

        $queryBuilder = $prestataireProfileRepository->getHomepageSearchQueryBuilder($criteria);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            9,
            [
                'wrap-queries' => true,
                'defaultSortFieldName' => 'p.averageRating',
                'defaultSortDirection' => 'desc',
            ]
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
}