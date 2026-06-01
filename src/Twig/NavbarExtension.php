<?php

namespace App\Twig;

use App\Repository\ServiceCategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class NavbarExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private ServiceCategoryRepository $categoryRepository
    ) {}

    public function getGlobals(): array
    {
        return [
            'navbarCategories' => $this->categoryRepository->findWithSubCategories(),
        ];
    }
}
