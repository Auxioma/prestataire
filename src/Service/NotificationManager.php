<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Enum\NotificationTypeEnum;
use Doctrine\ORM\EntityManagerInterface;

class NotificationManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function notify(
        User $recipient,
        NotificationTypeEnum $type,
        string $title,
        string $body,
        ?string $targetUrl = null,
        ?array $metadata = null,
        bool $flush = true,
    ): Notification {
        $notification = (new Notification())
            ->setRecipient($recipient)
            ->setType($type)
            ->setTitle($title)
            ->setBody($body)
            ->setTargetUrl($targetUrl)
            ->setMetadata($metadata)
        ;

        $this->entityManager->persist($notification);

        if ($flush) {
            $this->entityManager->flush();
        }

        return $notification;
    }

    /**
     * @param iterable<User> $recipients
     *
     * @return Notification[]
     */
    public function notifyMany(
        iterable $recipients,
        NotificationTypeEnum $type,
        string $title,
        string $body,
        ?string $targetUrl = null,
        ?array $metadata = null,
        bool $flush = true,
    ): array {
        $notifications = [];

        foreach ($recipients as $recipient) {
            if (!$recipient instanceof User) {
                continue;
            }

            $notification = (new Notification())
                ->setRecipient($recipient)
                ->setType($type)
                ->setTitle($title)
                ->setBody($body)
                ->setTargetUrl($targetUrl)
                ->setMetadata($metadata)
            ;

            $this->entityManager->persist($notification);
            $notifications[] = $notification;
        }

        if ($flush && [] !== $notifications) {
            $this->entityManager->flush();
        }

        return $notifications;
    }

    public function markAsRead(Notification $notification, bool $flush = true): Notification
    {
        $notification->markAsRead();

        if ($flush) {
            $this->entityManager->flush();
        }

        return $notification;
    }

    public function markAllAsReadForUser(User $user, bool $flush = true): void
    {
        foreach ($user->getNotifications() as $notification) {
            if (!$notification->isRead()) {
                $notification->markAsRead();
            }
        }

        if ($flush) {
            $this->entityManager->flush();
        }
    }
}