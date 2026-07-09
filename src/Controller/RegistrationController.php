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

use App\Entity\ClientProfile;
use App\Entity\PrestataireProfile;
use App\Entity\User;
use App\Enum\ClientTypeEnum;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

/**
 * Gère les actions liées à registration.
 */
class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register/choice', name: 'app_register_choice')]
    /**
     * Traite l’action "choice" du contrôleur Registration.
     *
     * @return Response
     */
    public function choice(): Response
    {
        return $this->render('registration/choice.html.twig');
    }

    #[Route('/register', name: 'app_register')]
    /**
     * Traite l’action "register" du contrôleur Registration.
     *
     * @return Response
     */
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        Security $security,
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        $accountType = $request->query->get('role', 'client');

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // 1. Encodage du mot de passe
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setIsVerified(false);
            $user->setEmailVerifiedAt(null);

            // 2. Attribution du rôle et création du profil selon le paramètre de l'URL
            if ('client' === $accountType) {
                $user->setRoles(['ROLE_CLIENT']);

                $clientProfile = new ClientProfile();
                $clientProfile->setType(ClientTypeEnum::PARTICULIER);
                $clientProfile->setAccount($user);

                $entityManager->persist($clientProfile);
            } elseif ('prestataire' === $accountType) {
                $user->setRoles(['ROLE_PRESTATAIRE']);

                $prestataireProfile = new PrestataireProfile();
                $prestataireProfile->setCompanyName('Nouveau Prestataire');
                $prestataireProfile->setSlug('profil-'.uniqid());
                $prestataireProfile->setAccount($user);

                $entityManager->persist($prestataireProfile);
            }

            // 3. Persistance de l'utilisateur global
            $entityManager->persist($user);
            $entityManager->flush();

            // 4. Envoi de l'email de confirmation
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address('noreply@trouvemoi.com', 'TrouveMoi'))
                    ->to((string) $user->getEmail())
                    ->subject('Veuillez confirmer votre adresse email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            // 5. Connexion automatique immédiate optionnelle
            // $security->login($user, 'form_login', 'main');

            // 6. Redirection vers la page d'accueil
            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    /**
     * Traite l’action "verifyUserEmail" du contrôleur Registration.
     *
     * @return Response
     */
    public function verifyUserEmail(
        Request $request,
        TranslatorInterface $translator,
        UserRepository $userRepository,
    ): Response {
        $user = $userRepository->find($request->query->get('id'));

        if (!$user) {
            throw $this->createNotFoundException();
        }

        try {
            $this->emailVerifier->handleEmailConfirmation(
                $request,
                $user
            );
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash(
                'verify_email_error',
                $translator->trans(
                    $exception->getReason(),
                    [],
                    'VerifyEmailBundle'
                )
            );

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash(
            'success',
            'Votre adresse email a bien été vérifiée.'
        );

        return $this->redirectToRoute('app_login');
    }
}
