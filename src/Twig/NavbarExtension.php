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

namespace App\Twig;

use App\Repository\ServiceCategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class NavbarExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private ServiceCategoryRepository $categoryRepository,
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'navbarCategories' => $this->categoryRepository->findWithSubCategories(),
        ];
    }
}
