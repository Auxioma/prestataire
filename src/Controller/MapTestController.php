<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;

final class MapTestController extends AbstractController
{
    #[Route('/test-map', name: 'app_test_map', methods: ['GET'])]
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

        return $this->render('test/map.html.twig', [
            'map' => $map,
        ]);
    }
}