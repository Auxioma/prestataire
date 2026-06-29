<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Service\NotificationManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/notifications', name: 'app_notification_')]
final class NotificationController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        NotificationRepository $notificationRepository,
        PaginatorInterface $paginator
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $queryBuilder = $notificationRepository
            ->createQueryBuilder('n')
            ->andWhere('n.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('notification/index.html.twig', [
            'notifications' => $pagination,
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

        return $this->redirectToRoute('app_notification_index');
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

        $redirect = (string) $request->request->get('redirect', '');

        if ($redirect !== '') {
            return $this->redirect($redirect, 303);
        }

        return $this->redirectToRoute('app_notification_index');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Notification $notification,
        EntityManagerInterface $entityManager
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
                'delete-notification-' . $notification->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_notification_index');
        }

        $entityManager->remove($notification);
        $entityManager->flush();

        $this->addFlash('success', 'La notification a bien été supprimée.');

        return $this->redirectToRoute('app_notification_index');
    }

    #[Route('/delete-all', name: 'delete_all', methods: ['POST'])]
    public function deleteAll(
        Request $request,
        NotificationRepository $notificationRepository,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if (
            !$this->isCsrfTokenValid(
                'delete-all-notifications',
                (string) $request->request->get('_token')
            )
        ) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_notification_index');
        }

        $notifications = $notificationRepository->findBy([
            'recipient' => $user,
        ]);

        foreach ($notifications as $notification) {
            $entityManager->remove($notification);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Toutes les notifications ont été supprimées.');

        return $this->redirectToRoute('app_notification_index');
    }
}
