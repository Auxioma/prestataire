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
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;

final class PrestataireServicePrestationController extends AbstractController
{
    #[Route('/prestataire/service/{id}/prestation', name: 'app_prestataire_service_prestation_edit')]
    public function edit(
        Request $request,
        PrestataireService $ps,
        EntityManagerInterface $em,
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

        $zonesCollection = $ps->getPrestataire()?->getPrestataireInterventionZones();
        $zones = $zonesCollection ? $zonesCollection->toArray() : [];

        $zoneMap = null;
        $firstMappableZone = null;

        foreach ($zones as $zone) {
            if (null !== $zone->getLatitude() && null !== $zone->getLongitude()) {
                $firstMappableZone = $zone;
                break;
            }
        }

        if (null !== $firstMappableZone) {
            $zoneMap = (new Map())
                ->center(new Point(
                    (float) $firstMappableZone->getLatitude(),
                    (float) $firstMappableZone->getLongitude()
                ))
                ->zoom(8)
                ->options(
                    (new LeafletOptions())->tileLayer(new TileLayer(
                        url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                        attribution: '<a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                        options: ['maxZoom' => 19],
                    ))
                );

            foreach ($zones as $zone) {
                if (null === $zone->getLatitude() || null === $zone->getLongitude()) {
                    continue;
                }

                $label = $zone->getCity() ?: 'Zone d’intervention';
                $radiusText = null !== $zone->getRadiusKm()
                    ? 'Rayon : ' . (int) $zone->getRadiusKm() . ' km'
                    : 'Rayon non renseigné';

                $zoneMap->addMarker(new Marker(
                    position: new Point(
                        (float) $zone->getLatitude(),
                        (float) $zone->getLongitude()
                    ),
                    title: $label,
                    infoWindow: new InfoWindow(
                        content: sprintf(
                            '<strong>%s</strong><br>%s',
                            htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
                            htmlspecialchars($radiusText, ENT_QUOTES, 'UTF-8')
                        )
                    )
                ));
            }
        }

        return $this->render('prestataire/edit_prestation.html.twig', [
            'form' => $form->createView(),
            'ps' => $ps,
            'prestation' => $ps,
            'zones' => $zones,
            'zoneMap' => $zoneMap,
            'map' => $zoneMap,
        ]);
    }
}