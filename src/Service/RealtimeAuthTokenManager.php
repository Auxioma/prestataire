<?php

namespace App\Service;

use App\Entity\User;

final class RealtimeAuthTokenManager
{
    private const DEFAULT_TTL = 300;

    public function __construct(
        private readonly string $internalToken,
    ) {
    }

    public function createUserToken(User $user, ?int $ttl = null): string
    {
        return $this->createToken([
            'type' => 'user',
            'userId' => (int) $user->getId(),
        ], $ttl);
    }

    public function createConversationToken(int $conversationId, User $user, ?int $ttl = null): string
    {
        return $this->createToken([
            'type' => 'conversation',
            'conversationId' => $conversationId,
            'userId' => (int) $user->getId(),
        ], $ttl);
    }

    public function createToken(array $payload, ?int $ttl = null): string
    {
        $payload['exp'] = time() + max(30, $ttl ?? self::DEFAULT_TTL);
        $encodedPayload = $this->base64UrlEncode((string) json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $encodedPayload, $this->internalToken);

        return sprintf('%s.%s', $encodedPayload, $signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
