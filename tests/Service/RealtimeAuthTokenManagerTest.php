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

namespace App\Tests\Service;

use App\Account\Entity\User;
use App\Messaging\Service\RealtimeAuthTokenManager;
use PHPUnit\Framework\TestCase;

final class RealtimeAuthTokenManagerTest extends TestCase
{
    public function testCreatesConversationTokenFromBigintStringIdentifier(): void
    {
        $manager = new RealtimeAuthTokenManager('test-internal-token');

        $token = $manager->createConversationToken('42', new User(), 60);

        [$encodedPayload, $signature] = explode('.', $token, 2);
        $decodedPayload = base64_decode(strtr($encodedPayload, '-_', '+/'), true);

        self::assertNotFalse($decodedPayload);
        self::assertSame(
            hash_hmac('sha256', $encodedPayload, 'test-internal-token'),
            $signature
        );

        $payload = json_decode($decodedPayload, true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame('conversation', $payload['type']);
        self::assertSame('42', $payload['conversationId']);
        self::assertArrayHasKey('exp', $payload);
    }
}
