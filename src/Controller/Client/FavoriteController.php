<?php

namespace App\Controller\Client;

use App\Entity\User;
use App\Enum\FavoriteTypeEnum;
use App\Service\FavoriteManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/favoris', name: 'app_favorite_')]
/**
 * Gère les actions liées à favorite.
 */
final class FavoriteController extends AbstractController
{
    #[Route('/toggle', name: 'toggle', methods: ['POST'])]
    /**
     * Traite l’action "toggle" du contrôleur Favorite.
     *
     * @return JsonResponse
     */
    public function toggle(
        Request $request,
        FavoriteManager $favoriteManager,
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'success' => false,
                'message' => 'Vous devez être connecté pour gérer vos favoris.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isGranted('ROLE_CLIENT')) {
            return $this->json([
                'success' => false,
                'message' => 'Accès réservé aux clients.',
            ], Response::HTTP_FORBIDDEN);
        }

        $token = (string) (
            $request->request->get('_token')
            ?? $request->headers->get('X-CSRF-TOKEN')
            ?? ''
        );

        if (!$this->isCsrfTokenValid('favorite_toggle', $token)) {
            return $this->json([
                'success' => false,
                'message' => 'Jeton CSRF invalide.',
            ], Response::HTTP_FORBIDDEN);
        }

        $typeValue = $request->request->get('type');
        $targetId = $request->request->get('targetId');

        if (!is_string($typeValue) || '' === trim($typeValue)) {
            return $this->json([
                'success' => false,
                'message' => 'Type de favori manquant.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (
            (!is_string($targetId) && !is_int($targetId))
            || '' === trim((string) $targetId)
            || !ctype_digit((string) $targetId)
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Cible invalide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $type = FavoriteTypeEnum::tryFrom($typeValue);

        if (!$type instanceof FavoriteTypeEnum) {
            return $this->json([
                'success' => false,
                'message' => 'Type de favori invalide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $isFavorite = $favoriteManager->toggle($user, $type, (string) $targetId);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'isFavorite' => $isFavorite,
            'type' => $type->value,
            'targetId' => (string) $targetId,
            'message' => $isFavorite
                ? sprintf('%s ajouté aux favoris.', $type->getLabel())
                : sprintf('%s retiré des favoris.', $type->getLabel()),
        ]);
    }
}
