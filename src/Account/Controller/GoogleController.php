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

namespace App\Account\Controller;

use App\Company\Service\PrestataireRegistrationAdmission;
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
