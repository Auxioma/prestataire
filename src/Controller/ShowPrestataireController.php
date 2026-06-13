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

use App\Entity\PrestataireProfile;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowPrestataireController extends AbstractController
{
    #[Route('/prestataire/{slug}', name: 'app_prestataire_show', methods: ['GET'])]
    public function __invoke(
        #[MapEntity(mapping: ['slug' => 'slug'])] PrestataireProfile $prestataire,
    ): Response {
        // Sécurité : si le pro n'a pas encore configuré son entreprise, on renvoie une 404
        if (!$prestataire->getCompanyName()) {
            throw $this->createNotFoundException('Ce profil professionnel n\'est pas encore actif.');
        }

        return $this->render('show_prestataire/show.html.twig', [
            'prestataire' => $prestataire,
        ]);
    }
}
