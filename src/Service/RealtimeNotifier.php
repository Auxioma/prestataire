<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\Notification;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class RealtimeNotifier
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $realtimeBaseUrl,
        private readonly string $internalToken,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function notifyMessageCreated(int $conversationId, Message $message): void
    {
        $author = $message->getAuthor();

        $authorName = $author
            ? trim(($author->getFirstName() ?? '') . ' ' . ($author->getLastName() ?? ''))
            : 'Système';

        $authorType = 'system';

        if ($author) {
            $authorType = in_array('ROLE_PRESTATAIRE', $author->getRoles(), true)
                ? 'prestataire'
                : 'client';
        }

        try {
            $response = $this->httpClient->request('POST', rtrim($this->realtimeBaseUrl, '/') . '/emit/message', [
                'headers' => [
                    'x-internal-token' => $this->internalToken,
                ],
                'json' => [
                    'conversationId' => $conversationId,
                    'message' => [
                        'id' => $message->getId(),
                        'content' => $message->getContent(),
                        'authorName' => $authorName,
                        'authorType' => $authorType,
                        'createdAt' => $message->getCreatedAt()?->format('Y-m-d H:i:s'),
                    ],
                ],
            ]);

            $response->getStatusCode();
        } catch (\Throwable $e) {
            $this->logger->error('Realtime message broadcast failed', [
                'conversationId' => $conversationId,
                'messageId' => $message->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notifyNotificationCreated(User $recipient, Notification $notification): void
    {
        $notificationId = $notification->getId();

        if (null === $notificationId) {
            return;
        }

        $openUrl = $this->urlGenerator->generate('app_notification_open', [
            'id' => $notificationId,
        ]);

        try {
            $response = $this->httpClient->request('POST', rtrim($this->realtimeBaseUrl, '/') . '/emit/notification', [
                'headers' => [
                    'x-internal-token' => $this->internalToken,
                ],
                'json' => [
                    'userId' => $recipient->getId(),
                    'notification' => [
                        'id' => $notificationId,
                        'type' => $notification->getType()?->value,
                        'title' => $notification->getTitle(),
                        'body' => $notification->getBody(),
                        'targetUrl' => $notification->getTargetUrl(),
                        'readUrl' => $openUrl,
                        'isRead' => $notification->isRead(),
                        'createdAt' => $notification->getCreatedAt()?->format('d/m/Y H:i'),
                    ],
                ],
            ]);

            $response->getStatusCode();
        } catch (\Throwable $e) {
            $this->logger->error('Realtime notification broadcast failed', [
                'recipientId' => $recipient->getId(),
                'notificationId' => $notificationId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}