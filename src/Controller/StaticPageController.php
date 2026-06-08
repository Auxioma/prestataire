<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StaticPageController extends AbstractController
{
    #[Route('/page/{slug}', name: 'app_static_page', requirements: ['slug' => '[a-z0-9\-]+'])]
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
            'current_slug' => $slug
        ]);
    }

    /**
     * Petite méthode utilitaire pour vérifier si le template twig existe
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