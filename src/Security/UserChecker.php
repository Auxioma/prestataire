<?php

/**
 * Copyright(c) 2026 Trouve moi
 *
 * Ce fichier fait partie d’un projet développé par Auxioma Web Agency.
 * Tous droits réservés.
 *
 * Ce code source est la propriété exclusive de Auxioma Web Agency.
 * Toute reproduction, modification, distribution ou utilisation sans autorisation préalable est interdite.
 */

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (null !== $user->getDeletedAt()) {
            throw new CustomUserMessageAccountStatusException('Ce compte a été désinscrit et n’est plus accessible.');
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException('Veuillez vérifier votre adresse email avant de vous connecter.');
        }
    }

    public function checkPostAuth(
        UserInterface $user,
        ?TokenInterface $token = null,
    ): void {
    }
}
