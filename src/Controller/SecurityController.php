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

namespace App\Controller;

use App\Entity\User;
use App\Security\EmailVerifier;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

/**
 * Gère les actions liées à security.
 */
class SecurityController extends AbstractController
{
    public function __construct(
        private readonly EmailVerifier $emailVerifier,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route(path: '/login', name: 'app_login')]
    /**
     * Affiche et traite l’authentification utilisateur.
     *
     * @return Response
     */
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        if ($request->isMethod('GET')) {
            $request->getSession()->remove('oauth_registration_role');
        }

        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/verify/email/resend', name: 'app_resend_verification_email', methods: ['POST'])]
    public function resendVerificationEmail(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('resend_verification_email', (string) $request->request->get('_token'))) {
            $this->addFlash('verify_email_error', 'La demande de renvoi est invalide. Veuillez réessayer.');

            return $this->redirectToRoute('app_login');
        }

        $email = mb_strtolower(trim((string) $request->request->get('email')));

        if ($email !== '') {
            $user = $this->userRepository->findOneBy(['email' => $email]);

            if ($user instanceof User && !$user->isVerified()) {
                $emailWasSent = $this->emailVerifier->trySendEmailConfirmation(
                    'app_verify_email',
                    $user,
                    (new TemplatedEmail())
                        ->from(new Address('contact@trouvemoi.fr', 'TrouveMoi'))
                        ->to((string) $user->getEmail())
                        ->subject('Confirmez votre adresse email')
                        ->htmlTemplate('registration/confirmation_email.html.twig')
                );

                if (!$emailWasSent) {
                    $this->addFlash(
                        'warning',
                        'Si un compte non vérifié existe pour cette adresse, l’email de confirmation n’a pas pu être envoyé pour le moment. Réessayez plus tard.'
                    );

                    return $this->redirectToRoute('app_login');
                }
            }
        }

        $this->addFlash(
            'success',
            'Si un compte non vérifié existe pour cette adresse, un nouvel email de confirmation vient d’être envoyé.'
        );

        return $this->redirectToRoute('app_login');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    /**
     * Déclenche la déconnexion de l’utilisateur.
     *
     * @return void
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
