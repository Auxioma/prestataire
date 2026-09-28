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

namespace App\Account\Controller\Client;

use App\Account\Controller\AbstractProfileController;
use App\Account\Entity\ClientProfile;
use App\Account\Entity\User;
use App\Account\Form\AccountDeletionType;
use App\Account\Form\AccountPasswordChangeType;
use App\Account\Form\AccountSettingsType;
use App\Account\Service\AccountSecurityManager;
use App\Messaging\Form\ClientNotificationPreferencesType;
use App\Prestataire\Repository\PrestataireProfileRepository;
use App\Prestataire\Repository\PrestataireServiceRepository;
use App\Review\Enum\FavoriteTypeEnum;
use App\Review\Repository\FavoriteRepository;
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

        $notificationForm = $this->createForm(
            ClientNotificationPreferencesType::class,
            $user,
            [
                'action' => $this->generateUrl('app_client_settings'),
                'method' => 'POST',
            ]
        );

        $form->handleRequest($request);
        $passwordForm->handleRequest($request);
        $notificationForm->handleRequest($request);
        $deletionForm->handleRequest($request);
        $activeTab = $request->query->get('tab', 'personal');

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil client a été mis à jour avec succès !');

            return $this->redirectToRoute('app_client_settings');
        }

        if ($response = $this->handleNotificationForm(
            entityManager: $entityManager,
            notificationForm: $notificationForm,
            user: $user,
            redirectRoute: 'app_client_settings',
        )) {
            return $response;
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

        return $this->render('Account/profile/client_profile.html.twig', [
            'settingsForm' => $form->createView(),
            'user' => $user,
            'notificationForm' => $notificationForm->createView(),
            'passwordForm' => $passwordForm->createView(),
            'deletionForm' => $deletionForm->createView(),
            'activeTab' => $this->resolveActiveTab(
                defaultTab: $activeTab,
                availabilityForm: null,
                notificationForm: $notificationForm,
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

            if (FavoriteTypeEnum::PRESTATAIRE === $type) {
                $providerIds[] = $favorite->getTargetId();
            }

            if (FavoriteTypeEnum::PRESTATION === $type) {
                $prestationIds[] = $favorite->getTargetId();
            }

            if (FavoriteTypeEnum::BON_PLAN === $type) {
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

        return $this->render('review/favorite/client_favorite.html.twig', [
            'user' => $user,
            'favoriteProviders' => $favoriteProviders,
            'favoritePrestations' => $favoritePrestations,
            'favoriteBonsPlans' => $favoriteBonsPlans,
        ]);
    }
}
