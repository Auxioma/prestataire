<?php

namespace App\Entity;

use App\Enum\FavoriteTypeEnum;
use App\Repository\FavoriteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FavoriteRepository::class)]
#[ORM\Table(name: 'favorite')]
#[ORM\UniqueConstraint(name: 'uniq_user_favorite_target', columns: ['user_id', 'type', 'target_id'])]
#[ORM\Index(name: 'idx_favorite_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_favorite_type', columns: ['type'])]
#[ORM\Index(name: 'idx_favorite_target', columns: ['target_id'])]
class Favorite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(inversedBy: 'favorites')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE', columnDefinition: 'BIGINT NOT NULL')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 30, enumType: FavoriteTypeEnum::class)]
    private ?FavoriteTypeEnum $type = null;

    #[ORM\Column(name: 'target_id', type: Types::BIGINT)]
    private ?string $targetId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getType(): ?FavoriteTypeEnum
    {
        return $this->type;
    }

    public function setType(FavoriteTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTargetId(): ?string
    {
        return $this->targetId;
    }

    public function setTargetId(string|int|null $targetId): static
    {
        $this->targetId = null !== $targetId ? (string) $targetId : null;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isPrestataire(): bool
    {
        return $this->type === FavoriteTypeEnum::PRESTATAIRE;
    }

    public function isPrestation(): bool
    {
        return $this->type === FavoriteTypeEnum::PRESTATION;
    }

    public function isBonPlan(): bool
    {
        return $this->type === FavoriteTypeEnum::BON_PLAN;
    }
}