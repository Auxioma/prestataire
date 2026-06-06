<?php

namespace App\Controller;

use App\Repository\PrestataireProfileRepository;
use App\Repository\ServiceCategoryRepository;
use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    // Une seule route flexible qui accepte soit un slug de catégorie/sous-catégorie, soit un slug de service
    #[Route('/trouver-un-pro/{type}/{slug}', name: 'app_search_flow', defaults: ['type' => null, 'slug' => null], methods: ['GET'])]
    public function index(
        ?string $type,
        ?string $slug,
        ServiceCategoryRepository $categoryRepository,
        ServiceRepository $serviceRepository,
        PrestataireProfileRepository $prestataireRepository
    ): Response {

        // Variables d'état pour construire le fil d'ariane et les titres dans Twig
        $currentCategory = null;
        $currentSubCategory = null;
        $currentService = null;
        
        $subCategories = [];
        $services = [];
        $prestataires = [];

        // Étape 1 & 2 : On a cliqué sur une catégorie ou une sous-catégorie
        if ($type === 'categorie' && $slug) {
            $category = $categoryRepository->findOneBy(['slug' => $slug]);
            
            if ($category) {
                if ($category->getParent() === null) {
                    // C'est une catégorie principale -> On récupère ses sous-catégories
                    $currentCategory = $category;
                    $subCategories = $category->getSubCategories();
                } else {
                    // C'vest une sous-catégorie -> On récupère ses services actifs
                    $currentSubCategory = $category;
                    $currentCategory = $category->getParent(); // Pour remonter le fil d'ariane
                    $services = $serviceRepository->findBy(['category' => $category, 'isActive' => true]);
                }
            }
        } 
        // Étape 3 : On a cliqué sur un service précis -> On cherche les prestataires associés
        elseif ($type === 'service' && $slug) {
            $currentService = $serviceRepository->findOneBy(['slug' => $slug]);
            
            if ($currentService) {
                $currentSubCategory = $currentService->getCategory();
                if ($currentSubCategory) {
                    $currentCategory = $currentSubCategory->getParent();
                }

                // Appel au Repository avec notre méthode ManyToMany sécurisée
                $prestataires = $prestataireRepository->findByService($currentService);
            }
        } 
        // Par sécurité ou pour une page d'index globale (ex: /trouver-un-pro)
        else {
            $subCategories = $categoryRepository->findBy(['parent' => null, 'isActive' => true]);
        }

        return $this->render('search/search.html.twig', [
            'currentCategory' => $currentCategory,
            'currentSubCategory' => $currentSubCategory,
            'currentService' => $currentService,
            'subCategories' => $subCategories,
            'services' => $services,
            'prestataires' => $prestataires,
        ]);
    }
}
