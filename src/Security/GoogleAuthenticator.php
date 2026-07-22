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

use App\Entity\ClientProfile;
use App\Entity\PrestataireProfile;
use App\Entity\User;
use App\Enum\ClientTypeEnum;
use App\Repository\PrestataireProfileRepository;
use App\Service\PrestataireProfileCompletionService;
use App\Service\UserLoginTracker;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class GoogleAuthenticator extends OAuth2Authenticator
{
    use TargetPathTrait;

    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private UserLoginTracker $userLoginTracker;
    private PrestataireProfileCompletionService $prestataireProfileCompletionService;
    private PrestataireProfileRepository $prestataireProfileRepository;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        UserLoginTracker $userLoginTracker,
        PrestataireProfileCompletionService $prestataireProfileCompletionService,
        PrestataireProfileRepository $prestataireProfileRepository,
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->userLoginTracker = $userLoginTracker;
        $this->prestataireProfileCompletionService = $prestataireProfileCompletionService;
        $this->prestataireProfileRepository = $prestataireProfileRepository;
    }

    public function supports(Request $request): ?bool
    {
        return 'connect_google_check' === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client, $request) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email = $googleUser->getEmail();

                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                if (!$user) {
                    $session = $request->getSession();
                    $chosenRole = $session->get('oauth_registration_role');

                    if (!$chosenRole) {
                        throw new CustomUserMessageAuthenticationException("Aucun compte n'est associé à cette adresse email. Veuillez d'abord vous inscrire.");
                    }

                    $user = new User();
                    $user->setEmail($email);
                    $user->setPassword('');
                    $user->setIsVerified(true);
                    $user->setCreatedAt(new \DateTimeImmutable());
                    $user->setUpdatedAt(new \DateTimeImmutable());

                    $session->remove('oauth_registration_role');

                    if ('prestataire' === $chosenRole) {
                        $user->setRoles(['ROLE_PRESTATAIRE']);

                        $prestataireProfile = new PrestataireProfile();
                        $prestataireProfile->setCompanyName('Nouveau Prestataire (Google)');
                        $prestataireProfile->setSlug('profil-'.uniqid());
                        $prestataireProfile->setAccount($user);

                        $this->entityManager->persist($prestataireProfile);
                    } else {
                        $user->setRoles(['ROLE_CLIENT']);

                        $clientProfile = new ClientProfile();
                        $clientProfile->setType(ClientTypeEnum::PARTICULIER);
                        $clientProfile->setAccount($user);

                        $this->entityManager->persist($clientProfile);
                    }

                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        $redirectParameters = [];

        if ($user instanceof User) {
            $this->userLoginTracker->trackSuccessfulLogin($user);
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
                if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
                    return new RedirectResponse($targetPath);
                }

                return new RedirectResponse($this->router->generate('app_home'));
            }

            $mandatoryChecklist = $this->prestataireProfileCompletionService->buildMandatoryChecklist(
                $user,
                $prestataireProfile
            );

            if (!$mandatoryChecklist['isComplete']) {
                $redirectParameters['onboarding'] = 1;
            }
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('app_home', $redirectParameters));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        /** @var \Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface $flashBag */
        $flashBag = $request->getSession()->getBag('flashes');
        $flashBag->add('danger', 'Erreur d\'authentification Google : '.$message);

        return new RedirectResponse($this->router->generate('app_register_choice'));
    }
}
