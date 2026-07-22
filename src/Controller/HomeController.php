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

use App\Entity\User;
use App\Enum\FavoriteTypeEnum;
use App\Form\HomepageSearchType;
use App\Repository\FavoriteRepository;
use App\Repository\PrestataireProfileRepository;
use App\Repository\PrestataireServiceRepository;
use App\Repository\ServiceCategoryRepository;
use App\Service\PrestataireProfileCompletionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
     *
     * @return Response
     */
    public function index(
        Request $request,
        ServiceCategoryRepository $categoryRepository,
        PrestataireProfileRepository $prestataireProfileRepository,
        PrestataireServiceRepository $prestataireServiceRepository,
        FavoriteRepository $favoriteRepository,
    ): Response {
        $homepageSearchForm = $this->createForm(HomepageSearchType::class, null, [
            'action' => $this->generateUrl('app_homepage_search'),
            'method' => 'GET',
        ]);

        $categories = $categoryRepository->findBy([
            'isActive' => true,
            'parent' => null,
        ], [
            'position' => 'ASC',
        ]);

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
                'mandatory_missing_count' => null !== $mandatoryChecklist ? count($mandatoryChecklist['missingItems']) : null,
                'mandatory_is_complete' => null !== $mandatoryChecklist ? $mandatoryChecklist['isComplete'] : null,
                'show_profile_completion_modal' => $showProfileCompletionModal,
            ];
        }

        return $this->render('home/index.html.twig', [
            'homepageSearchForm' => $homepageSearchForm->createView(),
            'categories' => $categories,
            'providers' => $prestataireProfileRepository->findBy([], ['averageRating' => 'DESC'], 4),
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
