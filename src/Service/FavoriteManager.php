<?php

namespace App\Service;

use App\Entity\Favorite;
use App\Entity\User;
use App\Enum\FavoriteTypeEnum;
use App\Repository\FavoriteRepository;
use App\Repository\PrestataireProfileRepository;
use App\Repository\PrestataireServiceRepository;
use Doctrine\ORM\EntityManagerInterface;

class FavoriteManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FavoriteRepository $favoriteRepository,
        private readonly PrestataireProfileRepository $prestataireProfileRepository,
        private readonly PrestataireServiceRepository $prestataireServiceRepository,
    ) {
    }

    public function isFavorite(User $user, FavoriteTypeEnum $type, string|int $targetId): bool
    {
        return $this->favoriteRepository->isFavorite($user, $type, $targetId);
    }

    public function add(User $user, FavoriteTypeEnum $type, string|int $targetId): Favorite
    {
        $this->guardTargetExists($type, $targetId);

        $existing = $this->favoriteRepository->findOneByUserTypeAndTarget($user, $type, $targetId);

        if ($existing instanceof Favorite) {
            return $existing;
        }

        $favorite = new Favorite();
        $favorite
            ->setUser($user)
            ->setType($type)
            ->setTargetId($targetId)
        ;

        $this->entityManager->persist($favorite);
        $this->entityManager->flush();

        return $favorite;
    }

    public function remove(User $user, FavoriteTypeEnum $type, string|int $targetId): bool
    {
        $favorite = $this->favoriteRepository->findOneByUserTypeAndTarget($user, $type, $targetId);

        if (!$favorite instanceof Favorite) {
            return false;
        }

        $this->entityManager->remove($favorite);
        $this->entityManager->flush();

        return true;
    }

    public function toggle(User $user, FavoriteTypeEnum $type, string|int $targetId): bool
    {
        $favorite = $this->favoriteRepository->findOneByUserTypeAndTarget($user, $type, $targetId);

        if ($favorite instanceof Favorite) {
            $this->entityManager->remove($favorite);
            $this->entityManager->flush();

            return false;
        }

        $this->guardTargetExists($type, $targetId);

        $favorite = new Favorite();
        $favorite
            ->setUser($user)
            ->setType($type)
            ->setTargetId($targetId)
        ;

        $this->entityManager->persist($favorite);
        $this->entityManager->flush();

        return true;
    }

    /**
     * @return Favorite[]
     */
    public function getByType(User $user, FavoriteTypeEnum $type): array
    {
        return $this->favoriteRepository->findByUserAndType($user, $type);
    }

    private function guardTargetExists(FavoriteTypeEnum $type, string|int $targetId): void
    {
        $exists = match ($type) {
            FavoriteTypeEnum::PRESTATAIRE => null !== $this->prestataireProfileRepository->find($targetId),
            FavoriteTypeEnum::PRESTATION => $this->prestataireServiceRepository->isActivePrestationFavoriteable($targetId),
            FavoriteTypeEnum::BON_PLAN => $this->prestataireServiceRepository->isActiveBonPlanFavoriteable($targetId),
        };

        if (!$exists) {
            throw new \InvalidArgumentException('La cible du favori est introuvable ou indisponible.');
        }
    }
}
