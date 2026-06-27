<?php

namespace App\Service;

use App\Entity\Message;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class RealtimeNotifier
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $realtimeBaseUrl,
        private readonly string $internalToken,
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
            $this->logger->error('Realtime notification failed', [
                'conversationId' => $conversationId,
                'messageId' => $message->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}