<?php

namespace App\Controller;

use App\Repository\PrestataireServiceRepository;
use App\Repository\PrestataireProfileRepository;
use App\Repository\ServiceCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(
        ServiceCategoryRepository $categoryRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
        PrestataireServiceRepository $prestataireServiceRepository
    ): Response {
        $categories = $categoryRepository->findBy([
            'isActive' => true,
            'parent' => null,
        ], [
            'position' => 'ASC',
        ]);

        return $this->render('home/index.html.twig', [
            'categories' => $categories,
            'providers' => $prestataireProfileRepository->findBy([], ['averageRating' => 'DESC'], 4),
            'bonsPlans' => $prestataireServiceRepository->findLatestBonsPlansForHome(4),
        ]);
    }
}