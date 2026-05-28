<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class GoogleController extends AbstractController
{
    /**
     * Route d'amorçage : C'est le lien sur lequel l'utilisateur va cliquer pour se connecter.
     */
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectAction(Request $request, ClientRegistry $clientRegistry): RedirectResponse
    {
        // On cherche si un rôle est EXPLICITEMENT fourni dans l'URL
        $role = $request->query->get('role');

        if ($role) {
            // C'est une inscription : on stocke le rôle demandé (client ou prestataire)
            $request->getSession()->set('oauth_registration_role', $role);
        } else {
            // C'est une connexion pure : on s'assure que la session est totalement vide de ce paramètre
            $request->getSession()->remove('oauth_registration_role');
        }

        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile'], []);
    }

    /**
     * Route de retour (Callback) : Google renvoie l'utilisateur ici avec son token.
     * Cette méthode reste vide car elle sera interceptée en amont par notre futur Authenticator !
     */
    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request)
    {
        // Intercepté par le Guard Authenticator de Symfony
    }
}
