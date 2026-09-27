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

namespace App\Account\Controller;

use App\Account\Entity\User;
use App\Account\Service\AccountSecurityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class AbstractProfileController extends AbstractController
{
    public function __construct(
        private readonly AccountSecurityManager $accountSecurityManager,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RequestStack $requestStack,
    ) {
    }

    protected function handlePasswordForm(
        EntityManagerInterface $entityManager,
        FormInterface $passwordForm,
        User $user,
        string $redirectRoute,
    ): ?Response {
        if (!$passwordForm->isSubmitted() || !$passwordForm->isValid()) {
            return null;
        }

        $plainPassword = $passwordForm->get('plainPassword')->getData();
        \assert(\is_string($plainPassword));

        $this->accountSecurityManager->changePassword($user, $plainPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Votre mot de passe a bien été mis à jour.');

        return $this->redirectToRoute($redirectRoute, [
            'tab' => 'security',
        ]);
    }

    protected function handleDeletionForm(
        EntityManagerInterface $entityManager,
        FormInterface $deletionForm,
        User $user,
    ): ?Response {
        if (!$deletionForm->isSubmitted() || !$deletionForm->isValid()) {
            return null;
        }

        $this->accountSecurityManager->softDelete($user);

        $entityManager->persist($user);
        $entityManager->flush();

        $this->tokenStorage->setToken(null);

        $request = $this->requestStack->getCurrentRequest();
        if ($request?->hasSession()) {
            $request->getSession()->invalidate();
        }

        $response = new RedirectResponse($this->generateUrl('app_home'));
        $response->headers->clearCookie('REMEMBERME', '/');

        if (\function_exists('session_name')) {
            $sessionCookieName = session_name();
            if (\is_string($sessionCookieName) && '' !== $sessionCookieName) {
                $response->headers->clearCookie($sessionCookieName, '/');
            }
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');

        return $response;
    }

    protected function handleNotificationForm(
        EntityManagerInterface $entityManager,
        FormInterface $notificationForm,
        User $user,
        string $redirectRoute,
    ): ?Response {
        if (!$notificationForm->isSubmitted() || !$notificationForm->isValid()) {
            return null;
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Vos préférences de notifications ont bien été enregistrées.');

        return $this->redirectToRoute($redirectRoute, [
            'tab' => 'notif',
        ]);
    }

    protected function resolveActiveTab(
        string $defaultTab,
        ?FormInterface $availabilityForm,
        ?FormInterface $notificationForm,
        FormInterface $passwordForm,
        FormInterface $deletionForm,
    ): string {
        if (null !== $availabilityForm && $availabilityForm->isSubmitted() && !$availabilityForm->isValid()) {
            return 'dispo';
        }

        if (null !== $notificationForm && $notificationForm->isSubmitted() && !$notificationForm->isValid()) {
            return 'notif';
        }

        if (($passwordForm->isSubmitted() && !$passwordForm->isValid()) || ($deletionForm->isSubmitted() && !$deletionForm->isValid())) {
            return 'security';
        }

        return $defaultTab;
    }
}
