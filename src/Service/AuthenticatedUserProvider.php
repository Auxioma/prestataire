<?php

namespace App\Service;

use App\Entity\PrestataireProfile;
use App\Entity\User;
use App\Repository\UserRepository;
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

        if (!$user instanceof User || !in_array('ROLE_PRESTATAIRE', $user->getRoles(), true)) {
            return null;
        }

        return $user;
    }

    public function getAuthenticatedPrestataireProfile(): ?PrestataireProfile
    {
        return $this->getAuthenticatedPrestataireUser()?->getPrestataireProfile();
    }
}
