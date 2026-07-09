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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Gère les actions liées à static page.
 */
class StaticPageController extends AbstractController
{
    #[Route('/page/{slug}', name: 'app_static_page', requirements: ['slug' => '[a-z0-9\-]+'])]
    /**
     * Affiche le détail de la ressource demandée.
     *
     * @return Response
     */
    public function show(string $slug): Response
    {
        // Nettoyage simple du nom pour correspondre au fichier twig
        $templateName = str_replace('-', '_', $slug);
        $templatePath = "static/{$templateName}.html.twig";

        // Vérification de l'existence du template pour éviter une erreur 500
        if (!$this->twigExists($templatePath)) {
            throw $this->createNotFoundException("La page demandée n'existe pas.");
        }

        return $this->render($templatePath, [
            'current_slug' => $slug,
        ]);
    }

    /**
     * Petite méthode utilitaire pour vérifier si le template twig existe.
     */
    private function twigExists(string $name): bool
    {
        try {
            $this->container->get('twig')->getLoader()->exists($name);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
