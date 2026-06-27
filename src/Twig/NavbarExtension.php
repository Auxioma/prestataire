<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Repository\ServiceCategoryRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class NavbarExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly ServiceCategoryRepository $categoryRepository,
        private readonly NotificationRepository $notificationRepository,
        private readonly Security $security,
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
        ];
    }
}