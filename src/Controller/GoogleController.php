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

use App\Service\PrestataireRegistrationAdmission;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Gère les actions liées à google.
 */
class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start')]
    /**
     * Traite l’action "connectAction" du contrôleur Google.
     */
    public function connectAction(Request $request, ClientRegistry $clientRegistry, PrestataireRegistrationAdmission $admission): RedirectResponse
    {
        $role = $request->query->get('role');

        if ('prestataire' === $role && null === $admission->getApprovedCompany($request->getSession())) {
            return $this->redirectToRoute('app_register_prestataire_siret');
        }

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
    /**
     * Traite l’action "connectCheckAction" du contrôleur Google.
     */
    public function connectCheckAction(Request $request): void
    {
        // Intercepté par le Guard Authenticator de Symfony
    }
}
