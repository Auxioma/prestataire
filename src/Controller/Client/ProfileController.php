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

namespace App\Controller\Client;

use App\Controller\AbstractProfileController;
use App\Entity\ClientProfile;
use App\Entity\User;
use App\Enum\FavoriteTypeEnum;
use App\Form\AccountDeletionType;
use App\Form\AccountPasswordChangeType;
use App\Form\AccountSettingsType;
use App\Repository\FavoriteRepository;
use App\Repository\PrestataireProfileRepository;
use App\Repository\PrestataireServiceRepository;
use App\Service\AccountSecurityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractProfileController
{
    public function __construct(
        AccountSecurityManager $accountSecurityManager,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
    ) {
        parent::__construct($accountSecurityManager, $tokenStorage, $requestStack);
    }

    #[Route('/client/parametres', name: 'app_client_settings')]
    #[IsGranted('ROLE_CLIENT')]
    public function clientSettings(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (null === $user->getClientProfile()) {
            $profile = new ClientProfile();

            $user->setClientProfile($profile);
            $profile->setAccount($user);
        }

        $form = $this->createForm(AccountSettingsType::class, $user, [
            'profile_type' => \in_array('ROLE_PRESTATAIRE', $user->getRoles(), true) ? 'prestataire' : 'client',
        ]);
        $passwordForm = $this->createForm(AccountPasswordChangeType::class, null, [
            'action' => $this->generateUrl('app_client_settings'),
            'method' => 'POST',
        ]);
        $deletionForm = $this->createForm(AccountDeletionType::class, null, [
            'action' => $this->generateUrl('app_client_settings'),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);
        $passwordForm->handleRequest($request);
        $deletionForm->handleRequest($request);
        $activeTab = $request->query->get('tab', 'personal');

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil client a été mis à jour avec succès !');

            return $this->redirectToRoute('app_client_settings');
        }

        if ($response = $this->handlePasswordForm(
            entityManager: $entityManager,
            passwordForm: $passwordForm,
            user: $user,
            redirectRoute: 'app_client_settings',
        )) {
            return $response;
        }

        if ($response = $this->handleDeletionForm(
            entityManager: $entityManager,
            deletionForm: $deletionForm,
            user: $user,
        )) {
            return $response;
        }

        return $this->render('profile/client_profile.html.twig', [
            'settingsForm' => $form->createView(),
            'user' => $user,
            'passwordForm' => $passwordForm->createView(),
            'deletionForm' => $deletionForm->createView(),
            'activeTab' => $this->resolveActiveTab(
                defaultTab: $activeTab,
                availabilityForm: null,
                notificationForm: null,
                passwordForm: $passwordForm,
                deletionForm: $deletionForm,
            ),
        ]);
    }

    #[Route('/client/parametres/favoris', name: 'app_client_settings_favorites', methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT')]
    public function clientFavorites(
        FavoriteRepository $favoriteRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
        PrestataireServiceRepository $prestataireServiceRepository,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $favorites = $favoriteRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        $providerIds = [];
        $prestationIds = [];
        $bonsPlanIds = [];

        foreach ($favorites as $favorite) {
            $type = $favorite->getType();

            if ($type === FavoriteTypeEnum::PRESTATAIRE) {
                $providerIds[] = $favorite->getTargetId();
            }

            if ($type === FavoriteTypeEnum::PRESTATION) {
                $prestationIds[] = $favorite->getTargetId();
            }

            if ($type === FavoriteTypeEnum::BON_PLAN) {
                $bonsPlanIds[] = $favorite->getTargetId();
            }
        }

        $favoriteProviders = !empty($providerIds)
            ? $prestataireProfileRepository->findBy(['id' => array_unique($providerIds)])
            : [];

        $favoritePrestations = !empty($prestationIds)
            ? $prestataireServiceRepository->findBy(['id' => array_unique($prestationIds)])
            : [];

        $favoriteBonsPlans = !empty($bonsPlanIds)
            ? $prestataireServiceRepository->findBy(['id' => array_unique($bonsPlanIds)])
            : [];

        return $this->render('client/client_favorite.html.twig', [
            'user' => $user,
            'favoriteProviders' => $favoriteProviders,
            'favoritePrestations' => $favoritePrestations,
            'favoriteBonsPlans' => $favoriteBonsPlans,
        ]);
    }
}
