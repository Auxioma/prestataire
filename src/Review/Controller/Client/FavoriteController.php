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

namespace App\Review\Controller\Client;

use App\Account\Entity\User;
use App\Review\Enum\FavoriteTypeEnum;
use App\Review\Service\FavoriteManager;
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

        if (!\is_string($typeValue) || '' === mb_trim($typeValue)) {
            return $this->json([
                'success' => false,
                'message' => 'Type de favori manquant.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (
            (!\is_string($targetId) && !\is_int($targetId))
            || '' === mb_trim((string) $targetId)
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
                ? \sprintf('%s ajouté aux favoris.', $type->getLabel())
                : \sprintf('%s retiré des favoris.', $type->getLabel()),
        ]);
    }
}
