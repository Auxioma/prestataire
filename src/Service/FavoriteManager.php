<?php

namespace App\Service;

use App\Entity\Favorite;
use App\Entity\User;
use App\Enum\FavoriteTypeEnum;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;

class FavoriteManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FavoriteRepository $favoriteRepository,
    ) {
    }

    public function isFavorite(User $user, FavoriteTypeEnum $type, string|int $targetId): bool
    {
        return $this->favoriteRepository->isFavorite($user, $type, $targetId);
    }

    public function add(User $user, FavoriteTypeEnum $type, string|int $targetId): Favorite
    {
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
}