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

namespace App\Messaging\Service;

use App\Account\Entity\User;
use App\Messaging\Entity\Notification;
use App\Messaging\Enum\NotificationTypeEnum;
use Doctrine\ORM\EntityManagerInterface;

class NotificationManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RealtimeNotifier $realtimeNotifier,
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
        if (!$recipient->shouldReceiveNotificationType($type)) {
            return new Notification()
                ->setRecipient($recipient)
                ->setType($type)
                ->setTitle($title)
                ->setBody($body)
                ->setTargetUrl($targetUrl)
                ->setMetadata($metadata);
        }

        $notification = new Notification()
            ->setRecipient($recipient)
            ->setType($type)
            ->setTitle($title)
            ->setBody($body)
            ->setTargetUrl($targetUrl)
            ->setMetadata($metadata);

        $this->entityManager->persist($notification);

        if ($flush) {
            $this->entityManager->flush();
            $this->realtimeNotifier->notifyNotificationCreated($recipient, $notification);
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

            if (!$recipient->shouldReceiveNotificationType($type)) {
                continue;
            }

            $notification = new Notification()
                ->setRecipient($recipient)
                ->setType($type)
                ->setTitle($title)
                ->setBody($body)
                ->setTargetUrl($targetUrl)
                ->setMetadata($metadata);

            $this->entityManager->persist($notification);
            $notifications[] = $notification;
        }

        if ($flush && [] !== $notifications) {
            $this->entityManager->flush();

            foreach ($notifications as $notification) {
                $recipient = $notification->getRecipient();

                if ($recipient instanceof User) {
                    $this->realtimeNotifier->notifyNotificationCreated($recipient, $notification);
                }
            }
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
