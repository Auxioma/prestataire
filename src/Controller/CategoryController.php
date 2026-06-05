<?php

namespace App\Controller;

use App\Repository\ServiceCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CategoryController extends AbstractController
{
    #[Route('/categories', name: 'app_category_index', methods: ['GET'])]
    public function index(ServiceCategoryRepository $categoryRepository): Response
    {
        return $this->render('category/category.html.twig', [
            'categories' => $categoryRepository->findWithSubCategories(),
        ]);
    }
}