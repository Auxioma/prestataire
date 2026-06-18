<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\PrestataireAppointmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/prestataire/calendrier', name: 'app_prestataire_appointment_')]
final class PrestataireAppointmentController extends AbstractController
{
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

        $events = array_map(static function ($appointment): array {
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
}