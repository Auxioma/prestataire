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
use App\Repository\PrestataireServiceRepository;
use App\Repository\ServiceCategoryRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
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
use Symfony\Component\HttpFoundation\JsonResponse;


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

        if ($ps->getMedias()->count() < 5) {
            $missing = 5 - $ps->getMedias()->count();

            for ($i = 0; $i < $missing; $i++) {
                $media = new \App\Entity\PrestationMedia();
                $media->setPosition($ps->getMedias()->count());
                $ps->addMedia($media);
            }
        }

        $form = $this->createForm(PrestataireServicePrestationType::class, $ps);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->has('medias')) {
                foreach ($form->get('medias') as $mediaForm) {
                    $media = $mediaForm->getData();

                    if (!$media) {
                        continue;
                    }

                    if ($mediaForm->has('delete') && true === $mediaForm->get('delete')->getData()) {
                        if (method_exists($ps, 'removeMedia')) {
                            $ps->removeMedia($media);
                        }

                        $em->remove($media);
                    }
                }
            }

            $em->flush();

            $this->addFlash('success', 'Prestation détaillée enregistrée.');

            return $this->redirect(
                $this->generateUrl('app_prestataire_settings') . '#services-panel'
            );
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

    #[Route('/prestataire/prestations/nouvelle', name: 'app_prestataire_service_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        PrestataireServiceRepository $prestataireServiceRepository,
        ServiceRepository $serviceRepository,
        ServiceCategoryRepository $categoryRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $prestataire = $user?->getPrestataireProfile();

        if (!$prestataire) {
            throw $this->createAccessDeniedException('Profil prestataire introuvable.');
        }

        $categories = $categoryRepository->findBy([
            'parent' => null,
            'isActive' => true,
        ], ['position' => 'ASC']);

        if ($request->isMethod('POST')) {
            $serviceId = $request->request->get('serviceId');
            $selectedService = $serviceId ? $serviceRepository->find($serviceId) : null;

            if (!$selectedService) {
                $this->addFlash('error', 'Veuillez sélectionner un service valide.');

                return $this->render('prestataire/new_prestation.html.twig', [
                    'categories' => $categories,
                ]);
            }

            $existing = $prestataireServiceRepository->findOneBy([
                'prestataire' => $prestataire,
                'service' => $selectedService,
            ]);

            if ($existing) {
                $this->addFlash('warning', 'Cette prestation existe déjà. Vous allez être redirigé vers sa fiche.');

                return $this->redirectToRoute('app_prestataire_service_prestation_edit', [
                    'id' => $existing->getId(),
                ]);
            }

            $prestation = new PrestataireService();
            $prestation->setPrestataire($prestataire);
            $prestation->setService($selectedService);
            $prestation->setIsActive(true);

            $em->persist($prestation);
            $em->flush();

            $this->addFlash('success', 'Le service a bien été ajouté. Vous pouvez maintenant compléter la prestation.');

            return $this->redirectToRoute('app_prestataire_service_prestation_edit', [
                'id' => $prestation->getId(),
            ]);
        }

        return $this->render('prestataire/new_prestation.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/prestataire/service/{id}/prestation/voir', name: 'app_prestataire_service_prestation_show', methods: ['GET'])]
    public function show(PrestataireService $ps): Response
    {
        if (!$ps->isActive()) {
            throw $this->createNotFoundException('Cette prestation est introuvable.');
        }

        $prestataire = $ps->getPrestataire();
        $zones = $prestataire?->getPrestataireInterventionZones() ?? [];

        $centerLat = 44.8378;
        $centerLng = -0.5792;
        $hasMapCenter = false;

        foreach ($zones as $zone) {
            if ($zone->getLatitude() && $zone->getLongitude()) {
                $centerLat = (float) $zone->getLatitude();
                $centerLng = (float) $zone->getLongitude();
                $hasMapCenter = true;
                break;
            }
        }

        $companyName = $prestataire?->getCompanyName() ?: 'Prestataire';
        $serviceName = $ps->getService()?->getName() ?: 'Prestation';

        $prestationMap = (new Map())
            ->center(new Point($centerLat, $centerLng))
            ->zoom($hasMapCenter ? 10 : 6)
            ->options(
                (new LeafletOptions())->tileLayer(new TileLayer(
                    url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                    attribution: '<a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    options: ['maxZoom' => 19],
                ))
            );

        if ($hasMapCenter) {
            $prestationMap->addMarker(
                new Marker(
                    position: new Point($centerLat, $centerLng),
                    title: $companyName,
                    infoWindow: new InfoWindow(
                        content: sprintf('<strong>%s</strong><br>%s', $companyName, $serviceName)
                    )
                )
            );
        }

        return $this->render('prestataire/show_prestation.html.twig', [
            'ps' => $ps,
            'prestation' => $ps,
            'prestationMap' => $prestationMap,
        ]);
    }

    #[Route('/prestataire/service/{id}/toggle-active', name: 'app_prestataire_service_toggle_active', methods: ['POST'])]
    public function toggleActive(
        Request $request,
        PrestataireService $ps,
        EntityManagerInterface $em,
    ): JsonResponse {
        $user = $this->getUser();

        if (
            !$user instanceof User
            || !$user->getPrestataireProfile()
            || $ps->getPrestataire() !== $user->getPrestataireProfile()
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Accès refusé.',
            ], Response::HTTP_FORBIDDEN);
        }

        $token = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('toggle_prestation_' . $ps->getId(), $token)) {
            return $this->json([
                'success' => false,
                'message' => 'Jeton CSRF invalide.',
            ], Response::HTTP_FORBIDDEN);
        }

        $ps->setIsActive(!$ps->isActive());
        $em->flush();

        return $this->json([
            'success' => true,
            'isActive' => $ps->isActive(),
            'message' => $ps->isActive()
                ? 'La prestation est maintenant active.'
                : 'La prestation a été désactivée.',
        ]);
    }
}
