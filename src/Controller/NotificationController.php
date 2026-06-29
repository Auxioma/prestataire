<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Service\NotificationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/notifications', name: 'app_notification_')]
final class NotificationController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        return $this->render('notification/index.html.twig', [
            'notifications' => $notificationRepository->findLatestForUser($user, 50),
            'unreadCount' => $notificationRepository->countUnreadForUser($user),
        ]);
    }

    #[Route('/{id}/open', name: 'open', methods: ['GET'])]
    public function open(
        Notification $notification,
        NotificationManager $notificationManager
    ): RedirectResponse {
        $user = $this->getUser();

        if (
            !$user instanceof User
            || $notification->getRecipient()?->getId() !== $user->getId()
        ) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if (!$notification->isRead()) {
            $notificationManager->markAsRead($notification);
        }

        return $this->redirect(
            $notification->getTargetUrl() ?: $this->generateUrl('app_notification_index')
        );
    }

    #[Route('/{id}/read', name: 'mark_read', methods: ['POST'])]
    public function markRead(
        Request $request,
        Notification $notification,
        NotificationManager $notificationManager
    ): RedirectResponse {
        $user = $this->getUser();

        if (
            !$user instanceof User
            || $notification->getRecipient()?->getId() !== $user->getId()
        ) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if (
            !$this->isCsrfTokenValid(
                'mark-notification-read-' . $notification->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_notification_index');
        }

        if (!$notification->isRead()) {
            $notificationManager->markAsRead($notification);
        }

        return $this->redirect(
            $notification->getTargetUrl() ?: $this->generateUrl('app_notification_index')
        );
    }

    #[Route('/read-all', name: 'mark_all_read', methods: ['POST'])]
    public function markAllRead(
        Request $request,
        NotificationManager $notificationManager
    ): RedirectResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if (
            !$this->isCsrfTokenValid(
                'mark-all-notifications-read',
                (string) $request->request->get('_token')
            )
        ) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_notification_index');
        }

        $notificationManager->markAllAsReadForUser($user);
        $this->addFlash('success', 'Toutes les notifications ont été marquées comme lues.');

        return $this->redirectToRoute('app_notification_index');
    }
}