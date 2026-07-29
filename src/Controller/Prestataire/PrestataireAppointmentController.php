<?php

namespace App\Controller\Prestataire;

use App\Entity\PrestataireAppointment;
use App\Entity\User;
use App\Form\PrestataireAppointmentType;
use App\Repository\PrestataireAppointmentRepository;
use App\Service\AuthenticatedUserProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/prestataire/calendrier', name: 'app_prestataire_appointment_')]
#[IsGranted('ROLE_PRESTATAIRE')]
/**
 * Gère les actions liées à prestataire appointment.
 */
final class PrestataireAppointmentController extends AbstractController
{
    public function __construct(
        private readonly AuthenticatedUserProvider $authenticatedUserProvider,
    ) {
    }

    // RECUPERER LES RENDEZ-VOUS
    #[Route('/events', name: 'events', methods: ['GET'])]
    /**
     * Traite l’action "events" du contrôleur Prestataire Appointment.
     *
     * @return JsonResponse
     */
    public function events(
        Request $request,
        PrestataireAppointmentRepository $appointmentRepository,
    ): JsonResponse {
        $user = $this->authenticatedUserProvider->getAuthenticatedPrestataireUser();

        if (!$user || !$user->getPrestataireProfile()) {
            return $this->json([
                'success' => false,
                'message' => 'Accès refusé.',
            ], Response::HTTP_FORBIDDEN);
        }

        $prestataire = $user->getPrestataireProfile();
        $timezone = new \DateTimeZone('Europe/Paris');

        $start = $request->query->get('start');
        $end = $request->query->get('end');

        if (!is_string($start) || !is_string($end) || '' === trim($start) || '' === trim($end)) {
            return $this->json([
                'success' => false,
                'message' => 'Période invalide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $startAt = new \DateTimeImmutable($start, $timezone);
            $endAt = new \DateTimeImmutable($end, $timezone);
        } catch (\Throwable) {
            return $this->json([
                'success' => false,
                'message' => 'Dates invalides.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $appointments = $appointmentRepository->findForCalendarRange(
            $prestataire->getId(),
            $startAt,
            $endAt
        );

        $events = array_map(function (PrestataireAppointment $appointment) use ($timezone): array {
            $status = $appointment->getStatus();

            $startsAt = $appointment->getStartsAt();
            $endsAt = $appointment->getEndsAt();

            $startFormatted = $startsAt instanceof \DateTimeInterface
                ? \DateTimeImmutable::createFromInterface($startsAt)
                ->setTimezone($timezone)
                ->format('Y-m-d\TH:i:s')
                : null;

            $endFormatted = $endsAt instanceof \DateTimeInterface
                ? \DateTimeImmutable::createFromInterface($endsAt)
                ->setTimezone($timezone)
                ->format('Y-m-d\TH:i:s')
                : null;

            return [
                'id' => $appointment->getId(),
                'title' => $appointment->getTitle(),
                'start' => $startFormatted,
                'end' => $endFormatted,
                'allDay' => false,
                'url' => $this->generateUrl('app_prestataire_appointment_show', [
                    'slug' => $appointment->getSlug(),
                ]),
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

    // AJOUTER UN RENDEZ-VOUS
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    /**
     * Affiche et traite le formulaire de création.
     *
     * @return Response
     */
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
    ): Response {
        $user = $this->authenticatedUserProvider->getAuthenticatedPrestataireUser();

        if (!$user || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $appointment = new PrestataireAppointment();
        $appointment->setPrestataire($user->getPrestataireProfile());

        if (null === $appointment->getStartsAt() && null === $appointment->getEndsAt()) {
            $timezone = new \DateTimeZone('Europe/Paris');
            $now = new \DateTimeImmutable('now', $timezone);

            $minute = (int) $now->format('i');
            $minutesToAdd = (15 - ($minute % 15)) % 15;

            $roundedStart = $now
                ->setTime((int) $now->format('H'), (int) $now->format('i'), 0)
                ->modify(sprintf('+%d minutes', $minutesToAdd));

            if ($minutesToAdd === 0) {
                $roundedStart = $roundedStart->modify('+15 minutes');
            }

            $roundedEnd = $roundedStart->modify('+1 hour');

            $appointment->setStartsAt(\DateTime::createFromImmutable($roundedStart));
            $appointment->setEndsAt(\DateTime::createFromImmutable($roundedEnd));
        }

        $form = $this->createForm(PrestataireAppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $baseLabel = $appointment->getTitle() ?: 'rendez-vous';
            $baseSlug = (string) $slugger->slug($baseLabel)->lower();
            $uniqueSlug = sprintf('%s-%s', $baseSlug, substr(bin2hex(random_bytes(4)), 0, 8));

            $appointment->setSlug($uniqueSlug);
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

    // LISTE DE TOUS LES RENDEZ-VOUS
    #[Route('', name: 'index', methods: ['GET'])]
    /**
     * Affiche la page principale de ce contrôleur.
     *
     * @return Response
     */
    public function index(
        PrestataireAppointmentRepository $appointmentRepository,
    ): Response {
        $user = $this->authenticatedUserProvider->getAuthenticatedPrestataireUser();

        if (!$user || !$user->getPrestataireProfile()) {
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

    // VISUALISER UN RENDEZ-VOUS
    #[Route('/{slug}', name: 'show', methods: ['GET'], requirements: ['slug' => Requirement::ASCII_SLUG])]
    /**
     * Affiche le détail de la ressource demandée.
     *
     * @return Response
     */
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] PrestataireAppointment $appointment,
    ): Response {
        $user = $this->authenticatedUserProvider->getAuthenticatedPrestataireUser();

        if (!$user || !$user->getPrestataireProfile()) {
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

    // MODIFIER UN RENDEZ-VOUS
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    /**
     * Affiche et traite le formulaire de modification.
     *
     * @return Response
     */
    public function edit(
        Request $request,
        #[MapEntity(id: 'id')] PrestataireAppointment $appointment,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->authenticatedUserProvider->getAuthenticatedPrestataireUser();

        if (!$user || !$user->getPrestataireProfile()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $prestataire = $user->getPrestataireProfile();

        if ($appointment->getPrestataire()?->getId() !== $prestataire->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce rendez-vous.');
        }

        $form = $this->createForm(PrestataireAppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

    // SUPPRIMER UN RENDEZ-VOUS
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    /**
     * Supprime la ressource demandée.
     *
     * @return Response
     */
    public function delete(
        Request $request,
        #[MapEntity(id: 'id')] PrestataireAppointment $appointment,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->authenticatedUserProvider->getAuthenticatedPrestataireUser();

        if (!$user || !$user->getPrestataireProfile()) {
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

    // DEPLACER UN RENDEZ-VOUS DRAG & DROP
    #[Route('/move', name: 'move', methods: ['POST'])]
    /**
     * Traite l’action "move" du contrôleur Prestataire Appointment.
     *
     * @return JsonResponse
     */
    public function move(
        Request $request,
        PrestataireAppointmentRepository $appointmentRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getPrestataireProfile()) {
            return $this->json([
                'success' => false,
                'message' => 'Accès refusé.',
            ], Response::HTTP_FORBIDDEN);
        }

        $csrfToken = $request->headers->get('X-CSRF-TOKEN');

        if (!$this->isCsrfTokenValid('move_appointment', $csrfToken)) {
            return $this->json([
                'success' => false,
                'message' => 'Jeton CSRF invalide.',
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (
            !is_array($data)
            || empty($data['id'])
            || empty($data['startsAt'])
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Données invalides.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $appointment = $appointmentRepository->find($data['id']);

        if (!$appointment instanceof PrestataireAppointment) {
            return $this->json([
                'success' => false,
                'message' => 'Rendez-vous introuvable.',
            ], Response::HTTP_NOT_FOUND);
        }

        $prestataire = $user->getPrestataireProfile();

        if ($appointment->getPrestataire()?->getId() !== $prestataire->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas modifier ce rendez-vous.',
            ], Response::HTTP_FORBIDDEN);
        }

        $timezone = new \DateTimeZone('Europe/Paris');

        try {
            $startsAt = new \DateTime($data['startsAt'], $timezone);

            if (!empty($data['endsAt']) && is_string($data['endsAt'])) {
                $endsAt = new \DateTime($data['endsAt'], $timezone);
            } else {
                $endsAt = clone $startsAt;
                $endsAt->modify('+1 hour');
            }

            if ($endsAt <= $startsAt) {
                return $this->json([
                    'success' => false,
                    'message' => 'La date de fin doit être postérieure à la date de début.',
                ], Response::HTTP_BAD_REQUEST);
            }

            $appointment
                ->setStartsAt($startsAt)
                ->setEndsAt($endsAt);

            $entityManager->flush();

            return $this->json([
                'success' => true,
                'event' => [
                    'id' => $appointment->getId(),
                    'start' => $startsAt->format('Y-m-d\TH:i:s'),
                    'end' => $endsAt->format('Y-m-d\TH:i:s'),
                ],
            ]);
        } catch (\Throwable) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du rendez-vous.',
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
