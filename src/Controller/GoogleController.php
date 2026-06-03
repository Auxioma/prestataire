<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class GoogleController extends AbstractController
{

    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectAction(Request $request, ClientRegistry $clientRegistry): RedirectResponse
    {
        $role = $request->query->get('role');

        if ($role) {
            $request->getSession()->set('oauth_registration_role', $role);
        } else {
            $request->getSession()->remove('oauth_registration_role');
        }

        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile'], []);
    }


    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request)
    {
        // Intercepté par le Guard Authenticator de Symfony
    }
}
