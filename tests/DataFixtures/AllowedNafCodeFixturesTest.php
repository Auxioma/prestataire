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

namespace App\Tests\DataFixtures;

use App\Catalog\DataFixtures\AllowedNafCodeFixtures;
use App\Catalog\Entity\AllowedNafCode;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;

final class AllowedNafCodeFixturesTest extends TestCase
{
    public function testLoadsUniqueCodesAndUpdatesExistingOne(): void
    {
        $existingCode = (new AllowedNafCode())
            ->setCode('43.21A')
            ->setLabel('Ancien libellé')
            ->setIsActive(false);

        $repository = $this->createStub(ObjectRepository::class);
        $repository->method('findAll')->willReturn([$existingCode]);

        $persisted = [];
        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects(self::once())
            ->method('getRepository')
            ->with(AllowedNafCode::class)
            ->willReturn($repository);
        $manager
            ->expects(self::exactly(95))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $manager->expects(self::once())->method('flush');

        (new AllowedNafCodeFixtures())->load($manager);

        self::assertCount(95, $persisted);
        self::assertContainsOnlyInstancesOf(AllowedNafCode::class, $persisted);

        $codes = array_map(
            static fn (AllowedNafCode $allowedNafCode): ?string => $allowedNafCode->getCode(),
            $persisted
        );
        self::assertCount(95, array_unique($codes));
        self::assertContains('41.20A', $codes);
        self::assertContains('90.02Z', $codes);
        self::assertSame('Installation électrique dans tous locaux', $existingCode->getLabel());
        self::assertTrue($existingCode->isActive());

        foreach ($codes as $code) {
            self::assertMatchesRegularExpression('/^\d{2}\.\d{2}[A-Z]$/', (string) $code);
        }
    }
}
