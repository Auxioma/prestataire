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

use App\Entity\ServiceCategory;
use App\Form\PrestataireBrowseFilterType;
use App\Repository\PrestataireProfileRepository;
use App\Repository\ServiceCategoryRepository;
use App\Search\PrestataireSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Gère les actions liées à prestataire browse.
 */
class PrestataireBrowseController extends AbstractController
{
    #[Route('/prestataires', name: 'app_prestataire_browse', methods: ['GET'])]
    /**
     * Affiche la page principale de ce contrôleur.
     *
     * @return Response
     */
    public function index(
        Request $request,
        PrestataireProfileRepository $profileRepository,
        ServiceCategoryRepository $categoryRepository,
        PrestataireSearchService $prestataireSearchService,
    ): Response {
        $selectedCategory = null;
        $categoryId = $request->query->get('category');
        if (null !== $categoryId && '' !== (string) $categoryId) {
            $candidate = $categoryRepository->find($categoryId);
            if ($candidate instanceof ServiceCategory && null === $candidate->getParent() && $candidate->isActive()) {
                $selectedCategory = $candidate;
            }
        }

        $form = $this->createForm(PrestataireBrowseFilterType::class, [
            'query' => '',
            'category' => $selectedCategory,
            'subCategory' => null,
            'sort' => 'relevance',
        ], [
            'method' => 'GET',
            'selected_category' => $selectedCategory,
        ]);

        $form->handleRequest($request);

        $data = ($form->isSubmitted() && $form->isValid())
            ? ($form->getData() ?? [])
            : [];

        $query = trim((string) ($data['query'] ?? ''));
        /** @var ServiceCategory|null $category */
        $category = $data['category'] ?? $selectedCategory;
        /** @var ServiceCategory|null $subCategory */
        $subCategory = $data['subCategory'] ?? null;
        $sort = (string) ($data['sort'] ?? 'relevance');

        if ($subCategory instanceof ServiceCategory) {
            if (!$subCategory->isActive() || null === $subCategory->getParent()) {
                $subCategory = null;
            } elseif (null === $category || $subCategory->getParent()?->getId() !== $category->getId()) {
                $category = $subCategory->getParent();
            }
        }

        $page = max(1, $request->query->getInt('page', 1));
        $perPage = 9;
        $from = ($page - 1) * $perPage;

        $searchResponse = $prestataireSearchService->browseSearch(
            $query !== '' ? $query : null,
            $category?->getSlug(),
            $subCategory?->getSlug(),
            $sort,
            $perPage,
            $from,
        );

        $totalResults = (int) ($searchResponse['total'] ?? 0);
        $totalPages = max(1, (int) ceil($totalResults / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
            $from = ($page - 1) * $perPage;
            $searchResponse = $prestataireSearchService->browseSearch(
                $query !== '' ? $query : null,
                $category?->getSlug(),
                $subCategory?->getSlug(),
                $sort,
                $perPage,
                $from,
            );
        }

        $hits = $searchResponse['hits'] ?? [];
        $hitIds = array_values(array_filter(array_map(
            static fn (array $hit): ?int => isset($hit['id']) ? (int) $hit['id'] : null,
            $hits
        )));

        $profiles = [];

        if ($hitIds !== []) {
            $fetchedProfiles = $profileRepository->createQueryBuilder('p')
                ->leftJoin('p.account', 'a')->addSelect('a')
                ->andWhere('p.id IN (:ids)')
                ->setParameter('ids', $hitIds)
                ->getQuery()
                ->getResult();

            $profilesById = [];
            foreach ($fetchedProfiles as $profile) {
                $profilesById[(int) $profile->getId()] = $profile;
            }

            foreach ($hitIds as $id) {
                if (isset($profilesById[$id])) {
                    $profiles[] = $profilesById[$id];
                }
            }
        }

        $pageTitle = $query !== ''
            ? 'Résultats pour "'.$query.'"'
            : 'Tous nos prestataires';

        return $this->render('prestataire_browse/prestataire_browse.html.twig', [
            'browseForm' => $form->createView(),
            'profiles' => $profiles,
            'query' => $query,
            'selectedCategory' => $category,
            'selectedSubCategory' => $subCategory,
            'current_sort' => $sort,
            'page_title' => $pageTitle,
            'totalResults' => $totalResults,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }
}
