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

use App\Core\Service\SafeReturnUrlResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class SafeReturnUrlResolverTest extends TestCase
{
    #[DataProvider('unsafeRefererProvider')]
    public function testFallsBackWhenRefererIsNotSafe(?string $referer): void
    {
        $server = [];
        if (null !== $referer) {
            $server['HTTP_REFERER'] = $referer;
        }

        $request = Request::create('https://trouvemoi.example/prestataire/artisan', server: $server);

        self::assertSame('/', (new SafeReturnUrlResolver())->resolve($request, '/'));
    }

    public function testKeepsSameOriginPathAndQuery(): void
    {
        $request = Request::create(
            'https://trouvemoi.example/prestataire/artisan',
            server: ['HTTP_REFERER' => 'https://trouvemoi.example/prestataires?query=plombier&page=2']
        );

        self::assertSame(
            '/prestataires?query=plombier&page=2',
            (new SafeReturnUrlResolver())->resolve($request, '/')
        );
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function unsafeRefererProvider(): iterable
    {
        yield 'missing referer' => [null];
        yield 'external website' => ['https://www.google.fr/search?q=plombier'];
        yield 'same host but another port' => ['https://trouvemoi.example:8443/prestataires'];
        yield 'current page' => ['https://trouvemoi.example/prestataire/artisan'];
        yield 'protocol-relative path' => ['https://trouvemoi.example//evil.example/path'];
        yield 'backslash path' => ['https://trouvemoi.example/\evil.example/path'];
    }
}
