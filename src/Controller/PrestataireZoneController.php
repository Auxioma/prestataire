<?php

namespace App\Controller;

use App\Entity\PrestataireInterventionZone;
use App\Entity\User;
use App\Form\PrestataireInterventionZoneType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormFactoryInterface;
use App\Service\ZoneGeocoder;

#[Route('/prestataire/zones', name: 'app_prestataire_zone_')]
final class PrestataireZoneController extends AbstractController
{
    #[Route('/ajouter', name: 'add', methods: ['POST'])]
    public function add(
        Request $request,
        EntityManagerInterface $em,
        FormFactoryInterface $formFactory,
        ZoneGeocoder $zoneGeocoder
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

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

            if ($geocoded !== null) {
                $zone->setLatitude($geocoded['latitude']);
                $zone->setLongitude($geocoded['longitude']);
                $zone->setCity($geocoded['city']);
                $zone->setPostalCode($geocoded['postalCode']);
                $zone->setDepartment($geocoded['department']);
                $zone->setRegion($geocoded['region']);
            }

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
    public function delete(
        Request $request,
        PrestataireInterventionZone $zone,
        EntityManagerInterface $em
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

        if (
            !$user ||
            !$user->getPrestataireProfile() ||
            $zone->getPrestataireProfile() !== $user->getPrestataireProfile()
        ) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if ($this->isCsrfTokenValid('delete_zone_' . $zone->getId(), $request->request->get('_token'))) {
            $em->remove($zone);
            $em->flush();

            $this->addFlash('success', 'La zone a bien été supprimée.');
        }

        return $this->redirectToRoute('app_prestataire_settings', ['_fragment' => 'zones-panel']);
    }
}
