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

namespace App\Website\Controller;

use App\Account\Entity\User;
use App\Catalog\Entity\ServiceCategory;
use App\Catalog\Repository\ServiceCategoryRepository;
use App\Prestataire\Entity\PrestataireProfile;
use App\Prestataire\Repository\PrestataireProfileRepository;
use App\Prestataire\Repository\PrestataireServiceRepository;
use App\Prestataire\Service\PrestataireProfileCompletionService;
use App\Review\Enum\FavoriteTypeEnum;
use App\Review\Repository\FavoriteRepository;
use App\Search\Form\HomepageSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Gère les actions liées à home.
 */
class HomeController extends AbstractController
{
    public function __construct(
        private readonly PrestataireProfileCompletionService $prestataireProfileCompletionService,
    ) {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    /**
     * Affiche la page principale de ce contrôleur.
     */
    public function index(
        Request $request,
        ServiceCategoryRepository $categoryRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
        PrestataireServiceRepository $prestataireServiceRepository,
        FavoriteRepository $favoriteRepository,
        CacheInterface $cache,
    ): Response {
        $homepageSearchForm = $this->createForm(HomepageSearchType::class, null, [
            'action' => $this->generateUrl('app_homepage_search'),
            'method' => 'GET',
        ]);

        $categories = $cache->get('homepage.categories.v1', static function (ItemInterface $item) use ($categoryRepository): array {
            $item->expiresAfter(3600);

            return array_map(
                static fn (ServiceCategory $category): array => [
                    'name' => $category->getName(),
                    'slug' => $category->getSlug(),
                    'icon' => $category->getIcon(),
                ],
                $categoryRepository->findBy([
                    'isActive' => true,
                    'parent' => null,
                ], [
                    'position' => 'ASC',
                ])
            );
        });

        $providers = $cache->get('homepage.providers.v1', static function (ItemInterface $item) use ($prestataireProfileRepository): array {
            $item->expiresAfter(3600);

            return array_map(
                static fn (PrestataireProfile $provider): array => [
                    'id' => $provider->getId(),
                    'slug' => $provider->getSlug(),
                    'companyName' => $provider->getCompanyName(),
                    'coverImage' => $provider->getCoverImage(),
                    'averageRating' => $provider->getAverageRating(),
                    'reviewsCount' => $provider->getReviewsCount(),
                    'city' => $provider->getCity(),
                    'metier' => $provider->getMetier(),
                ],
                $prestataireProfileRepository->findBy([], ['averageRating' => 'DESC'], 4)
            );
        });

        $favoriteProviderIds = [];
        $favoriteBonPlanIds = [];
        $user = $this->getUser();
        $showProfileCompletionModal = false;
        $mandatoryChecklist = null;
        $profileCompletionSettingsUrl = null;
        $profileCompletionDebug = null;

        if ($user instanceof User && $this->isGranted('ROLE_CLIENT')) {
            $favoriteProviderIds = $favoriteRepository->findTargetIdsByUserAndType($user, FavoriteTypeEnum::PRESTATAIRE);
            $favoriteBonPlanIds = $favoriteRepository->findTargetIdsByUserAndType($user, FavoriteTypeEnum::BON_PLAN);
        }

        $shouldShowProfileCompletionModal = '1' === (string) $request->query->get('onboarding', '');

        if (
            $user instanceof User
            && $this->isGranted('ROLE_PRESTATAIRE')
            && $shouldShowProfileCompletionModal
        ) {
            $prestataireProfile = $prestataireProfileRepository->findOneBy([
                'account' => $user,
            ]);

            if (null === $prestataireProfile) {
                $shouldShowProfileCompletionModal = false;
            }
        }

        if (
            $user instanceof User
            && $this->isGranted('ROLE_PRESTATAIRE')
            && $shouldShowProfileCompletionModal
            && isset($prestataireProfile)
        ) {
            $mandatoryChecklist = $this->prestataireProfileCompletionService->buildMandatoryChecklist(
                $user,
                $prestataireProfile
            );

            if (!$mandatoryChecklist['isComplete']) {
                $showProfileCompletionModal = true;

                $target = $mandatoryChecklist['missingItems'][0] ?? null;
                $parameters = [
                    'tab' => $target['tab'] ?? 'profile',
                ];

                if (isset($target['fragment']) && null !== $target['fragment']) {
                    $parameters['_fragment'] = $target['fragment'];
                }

                $profileCompletionSettingsUrl = $this->generateUrl('app_prestataire_settings', $parameters);
            }
        }

        if ($user instanceof User && $this->isGranted('ROLE_PRESTATAIRE')) {
            $profileCompletionDebug = [
                'user_id' => $user->getId(),
                'login_count' => $user->getLoginCount(),
                'has_role_prestataire' => \in_array('ROLE_PRESTATAIRE', $user->getRoles(), true),
                'onboarding_query_received' => $shouldShowProfileCompletionModal,
                'prestataire_profile_found' => isset($prestataireProfile) && null !== $prestataireProfile,
                'mandatory_checklist_built' => null !== $mandatoryChecklist,
                'mandatory_missing_count' => null !== $mandatoryChecklist ? \count($mandatoryChecklist['missingItems']) : null,
                'mandatory_is_complete' => null !== $mandatoryChecklist ? $mandatoryChecklist['isComplete'] : null,
                'show_profile_completion_modal' => $showProfileCompletionModal,
            ];
        }

        return $this->render('Website/home/index.html.twig', [
            'homepageSearchForm' => $homepageSearchForm->createView(),
            'categories' => $categories,
            'providers' => $providers,
            'bonsPlans' => $prestataireServiceRepository->findLatestBonsPlansForHome(4),
            'favoriteProviderIds' => $favoriteProviderIds,
            'favoriteBonPlanIds' => $favoriteBonPlanIds,
            'showProfileCompletionModal' => $showProfileCompletionModal,
            'mandatoryChecklist' => $mandatoryChecklist,
            'profileCompletionSettingsUrl' => $profileCompletionSettingsUrl,
            'profileCompletionDebug' => $profileCompletionDebug,
        ]);
    }
}
