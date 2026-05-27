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
    public function __construct(private EmailVerifier $emailVerifier)
    {
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

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // 1. Encodage du mot de passe
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // 2. Récupération du type de compte choisi dans le formulaire
            $accountType = $form->get('accountType')->getData();

            if ($accountType === 'client') {
                $user->setRoles(['ROLE_CLIENT']);

                // Instanciation automatique du profil Client
                $clientProfile = new ClientProfile();
                $clientProfile->setType(ClientTypeEnum::PARTICULIER);
                $clientProfile->setAccount($user);
                
                $entityManager->persist($clientProfile);

            } elseif ($accountType === 'prestataire') {
                $user->setRoles(['ROLE_PRESTATAIRE']);

                // Instanciation automatique du profil Prestataire (les Enums et valeurs par défaut sont gérés dans son constructeur)
                $prestataireProfile = new PrestataireProfile();
                $prestataireProfile->setCompanyName('Nouveau Prestataire'); // Valeur temporaire requise par le NOT NULL
                $prestataireProfile->setSlug('profil-' . uniqid()); // Slug unique temporaire requis
                $prestataireProfile->setAccount($user);
                
                $entityManager->persist($prestataireProfile);
            }

            // 3. Persistance de l'utilisateur global
            $entityManager->persist($user);
            $entityManager->flush();

            // 4. Génération de l'URL signée et envoi de l'email de confirmation
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('noreply@trouvemoi.com', 'TrouveMoi'))
                    ->to((string) $user->getEmail())
                    ->subject('Veuillez confirmer votre adresse email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            // 5. Connexion automatique immédiate avec le pare-feu standard (form_login)
            return $security->login($user, 'form_login', 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        // On exige que l'utilisateur soit connecté pour valider son mail (sécurité renforcée)
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

        // Redirection temporaire sur l'inscription ou la future page d'accueil
        return $this->redirectToRoute('app_register');
    }
}