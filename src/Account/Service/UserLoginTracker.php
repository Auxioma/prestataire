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

namespace App\Account\Service;

use App\Account\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class UserLoginTracker
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function trackSuccessfulLogin(User $user): void
    {
        $currentLoginCount = max(0, (int) ($user->getLoginCount() ?? 0));

        $user
            ->setLoginCount($currentLoginCount + 1)
            ->setLastLoginAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
