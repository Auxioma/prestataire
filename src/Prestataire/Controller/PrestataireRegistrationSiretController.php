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

namespace App\Prestataire\Controller;

use App\Company\Service\PrestataireRegistrationAdmission;
use App\Prestataire\Form\PrestataireSiretRegistrationType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PrestataireRegistrationSiretController extends AbstractController
{
    #[Route('/register/prestataire/siret', name: 'app_register_prestataire_siret', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, PrestataireRegistrationAdmission $admission, LoggerInterface $logger): Response
    {
        $session = $request->getSession();
        $session->remove(PrestataireRegistrationAdmission::SESSION_KEY);
        $session->remove('prestataire_registration.step_one');
        $session->remove('prestataire_registration.step_two');
        $form = $this->createForm(PrestataireSiretRegistrationType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $admission->verify($form->get('siret')->getData(), $session);

                return $this->redirectToRoute('app_register', ['role' => 'prestataire', 'step' => 1]);
            } catch (\App\Prestataire\Exception\RegistrationAdmissionException $exception) {
                $this->addFlash('warning', $exception->getMessage());
            } catch (\Throwable $exception) {
                $logger->error('Vérification SIRET indisponible pendant l’inscription.', ['error_type' => $exception::class]);
                $this->addFlash('warning', 'La vérification SIRET est temporairement indisponible. Veuillez réessayer dans quelques instants.');
            }
        }

        return $this->render('Prestataire/registration/prestataire_siret.html.twig', ['siretForm' => $form]);
    }
}
