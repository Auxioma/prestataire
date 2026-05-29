<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\ClientProfile;
use App\Entity\PrestataireProfile;
use App\Enum\ClientTypeEnum;
use App\Form\RegistrationFormType;
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

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier) {}

    #[Route('/register/choice', name: 'app_register_choice')]
    public function choice(): Response
    {
        return $this->render('registration/choice.html.twig');
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        // On récupère le rôle directement depuis l'URL (?role=...)
        $accountType = $request->query->get('role', 'client');

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // 1. Encodage du mot de passe
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // 2. Attribution du rôle et création du profil selon le paramètre de l'URL
            if ($accountType === 'client') {
                $user->setRoles(['ROLE_CLIENT']);

                $clientProfile = new ClientProfile();
                $clientProfile->setType(ClientTypeEnum::PARTICULIER);
                $clientProfile->setAccount($user);

                $entityManager->persist($clientProfile);
            } elseif ($accountType === 'prestataire') {
                $user->setRoles(['ROLE_PRESTATAIRE']);

                $prestataireProfile = new PrestataireProfile();
                $prestataireProfile->setCompanyName('Nouveau Prestataire');
                $prestataireProfile->setSlug('profil-' . uniqid());
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

            // 5. Connexion automatique immédiate
            $security->login($user, 'form_login', 'main');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            /** @var User $user */
            $user = $this->getUser();
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Votre adresse email a bien été vérifiée.');

        // MODIFICATION ICI : Une fois l'email validé, on l'envoie sur l'accueil plutôt que sur le formulaire d'inscription
        return $this->redirectToRoute('app_home');
    }
}