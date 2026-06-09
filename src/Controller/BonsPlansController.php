<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BonsPlansController extends AbstractController
{
    #[Route('/bons-plans', name: 'app_bons_plans')]
    public function index(): Response
    {
        // Simulation de tes données de bons plans
        $bonsPlans = [
            ['title' => 'Plomberie Express', 'desc' => 'Débouchage canalisation', 'discount' => '20%', 'time' => '3j 12h', 'old_price' => 90, 'price' => 72, 'city' => 'Lyon (69)', 'rating' => 4.8, 'reviews' => 256, 'icon' => 'fa-droplet'],
            ['title' => 'Clean & Shine', 'desc' => 'Ménage complet 3h', 'discount' => '15%', 'time' => '5j 08h', 'old_price' => 120, 'price' => 102, 'city' => 'Toulouse (31)', 'rating' => 4.9, 'reviews' => 178, 'icon' => 'fa-broom'],
            // ... ajoute tes autres éléments ici
        ];

        return $this->render('bons_plans/bon_plans.html.twig', [
            'bons_plans' => $bonsPlans,
        ]);
    }
}