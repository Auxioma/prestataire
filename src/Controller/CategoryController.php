<?php

namespace App\Controller;

use App\Entity\ServiceCategory;
use App\Repository\ServiceCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity; // <-- IMPORTANT : Pense à ajouter cet import en haut !

class CategoryController extends AbstractController
{
    /**
     * NIVEAU 1 : Liste toutes les grandes catégories
     */
    #[Route('/categories', name: 'app_category_index', methods: ['GET'])]
    public function index(ServiceCategoryRepository $categoryRepository): Response
    {
        return $this->render('category/category.html.twig', [
            'categories' => $categoryRepository->findWithSubCategories(),
        ]);
    }

    /**
     * NIVEAU 2 : Liste les métiers d'une grande catégorie
     * FIX : On utilise #[MapEntity] pour lui dire d'associer le paramètre {slug} de l'URL au champ 'slug' de l'entité
     */
    #[Route('/categories/{slug}', name: 'app_category_show', methods: ['GET'])]
    public function showCategory(
        #[MapEntity(mapping: ['slug' => 'slug'])] ServiceCategory $category
    ): Response {
        if (!$category->isActive()) {
            throw $this->createNotFoundException('Cette catégorie n\'est pas disponible.');
        }

        return $this->render('category/sub_categories.html.twig', [
            'category' => $category,
        ]);
    }

    /**
     * NIVEAU 3 : Liste les prestations précises d'un métier
     */
    #[Route('/categories/{categorySlug}/{subCategorySlug}', name: 'app_subcategory_services', methods: ['GET'])]
    public function showServices(
        string $categorySlug, 
        string $subCategorySlug, 
        ServiceCategoryRepository $categoryRepository
    ): Response {
        
        $subCategory = $categoryRepository->findOneBy([
            'slug' => $subCategorySlug,
            'isActive' => true
        ]);

        if (!$subCategory || !$subCategory->getParent() || $subCategory->getParent()->getSlug() !== $categorySlug) {
            throw $this->createNotFoundException('Ce métier ou cette spécialité n\'existe pas.');
        }

        return $this->render('category/services.html.twig', [
            'category' => $subCategory->getParent(),
            'subCategory' => $subCategory,
        ]);
    }
}