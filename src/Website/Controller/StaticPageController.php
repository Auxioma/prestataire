<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 AUXIOMA Web Agency.
 *
 * Projet : TrouveMoi
 *
 * Tous droits réservés.
 *
 * Ce fichier fait partie du projet TrouveMoi,
 * développé par AUXIOMA Web Agency.
 *
 * Toute reproduction, modification, distribution ou utilisation,
 * totale ou partielle, sans autorisation écrite préalable,
 * est strictement interdite.
 */

namespace App\Website\Controller;

use App\Website\Content\FaqContent;
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
     */
    public function show(string $slug): Response
    {
        // Nettoyage simple du nom pour correspondre au fichier twig
        $templateName = str_replace('-', '_', $slug);
        $templatePath = "Website/static/{$templateName}.html.twig";

        // Vérification de l'existence du template pour éviter une erreur 500
        if (!$this->twigExists($templatePath)) {
            throw $this->createNotFoundException("La page demandée n'existe pas.");
        }

        $context = [
            'current_slug' => $slug,
        ];

        if ('aide-faq' === $slug) {
            $context['faqCategories'] = FaqContent::categories();
        }

        return $this->render($templatePath, $context);
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
