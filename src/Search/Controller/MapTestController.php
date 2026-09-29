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

namespace App\Search\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;

/**
 * Gère les actions liées à map test.
 */
final class MapTestController extends AbstractController
{
    #[Route('/test-map', name: 'app_test_map', methods: ['GET'])]
    /**
     * Traite l’action "__invoke" du contrôleur Map Test.
     */
    public function __invoke(): Response
    {
        $map = (new Map('default'))
            ->center(new Point(44.9793, -1.0797))
            ->zoom(9)
            ->addMarker(new Marker(
                position: new Point(44.9793, -1.0797),
                title: 'Lacanau',
                infoWindow: new InfoWindow(
                    content: '<strong>Lacanau</strong><br>Carte de test UX Map'
                )
            ))
            ->options(
                (new LeafletOptions())
                    ->tileLayer(new TileLayer(
                        url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                        options: ['maxZoom' => 19]
                    ))
            );

        return $this->render('search/test/map.html.twig', [
            'map' => $map,
        ]);
    }
}
