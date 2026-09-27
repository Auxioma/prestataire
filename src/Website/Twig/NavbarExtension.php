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

namespace App\Website\Twig;

use App\Account\Entity\User;
use App\Catalog\Repository\ServiceCategoryRepository;
use App\Messaging\Repository\NotificationRepository;
use App\Messaging\Service\RealtimeAuthTokenManager;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class NavbarExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly ServiceCategoryRepository $categoryRepository,
        private readonly NotificationRepository $notificationRepository,
        private readonly Security $security,
        private readonly RealtimeAuthTokenManager $realtimeAuthTokenManager,
    ) {
    }

    public function getGlobals(): array
    {
        $user = $this->security->getUser();

        $unreadCount = 0;
        $latestNotifications = [];

        if ($user instanceof User) {
            $unreadCount = $this->notificationRepository->countUnreadForUser($user);
            $latestNotifications = $this->notificationRepository->findLatestForUser($user, 5);
        }

        return [
            'navbarCategories' => $this->categoryRepository->findWithSubCategories(),
            'navbarUnreadNotificationCount' => $unreadCount,
            'navbarLatestNotifications' => $latestNotifications,
            'navbarRealtimeNotificationsToken' => $user instanceof User
                ? $this->realtimeAuthTokenManager->createUserToken($user)
                : null,
        ];
    }
}
