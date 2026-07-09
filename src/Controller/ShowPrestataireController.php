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

use App\Entity\PrestataireProfile;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use App\Entity\User;
use App\Enum\FavoriteTypeEnum;
use App\Repository\FavoriteRepository;

/**
 * Gère les actions liées à show prestataire.
 */
class ShowPrestataireController extends AbstractController
{
    #[Route('/prestataire/{slug}', name: 'app_prestataire_show', methods: ['GET'])]
    /**
     * Traite l’action "__invoke" du contrôleur Show Prestataire.
     *
     * @return Response
     */
    public function __invoke(
        #[MapEntity(mapping: ['slug' => 'slug'])] PrestataireProfile $prestataire,
        FavoriteRepository $favoriteRepository,
    ): Response {
        if (!$prestataire->getCompanyName()) {
            throw $this->createNotFoundException('Ce profil professionnel n\'est pas encore actif.');
        }

        $zones = array_values(array_filter(
            $prestataire->getPrestataireInterventionZones()->toArray(),
            static fn($zone) => $zone->isActive()
        ));

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
                ->zoom(9)
                ->options(
                    (new LeafletOptions())
                        ->tileLayer(new TileLayer(
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
                        (float) $zone->getLongitude(),
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

        $isFavoriteProvider = false;
        $user = $this->getUser();

        if ($user instanceof User && $this->isGranted('ROLE_CLIENT')) {
            $isFavoriteProvider = null !== $favoriteRepository->findOneBy([
                'user' => $user,
                'type' => FavoriteTypeEnum::PRESTATAIRE,
                'targetId' => $prestataire->getId(),
            ]);
        }

        return $this->render('show_prestataire/show.html.twig', [
            'prestataire' => $prestataire,
            'zones' => $zones,
            'zoneMap' => $zoneMap,
            'isFavoriteProvider' => $isFavoriteProvider,
        ]);
    }
}
