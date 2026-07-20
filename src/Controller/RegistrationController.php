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
use App\Entity\PrestataireService;
use App\Entity\Service;
use App\Entity\User;
use App\Enum\ClientTypeEnum;
use App\Form\RegistrationFormType;
use App\Repository\ServiceCategoryRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\PrestataireProfileCompletionService;
use App\Service\Subscription\PrestataireSubscriptionOnboardingManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

/**
 * Gère les actions liées à registration.
 */
class RegistrationController extends AbstractController
{
    private const PRESTATAIRE_REGISTRATION_STEP_ONE = 'prestataire_registration.step_one';
    private const PRESTATAIRE_REGISTRATION_STEP_TWO = 'prestataire_registration.step_two';

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
        ServiceCategoryRepository $serviceCategoryRepository,
        ServiceRepository $serviceRepository,
        SluggerInterface $slugger,
        PrestataireProfileCompletionService $prestataireProfileCompletionService,
        PrestataireSubscriptionOnboardingManager $prestataireSubscriptionOnboardingManager,
    ): Response {
        $accountType = $this->resolveAccountType((string) $request->query->get('role', $request->request->get('role', 'client')));

        if ('prestataire' === $accountType) {
            return $this->handlePrestataireRegistration(
                $request,
                $userPasswordHasher,
                $entityManager,
                $serviceCategoryRepository,
                $serviceRepository,
                $slugger,
                $prestataireProfileCompletionService,
                $prestataireSubscriptionOnboardingManager,
            );
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'action' => $this->generateUrl('app_register', ['role' => 'client']),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setIsVerified(false);
            $user->setEmailVerifiedAt(null);
            $user->setRoles(['ROLE_CLIENT']);

            $clientProfile = new ClientProfile();
            $clientProfile->setType(ClientTypeEnum::PARTICULIER);
            $clientProfile->setAccount($user);
            $user->setClientProfile($clientProfile);

            $entityManager->persist($clientProfile);
            $entityManager->persist($user);
            $entityManager->flush();

            $this->sendRegistrationConfirmationEmail($user);
            $this->addFlash(
                'registration_email_confirmation_notice',
                'Votre compte a bien été créé. Vérifiez votre boîte mail et confirmez votre adresse email pour finaliser votre inscription.'
            );

            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'accountType' => $accountType,
            'prestataireStep' => null,
            'categories' => [],
            'selectedService' => null,
            'activityError' => null,
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

    private function handlePrestataireRegistration(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        ServiceCategoryRepository $serviceCategoryRepository,
        ServiceRepository $serviceRepository,
        SluggerInterface $slugger,
        PrestataireProfileCompletionService $prestataireProfileCompletionService,
        PrestataireSubscriptionOnboardingManager $prestataireSubscriptionOnboardingManager,
    ): Response {
        $session = $request->getSession();
        $step = max(1, min(3, (int) $request->query->get('step', $request->request->get('step', 1))));
        $stepOneData = $session->get(self::PRESTATAIRE_REGISTRATION_STEP_ONE);
        $stepTwoData = $session->get(self::PRESTATAIRE_REGISTRATION_STEP_TWO);

        if (1 === $step && !$request->isMethod('POST') && '1' === $request->query->get('reset')) {
            $session->remove(self::PRESTATAIRE_REGISTRATION_STEP_ONE);
            $session->remove(self::PRESTATAIRE_REGISTRATION_STEP_TWO);
            $stepOneData = null;
            $stepTwoData = null;
        }

        if ($step > 1 && !\is_array($stepOneData)) {
            return $this->redirectToRoute('app_register', ['role' => 'prestataire', 'step' => 1]);
        }

        if (3 === $step && !\is_array($stepTwoData)) {
            return $this->redirectToRoute('app_register', ['role' => 'prestataire', 'step' => 2]);
        }

        if (1 === $step) {
            $user = new User();

            if (\is_array($stepOneData)) {
                $user
                    ->setFirstName($stepOneData['firstName'] ?? null)
                    ->setLastName($stepOneData['lastName'] ?? null)
                    ->setPhoneNumber($stepOneData['phoneNumber'] ?? null)
                    ->setEmail($stepOneData['email'] ?? '');
            }

            $form = $this->createForm(RegistrationFormType::class, $user, [
                'action' => $this->generateUrl('app_register', ['role' => 'prestataire', 'step' => 1]),
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                /** @var string $plainPassword */
                $plainPassword = $form->get('plainPassword')->getData();

                $session->set(self::PRESTATAIRE_REGISTRATION_STEP_ONE, [
                    'firstName' => trim((string) $user->getFirstName()),
                    'lastName' => trim((string) $user->getLastName()),
                    'phoneNumber' => trim((string) $user->getPhoneNumber()),
                    'email' => mb_strtolower(trim((string) $user->getEmail())),
                    'plainPassword' => $plainPassword,
                ]);
                $session->remove(self::PRESTATAIRE_REGISTRATION_STEP_TWO);

                return $this->redirectToRoute('app_register', ['role' => 'prestataire', 'step' => 2]);
            }

            return $this->render('registration/register.html.twig', [
                'registrationForm' => $form,
                'accountType' => 'prestataire',
                'prestataireStep' => 1,
                'categories' => [],
                'selectedService' => null,
                'activityError' => null,
            ]);
        }

        if (2 === $step) {
            $serviceId = trim((string) $request->request->get('service_id'));
            $activityError = null;
            $selectedService = null;

            if (\is_array($stepTwoData) && isset($stepTwoData['serviceId'])) {
                $selectedService = $serviceRepository->find($stepTwoData['serviceId']);
            }

            if ($request->isMethod('POST')) {
                if (!$this->isCsrfTokenValid('prestataire_registration_activity', (string) $request->request->get('_token'))) {
                    $activityError = 'Le jeton de sécurité est invalide. Veuillez réessayer.';
                } else {
                    $service = ctype_digit($serviceId) ? $serviceRepository->find((int) $serviceId) : null;

                    if (!$service instanceof Service || !$service->isActive() || !$service->getCategory()?->isActive() || !$service->getCategory()?->getParent()?->isActive()) {
                        $activityError = 'Veuillez sélectionner un service valide.';
                    } else {
                        $session->set(self::PRESTATAIRE_REGISTRATION_STEP_TWO, [
                            'serviceId' => $service->getId(),
                        ]);

                        return $this->redirectToRoute('app_register', ['role' => 'prestataire', 'step' => 3]);
                    }
                }

                if (ctype_digit($serviceId)) {
                    $selectedService = $serviceRepository->find((int) $serviceId);
                }
            }

            return $this->render('registration/register.html.twig', [
                'registrationForm' => null,
                'accountType' => 'prestataire',
                'prestataireStep' => 2,
                'categories' => $serviceCategoryRepository->findBy([
                    'parent' => null,
                    'isActive' => true,
                ], ['position' => 'ASC']),
                'selectedService' => $selectedService,
                'activityError' => $activityError,
            ]);
        }

        if (!$request->isMethod('POST')) {
            return $this->render('registration/register.html.twig', [
                'registrationForm' => null,
                'accountType' => 'prestataire',
                'prestataireStep' => 3,
                'categories' => [],
                'selectedService' => isset($stepTwoData['serviceId']) ? $serviceRepository->find($stepTwoData['serviceId']) : null,
                'activityError' => null,
            ]);
        }

        if (!$this->isCsrfTokenValid('prestataire_registration_finalize', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Le jeton de sécurité est invalide. Veuillez réessayer.');

            return $this->redirectToRoute('app_register', ['role' => 'prestataire', 'step' => 3]);
        }

        $selectedService = isset($stepTwoData['serviceId']) ? $serviceRepository->find($stepTwoData['serviceId']) : null;

        if (!$selectedService instanceof Service || !$selectedService->isActive()) {
            $this->addFlash('danger', 'Le service sélectionné n’est plus disponible.');

            return $this->redirectToRoute('app_register', ['role' => 'prestataire', 'step' => 2]);
        }

        $user = new User();
        $user
            ->setFirstName($stepOneData['firstName'] ?? null)
            ->setLastName($stepOneData['lastName'] ?? null)
            ->setPhoneNumber($stepOneData['phoneNumber'] ?? null)
            ->setEmail((string) ($stepOneData['email'] ?? ''))
            ->setPassword($userPasswordHasher->hashPassword($user, (string) ($stepOneData['plainPassword'] ?? '')))
            ->setIsVerified(false)
            ->setEmailVerifiedAt(null)
            ->setRoles(['ROLE_PRESTATAIRE']);

        $prestataireProfile = new PrestataireProfile();
        $prestataireProfile
            ->setCompanyName('Nouveau Prestataire')
            ->setSlug($this->generateProfileSlug())
            ->setAccount($user);
        $user->setPrestataireProfile($prestataireProfile);

        $prestataireService = new PrestataireService();
        $prestataireService
            ->setPrestataire($prestataireProfile)
            ->setService($selectedService)
            ->setIsActive(true)
            ->setSlug($this->generateServiceSlug($slugger, $selectedService));

        $entityManager->persist($user);
        $entityManager->persist($prestataireProfile);
        $entityManager->persist($prestataireService);
        $prestataireProfileCompletionService->syncCompletionScore($user, $prestataireProfile);
        $entityManager->persist($prestataireProfile);

        try {
            $prestataireSubscriptionOnboardingManager->assignFreePlanToNewPrestataire($prestataireProfile);
        } catch (\RuntimeException $exception) {
            $this->addFlash('danger', $exception->getMessage());

            return $this->redirectToRoute('app_register', ['role' => 'prestataire', 'step' => 3]);
        }

        $entityManager->flush();

        $this->sendRegistrationConfirmationEmail($user);

        $session->remove(self::PRESTATAIRE_REGISTRATION_STEP_ONE);
        $session->remove(self::PRESTATAIRE_REGISTRATION_STEP_TWO);

        $this->addFlash(
            'registration_email_confirmation_notice',
            'Votre compte prestataire a bien été créé. Vérifiez votre boîte mail et confirmez votre adresse email pour finaliser votre inscription.'
        );

        return $this->redirectToRoute('app_home');
    }

    private function sendRegistrationConfirmationEmail(User $user): void
    {
        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            (new TemplatedEmail())
                ->from(new Address('noreply@trouvemoi.com', 'TrouveMoi'))
                ->to((string) $user->getEmail())
                ->subject('Veuillez confirmer votre adresse email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }

    private function resolveAccountType(string $accountType): string
    {
        return \in_array($accountType, ['client', 'prestataire'], true) ? $accountType : 'client';
    }

    private function generateProfileSlug(): string
    {
        return sprintf('profil-%s', substr(bin2hex(random_bytes(8)), 0, 12));
    }

    private function generateServiceSlug(SluggerInterface $slugger, Service $service): string
    {
        $baseSlug = (string) $slugger->slug($service->getName() ?: 'prestation')->lower();

        return sprintf('%s-%s', $baseSlug, substr(bin2hex(random_bytes(4)), 0, 8));
    }
}
