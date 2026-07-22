<?php

namespace App\Service;

use App\Entity\User;
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
