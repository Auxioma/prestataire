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
    public function connectAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        // On récupère le client 'google' configuré dans notre fichier YAML
        return $clientRegistry
            ->getClient('google')
            // On redirige l'utilisateur vers Google en lui demandant l'accès à son email et son profil public
            ->redirect([
                'email', 'profile'
            ], []);
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