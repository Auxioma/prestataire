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
use App\Account\Repository\UserRepository;
use App\Prestataire\Entity\PrestataireProfile;
use Symfony\Bundle\SecurityBundle\Security;

final class AuthenticatedUserProvider
{
    public function __construct(
        private readonly Security $security,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function getAuthenticatedUser(): ?User
    {
        $user = $this->security->getUser();

        if (!$user instanceof User || null === $user->getId()) {
            return null;
        }

        return $this->userRepository->findOneWithProfilesById($user->getId());
    }

    public function getAuthenticatedPrestataireUser(): ?User
    {
        $user = $this->getAuthenticatedUser();

        if (!$user instanceof User || !\in_array('ROLE_PRESTATAIRE', $user->getRoles(), true)) {
            return null;
        }

        return $user;
    }

    public function getAuthenticatedPrestataireProfile(): ?PrestataireProfile
    {
        return $this->getAuthenticatedPrestataireUser()?->getPrestataireProfile();
    }
}
