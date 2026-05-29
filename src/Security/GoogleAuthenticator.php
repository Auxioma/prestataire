<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\ClientProfile;
use App\Entity\PrestataireProfile;
use App\Enum\ClientTypeEnum;
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
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

/**
 * On hérite de OAuth2Authenticator (fourni par le bundle KnpUniversity)
 * qui gère l'infrastructure de base pour l'authentification externe.
 */
class GoogleAuthenticator extends OAuth2Authenticator
{
    // Permet à Symfony de rediriger l'utilisateur vers la page qu'il essayait d'atteindre avant d'être bloqué
    use TargetPathTrait;

    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;

    // Injection de dépendances classique de Symfony pour récupérer nos services indispensables
    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router)
    {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    /**
     * 1. On indique à Symfony si cet authentificateur doit s'activer pour la requête courante.
     * On répond "true" UNIQUEMENT si l'utilisateur est sur la route de retour 'connect_google_check'.
     */
    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    /**
     * 2. Si supports() a renvoyé true, Symfony exécute cette méthode.
     * C'est ici qu'on récupère les données de Google et qu'on cherche/crée l'utilisateur.
     */
    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client, $request) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email = $googleUser->getEmail();

                // 1. On cherche l'utilisateur en base de données
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                // 2. Si l'utilisateur n'existe pas encore
                if (!$user) {
                    
                    // On vérifie si un rôle est présent en session (Preuve qu'il vient de la page d'inscription)
                    $session = $request->getSession();
                    $chosenRole = $session->get('oauth_registration_role');

                    // 🛑 S'IL N'Y A PAS DE RÔLE EN SESSION : L'utilisateur a cliqué sur "Google" depuis la page Login !
                    if (!$chosenRole) {
                        // On refuse catégoriquement l'authentification avec un message clair
                        throw new CustomUserMessageAuthenticationException(
                            "Aucun compte n'est associé à cette adresse email. Veuillez d'abord vous inscrire."
                        );
                    }

                    // --- SINON : C'est une inscription légitime, on procède à la création ---
                    $user = new User();
                    $user->setEmail($email);
                    $user->setPassword('');
                    $user->setIsVerified(true);
                    $user->setCreatedAt(new \DateTimeImmutable());
                    $user->setUpdatedAt(new \DateTimeImmutable());

                    // Nettoyage de la session
                    $session->remove('oauth_registration_role');

                    if ($chosenRole === 'prestataire') {
                        $user->setRoles(['ROLE_PRESTATAIRE']);

                        $prestataireProfile = new PrestataireProfile();
                        $prestataireProfile->setCompanyName('Nouveau Prestataire (Google)');
                        $prestataireProfile->setSlug('profil-' . uniqid());
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

                // Si l'utilisateur existait déjà (connexion classique), ou s'il vient d'être créé (inscription)
                return $user;
            })
        );
    }

    /**
     * 3. Que fait-on si l'authentification est un succès total ?
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Si l'utilisateur voulait aller sur une page protégée avant, on l'y renvoie
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Sinon, redirection temporaire vers la page d'inscription ou d'accueil
        return new RedirectResponse($this->router->generate('app_home'));
    }

    /**
     * 4. Que fait-on si l'authentification échoue (ex: jeton expiré, refus de l'utilisateur...) ?
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // On traduit ou récupère le message d'erreur
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        /** @var \Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface $flashBag */
        $flashBag = $request->getSession()->getBag('flashes');
        $flashBag->add('danger', 'Erreur d\'authentification Google : ' . $message);

        // On le redirige vers la page de login classique
        return new RedirectResponse($this->router->generate('app_register_choice'));
    }
}
