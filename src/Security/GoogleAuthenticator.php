<?php

namespace App\Security;

use App\Entity\User;
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
        // On récupère le client HTTP configuré pour Google
        $client = $this->clientRegistry->getClient('google');

        // Le bundle va échanger le code reçu dans l'URL contre un "Access Token" (un jeton d'accès sécurisé)
        $accessToken = $this->fetchAccessToken($client);

        // On retourne un Passport auto-validé (pas besoin de vérifier de mot de passe, Google l'a fait pour nous)
        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                // On utilise le jeton pour récupérer l'objet utilisateur officiel contenant les infos de Google
                $googleUser = $client->fetchUserFromToken($accessToken);

                // Extraction de l'adresse email
                $email = $googleUser->getEmail();

                // On cherche si un utilisateur possède déjà cet email dans notre base Postgres
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                // Si l'utilisateur n'existe pas encore, on le crée AUTOMATIQUEMENT à la volée !
                if (!$user) {
                    $user = new User();
                    $user->setEmail($email);

                    // Rôle par défaut (on pourra affiner ou le rediriger plus tard pour choisir son profil dédié)
                    $user->setRoles(['ROLE_CLIENT']);

                    // Pas de mot de passe physique puisque c'est Google qui gère la sécurité.
                    // On laisse une chaîne vide, Symfony s'en accommode parfaitement.
                    $user->setPassword('');

                    // Initialisation des dates obligatoires de ton entité
                    $user->setCreatedAt(new \DateTimeImmutable());
                    $user->setUpdatedAt(new \DateTimeImmutable());

                    // Enregistrement en base de données
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                }

                // On renvoie l'entité User (existante ou créée). Symfony valide alors la connexion !
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
        return new RedirectResponse($this->router->generate('app_register'));
    }

    /**
     * 4. Que fait-on si l'authentification échoue (ex: jeton expiré, refus de l'utilisateur...) ?
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // On traduit ou récupère le message d'erreur
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        /** @var \Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface $flashBag */
        // 🚀 Correction moderne pour Symfony : on récupère le sac de flashs proprement
        $flashBag = $request->getSession()->getBag('flashes');
        $flashBag->add('danger', 'Erreur d\'authentification Google : ' . $message);

        // On le redirige vers la page de login classique
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
