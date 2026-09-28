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

namespace App\Account\Security;

use App\Account\Entity\User;
use App\Account\Service\UserLoginTracker;
use App\Prestataire\Repository\PrestataireProfileRepository;
use App\Prestataire\Service\PrestataireProfileCompletionService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UserLoginTracker $userLoginTracker,
        private readonly PrestataireProfileCompletionService $prestataireProfileCompletionService,
        private readonly PrestataireProfileRepository $prestataireProfileRepository,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        $redirectParameters = [];

        if ($user instanceof User) {
            $this->userLoginTracker->trackSuccessfulLogin($user);
        }

        if (\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse('/admin/user');
        }

        if (
            $user instanceof User
            && \in_array('ROLE_PRESTATAIRE', $user->getRoles(), true)
            && 1 === (int) ($user->getLoginCount() ?? 0)
        ) {
            $prestataireProfile = $this->prestataireProfileRepository->findOneBy([
                'account' => $user,
            ]);

            if (null === $prestataireProfile) {
                return new RedirectResponse($this->urlGenerator->generate('app_home'));
            }

            $mandatoryChecklist = $this->prestataireProfileCompletionService->buildMandatoryChecklist(
                $user,
                $prestataireProfile
            );

            if (!$mandatoryChecklist['isComplete']) {
                $redirectParameters['onboarding'] = 1;
            }
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home', $redirectParameters));
    }
}
