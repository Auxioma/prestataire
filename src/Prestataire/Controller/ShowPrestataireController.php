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

use App\Account\Entity\User;
use App\Core\Service\SafeReturnUrlResolver;
use App\Prestataire\Entity\PrestataireProfile;
use App\Prestataire\Service\PrestataireResponseTimeManager;
use App\Review\Enum\FavoriteTypeEnum;
use App\Review\Repository\FavoriteRepository;
use App\Subscription\Service\SubscriptionAccessManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;

/**
 * Gère les actions liées à show prestataire.
 */
class ShowPrestataireController extends AbstractController
{
    #[Route(
        '/prestataire/{slug}',
        name: 'app_prestataire_show',
        methods: ['GET'],
        requirements: ['slug' => '(?!(?:abonnements|demandes)$)[a-z0-9-]+']
    )]
    /**
     * Traite l’action "__invoke" du contrôleur Show Prestataire.
     */
    public function __invoke(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])]
        PrestataireProfile $prestataire,
        FavoriteRepository $favoriteRepository,
        PrestataireResponseTimeManager $prestataireResponseTimeManager,
        SubscriptionAccessManager $subscriptionAccessManager,
        SafeReturnUrlResolver $safeReturnUrlResolver,
    ): Response {
        if (!$prestataire->getCompanyName()) {
            throw $this->createNotFoundException('Ce profil professionnel n\'est pas encore actif.');
        }

        if (null === $prestataire->getResponseTimeMinutes()) {
            $prestataireResponseTimeManager->refreshForPrestataire($prestataire);
        }

        $zones = array_values(array_filter(
            $prestataire->getPrestataireInterventionZones()->toArray(),
            static fn ($zone) => $zone->isActive()
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
                    ? 'Rayon : '.(int) $zone->getRadiusKm().' km'
                    : 'Rayon non renseigné';

                $zoneMap->addMarker(new Marker(
                    position: new Point(
                        (float) $zone->getLatitude(),
                        (float) $zone->getLongitude(),
                    ),
                    title: $label,
                    infoWindow: new InfoWindow(
                        content: \sprintf(
                            '<strong>%s</strong><br>%s',
                            htmlspecialchars($label, \ENT_QUOTES, 'UTF-8'),
                            htmlspecialchars($radiusText, \ENT_QUOTES, 'UTF-8')
                        )
                    )
                ));
            }
        }

        $isFavoriteProvider = false;
        $favoritePrestationIds = [];
        $user = $this->getUser();

        if ($user instanceof User && $this->isGranted('ROLE_CLIENT')) {
            $isFavoriteProvider = null !== $favoriteRepository->findOneBy([
                'user' => $user,
                'type' => FavoriteTypeEnum::PRESTATAIRE,
                'targetId' => $prestataire->getId(),
            ]);

            $favoritePrestationIds = $favoriteRepository->findTargetIdsByUserAndType($user, FavoriteTypeEnum::PRESTATION);
        }

        $currentSubscription = $subscriptionAccessManager->getCurrentUsableSubscription($prestataire);
        $hasUnlockedContactDetails = null !== $currentSubscription
            && 'free' !== $currentSubscription->getPlan()?->getCode();

        return $this->render('prestataire/show_prestataire/show.html.twig', [
            'prestataire' => $prestataire,
            'zones' => $zones,
            'zoneMap' => $zoneMap,
            'isFavoriteProvider' => $isFavoriteProvider,
            'favoritePrestationIds' => $favoritePrestationIds,
            'hasUnlockedContactDetails' => $hasUnlockedContactDetails,
            'backUrl' => $safeReturnUrlResolver->resolve($request, $this->generateUrl('app_home')),
        ]);
    }
}
