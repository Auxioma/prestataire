<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\PrestataireAppointmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\PrestataireAppointment;
use App\Form\PrestataireAppointmentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

#[Route('/prestataire/calendrier', name: 'app_prestataire_appointment_')]
final class PrestataireAppointmentController extends AbstractController
{

    private function normalizeAllDayAppointment(PrestataireAppointment $appointment): void
{
    if (!$appointment->isAllDay()) {
        return;
    }

    $startsAt = $appointment->getStartsAt();
    $endsAt = $appointment->getEndsAt();

    $normalizedStart = null;
    $normalizedEnd = null;

    if ($startsAt instanceof \DateTimeInterface) {
        $normalizedStart = new \DateTime($startsAt->format('Y-m-d H:i:s'));
        $normalizedStart->setTime(0, 0, 0);
    }

    if ($endsAt instanceof \DateTimeInterface) {
        $normalizedEnd = new \DateTime($endsAt->format('Y-m-d H:i:s'));
        $normalizedEnd->setTime(23, 59, 59);
    } elseif ($normalizedStart instanceof \DateTime) {
        $normalizedEnd = clone $normalizedStart;
        $normalizedEnd->setTime(23, 59, 59);
    }

    if ($normalizedStart instanceof \DateTime) {
        $appointment->setStartsAt($normalizedStart);
    }

    if ($normalizedEnd instanceof \DateTime) {
        $appointment->setEndsAt($normalizedEnd);
    }
}

    // RECUPERER LES RENDEZ-VOUS
    #[Route('/events', name: 'events', methods: ['GET'])]
    public function events(
        Request $request,
        PrestataireAppointmentRepository $appointmentRepository,
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getPrestataireProfile()) {
            return $this->json([
                'success' => false,
                'message' => 'Accès refusé.',
            ], Response::HTTP_FORBIDDEN);
        }

        $prestataire = $user->getPrestataireProfile();

        $start = $request->query->get('start');
        $end = $request->query->get('end');

        if (!$start || !$end) {
            return $this->json([
                'success' => false,
                'message' => 'Période invalide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $startAt = new \DateTimeImmutable($start);
            $endAt = new \DateTimeImmutable($end);
        } catch (\Throwable) {
            return $this->json([
                'success' => false,
                'message' => 'Dates invalides.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $appointments = $appointmentRepository->findForCalendarRange($prestataire->getId(), $startAt, $endAt);

        $events = array_map(static function (PrestataireAppointment $appointment): array {
            $status = $appointment->getStatus();

            return [
                'id' => $appointment->getId(),
                'title' => $appointment->getTitle(),
                'start' => $appointment->getStartsAt()?->format(\DateTimeInterface::ATOM),
                'end' => $appointment->getEndsAt()?->format(\DateTimeInterface::ATOM),
                'allDay' => $appointment->isAllDay(),
                'backgroundColor' => $status->getCalendarColor(),
                'borderColor' => $status->getCalendarColor(),
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'status' => $status->value,
                    'statusLabel' => $status->getLabel(),
                    'description' => $appointment->getDescription(),
                    'locationLabel' => $appointment->getLocationLabel(),
                    'prestationId' => $appointment->getPrestation()?->getId(),
                    'clientId' => $appointment->getClient()?->getId(),
                ],
            ];
        }, $appointments);

        return $this->json($events);
    }

    // CREATE NEW APPOINTMENT
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $appointment = new PrestataireAppointment();
        $appointment->setPrestataire($user->getPrestataireProfile());

        $form = $this->createForm(PrestataireAppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->normalizeAllDayAppointment($appointment);

            $entityManager->persist($appointment);
            $entityManager->flush();

            $this->addFlash('success', 'Le rendez-vous a bien été créé.');

            return $this->redirect(
                $this->generateUrl('app_prestataire_dashboard') . '#calendrier-main-panel'
            );
        }

        return $this->render('prestataire/appointment/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // READ ALL APPOINTMENTS
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        PrestataireAppointmentRepository $appointmentRepository,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $prestataire = $user->getPrestataireProfile();

        $appointments = $appointmentRepository->findBy(
            ['prestataire' => $prestataire],
            ['startsAt' => 'ASC']
        );

        return $this->render('prestataire/appointment/index.html.twig', [
            'appointments' => $appointments,
        ]);
    }

    // READ ONE APPOINTMENT
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        #[MapEntity(id: 'id')] PrestataireAppointment $appointment,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $prestataire = $user->getPrestataireProfile();

        if ($appointment->getPrestataire()?->getId() !== $prestataire->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas consulter ce rendez-vous.');
        }

        return $this->render('prestataire/appointment/show.html.twig', [
            'appointment' => $appointment,
        ]);
    }

    // EDIT APPOINTMENT
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        #[MapEntity(id: 'id')] PrestataireAppointment $appointment,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $prestataire = $user->getPrestataireProfile();

        if ($appointment->getPrestataire()?->getId() !== $prestataire->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce rendez-vous.');
        }

        $form = $this->createForm(PrestataireAppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->normalizeAllDayAppointment($appointment);

            $entityManager->flush();

            $this->addFlash('success', 'Le rendez-vous a bien été modifié.');

            return $this->redirect(
                $this->generateUrl('app_prestataire_dashboard') . '#calendrier-main-panel'
            );
        }

        return $this->render('prestataire/appointment/edit.html.twig', [
            'appointment' => $appointment,
            'form' => $form->createView(),
        ]);
    }

    // DELETE APPOINTMENT
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        Request $request,
        #[MapEntity(id: 'id')] PrestataireAppointment $appointment,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $prestataire = $user->getPrestataireProfile();

        if ($appointment->getPrestataire()?->getId() !== $prestataire->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce rendez-vous.');
        }

        if ($this->isCsrfTokenValid('delete_appointment_' . $appointment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($appointment);
            $entityManager->flush();

            $this->addFlash('success', 'Le rendez-vous a bien été supprimé.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirect(
            $this->generateUrl('app_prestataire_dashboard') . '#calendrier-main-panel'
        );
    }
}
