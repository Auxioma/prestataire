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

use App\Account\Service\AuthenticatedUserProvider;
use App\Prestataire\Entity\PrestataireInterventionZone;
use App\Prestataire\Form\PrestataireInterventionZoneType;
use App\Prestataire\Service\PrestataireProfileCompletionService;
use App\Prestataire\Service\ZoneGeocoder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/prestataire/zones', name: 'app_prestataire_zone_')]
#[IsGranted('ROLE_PRESTATAIRE')]
/**
 * Gère les actions liées à prestataire zone.
 */
final class PrestataireZoneController extends AbstractController
{
    public function __construct(
        private readonly AuthenticatedUserProvider $authenticatedUserProvider,
    ) {
    }

    #[Route('/ajouter', name: 'add', methods: ['POST'])]
    /**
     * Ajoute la ressource demandée.
     */
    public function add(
        Request $request,
        EntityManagerInterface $em,
        FormFactoryInterface $formFactory,
        PrestataireProfileCompletionService $prestataireProfileCompletionService,
        ZoneGeocoder $zoneGeocoder,
    ): Response {
        $user = $this->authenticatedUserProvider->getAuthenticatedPrestataireUser();

        if (!$user || !$user->getPrestataireProfile()) {
            $this->addFlash('error', 'Profil prestataire introuvable.');

            return $this->redirectToRoute('app_login');
        }

        $zone = new PrestataireInterventionZone();
        $zone->setPrestataireProfile($user->getPrestataireProfile());

        $form = $formFactory->createNamed('zone_form', PrestataireInterventionZoneType::class, $zone, [
            'action' => $this->generateUrl('app_prestataire_zone_add'),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $geocoded = $zoneGeocoder->geocode(
                $zone->getCity(),
                $zone->getPostalCode()
            );

            if (null !== $geocoded) {
                $zone->setLatitude($geocoded['latitude']);
                $zone->setLongitude($geocoded['longitude']);
                $zone->setCity($geocoded['city']);
                $zone->setPostalCode($geocoded['postalCode']);
                $zone->setDepartment($geocoded['department']);
                $zone->setRegion($geocoded['region']);
            }

            $prestataireProfileCompletionService->syncCompletionScore($user, $user->getPrestataireProfile());
            $em->persist($user->getPrestataireProfile());
            $em->persist($zone);
            $em->flush();

            $this->addFlash('success', 'La zone d’intervention a bien été ajoutée.');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'zones-panel']);
    }

    #[Route('/supprimer/{id}', name: 'delete', methods: ['POST'])]
    /**
     * Supprime la ressource demandée.
     */
    public function delete(
        Request $request,
        PrestataireInterventionZone $zone,
        EntityManagerInterface $em,
        PrestataireProfileCompletionService $prestataireProfileCompletionService,
    ): Response {
        $user = $this->authenticatedUserProvider->getAuthenticatedPrestataireUser();

        if (
            !$user
            || !$user->getPrestataireProfile()
            || $zone->getPrestataireProfile() !== $user->getPrestataireProfile()
        ) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if ($this->isCsrfTokenValid('delete_zone_'.$zone->getId(), $request->request->get('_token'))) {
            $em->remove($zone);
            $prestataireProfileCompletionService->syncCompletionScore($user, $user->getPrestataireProfile());
            $em->persist($user->getPrestataireProfile());
            $em->flush();

            $this->addFlash('success', 'La zone a bien été supprimée.');
        }

        return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'zones-panel']);
    }
}
