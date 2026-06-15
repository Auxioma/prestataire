<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PrestataireDashboardController extends AbstractController
{
    #[Route('/prestataire/espace-pro', name: 'app_prestataire_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isGranted('ROLE_PRESTATAIRE')) {
            throw $this->createAccessDeniedException('Accès réservé aux prestataires.');
        }

        return $this->render('prestataire_dashboard/prestataire_dashboard.html.twig', [
            'user' => $user,
        ]);
    }
}