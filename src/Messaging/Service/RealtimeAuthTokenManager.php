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
        $encodedPayload = $this->base64UrlEncode((string) json_encode($payload, \JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $encodedPayload, $this->internalToken);

        return \sprintf('%s.%s', $encodedPayload, $signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return mb_rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
