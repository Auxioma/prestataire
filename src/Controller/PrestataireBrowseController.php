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

use App\Repository\PrestataireProfileRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PrestataireBrowseController extends AbstractController
{
    #[Route('/prestataires', name: 'app_prestataire_browse', methods: ['GET'])]
    public function index(
        Request $request,
        PrestataireProfileRepository $profileRepository,
        PaginatorInterface $paginator,
    ): Response {
        // On récupère le paramètre pour savoir quel bouton est actif dans le Twig
        $sortBy = $request->query->get('sort', 'all');

        // On récupère le QueryBuilder de base (SANS le orderBy, KnpPaginator va s'en charger)
        $queryBuilder = $profileRepository->getBrowseQueryBuilder($sortBy);

        // On passe directement la request d'origine
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            9,
            [
                'wrap-queries' => true,
                'defaultSortFieldName' => 'p.createdAt',
                'defaultSortDirection' => 'desc',
            ]
        );

        $pageTitle = ('p.averageRating' === $sortBy)
            ? 'Les prestataires les mieux notés'
            : 'Tous nos prestataires';

        return $this->render('prestataire_browse/prestataire_browse.html.twig', [
            'pagination' => $pagination,
            'current_sort' => $sortBy,
            'page_title' => $pageTitle,
        ]);
    }
}
