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

use App\Entity\PrestataireService;
use App\Entity\User;
use App\Form\PrestataireServicePrestationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PrestataireServicePrestationController extends AbstractController
{
    #[Route('/prestataire/service/{id}/prestation', name: 'app_prestataire_service_prestation_edit')]
    public function edit(
        Request $request,
        PrestataireService $ps,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        if (
            !$user instanceof User
            || !$user->getPrestataireProfile()
            || $ps->getPrestataire() !== $user->getPrestataireProfile()
        ) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(PrestataireServicePrestationType::class, $ps);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Prestation détaillée enregistrée.');

            return $this->redirectToRoute('app_prestataire_settings', [
                '_fragment' => 'services-panel',
            ]);
        }

        return $this->render('prestataire/edit_prestation.html.twig', [
            'form' => $form->createView(),
            'ps' => $ps,
            'prestation' => $ps,
            'zones' => $ps->getPrestataire()?->getPrestataireInterventionZones() ?? [],
        ]);
    }
}