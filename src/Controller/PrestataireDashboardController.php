<?php

namespace App\Controller;

use App\Repository\PrestataireProfileRepository;
use App\Repository\PrestataireServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PrestataireDashboardController extends AbstractController
{
    #[Route('/prestataire/espace-pro', name: 'app_prestataire_dashboard', methods: ['GET'])]
    public function index(
        PrestataireProfileRepository $prestataireProfileRepository,
        PrestataireServiceRepository $prestataireServiceRepository
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isGranted('ROLE_PRESTATAIRE')) {
            throw $this->createAccessDeniedException('Accès réservé aux prestataires.');
        }

        $prestataireProfile = $prestataireProfileRepository->findOneBy([
            'account' => $user,
        ]);

        if (!$prestataireProfile) {
            throw $this->createAccessDeniedException('Profil prestataire introuvable.');
        }

        $prestations = $prestataireServiceRepository->findBy(
            ['prestataire' => $prestataireProfile],
            ['updatedAt' => 'DESC', 'createdAt' => 'DESC']
        );

        return $this->render('prestataire_dashboard/prestataire_dashboard.html.twig', [
            'user' => $user,
            'prestataireProfile' => $prestataireProfile,
            'prestations' => $prestations,
        ]);
    }
}