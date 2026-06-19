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

namespace App\Entity;

use App\Repository\PrestataireServiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\PrestationMedia;

#[ORM\Entity(repositoryClass: PrestataireServiceRepository::class)]
#[ORM\Table(
    name: 'prestataire_profile_service',
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'uniq_prestataire_profile_service',
            columns: ['prestataire_profile_id', 'service_id']
        ),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class PrestataireService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PrestataireProfile::class, inversedBy: 'prestataireServices')]
    #[ORM\JoinColumn(name: 'prestataire_profile_id', referencedColumnName: 'id', nullable: false)]
    private ?PrestataireProfile $prestataire = null;

    #[ORM\ManyToOne(targetEntity: Service::class)]
    #[ORM\JoinColumn(name: 'service_id', referencedColumnName: 'id', nullable: false)]
    private ?Service $service = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $pricingType = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $priceFrom = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $priceTo = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $priceUnit = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $additionalInfo = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => 0])]
    private ?string $prixCatalogue = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $tauxReduction = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $promotionCreatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(
        mappedBy: 'prestation',
        targetEntity: PrestationMedia::class,
        orphanRemoval: true,
        cascade: ['persist', 'remove']
    )]
    #[ORM\OrderBy(['position' => 'ASC', 'createdAt' => 'ASC'])]
    private Collection $medias;


    public function __construct()
    {
        $this->medias = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrestataire(): ?PrestataireProfile
    {
        return $this->prestataire;
    }

    public function setPrestataire(?PrestataireProfile $prestataire): self
    {
        $this->prestataire = $prestataire;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): self
    {
        $this->service = $service;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = null !== $title ? trim($title) : null;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): self
    {
        $this->shortDescription = null !== $shortDescription ? trim($shortDescription) : null;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = null !== $description ? trim($description) : null;

        return $this;
    }

    public function getPricingType(): ?string
    {
        return $this->pricingType;
    }

    public function setPricingType(?string $pricingType): self
    {
        $this->pricingType = null !== $pricingType ? trim($pricingType) : null;

        return $this;
    }

    public function getPriceFrom(): ?string
    {
        return $this->priceFrom;
    }

    public function setPriceFrom(?string $priceFrom): self
    {
        $this->priceFrom = $priceFrom;

        return $this;
    }

    public function getPriceTo(): ?string
    {
        return $this->priceTo;
    }

    public function setPriceTo(?string $priceTo): self
    {
        $this->priceTo = $priceTo;

        return $this;
    }

    public function getPriceUnit(): ?string
    {
        return $this->priceUnit;
    }

    public function setPriceUnit(?string $priceUnit): self
    {
        $this->priceUnit = null !== $priceUnit ? trim($priceUnit) : null;

        return $this;
    }

    public function getAdditionalInfo(): ?string
    {
        return $this->additionalInfo;
    }

    public function setAdditionalInfo(?string $additionalInfo): self
    {
        $this->additionalInfo = null !== $additionalInfo ? trim($additionalInfo) : null;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getPrixCatalogue(): ?string
    {
        return $this->prixCatalogue;
    }

    public function setPrixCatalogue(string $prixCatalogue): self
    {
        $this->prixCatalogue = $prixCatalogue;

        return $this;
    }

    public function getTauxReduction(): ?string
    {
        return $this->tauxReduction;
    }

    public function setTauxReduction(?string $tauxReduction): self
    {
        $this->tauxReduction = $tauxReduction;

        return $this;
    }

    public function getPromotionCreatedAt(): ?\DateTimeImmutable
    {
        return $this->promotionCreatedAt;
    }

    public function setPromotionCreatedAt(?\DateTimeImmutable $promotionCreatedAt): self
    {
        $this->promotionCreatedAt = $promotionCreatedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, PrestationMedia>
     */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedia(PrestationMedia $media): static
    {
        if (!$this->medias->contains($media)) {
            $this->medias->add($media);
            $media->setPrestation($this);
        }

        return $this;
    }

    public function removeMedia(PrestationMedia $media): static
    {
        $this->medias->removeElement($media);

        return $this;
    }

    public function getPrixRemise(): ?float
    {
        if (null === $this->prixCatalogue) {
            return null;
        }

        $prix = (float) $this->prixCatalogue;
        $taux = (float) ($this->tauxReduction ?? 0);

        if ($taux <= 0) {
            return $prix;
        }

        return round($prix * (1 - ($taux / 100)), 2);
    }

    public function hasPromotion(): bool
    {
        return null !== $this->tauxReduction && (float) $this->tauxReduction > 0;
    }

    public function hasDetailedOffer(): bool
    {
        return (null !== $this->title && '' !== $this->title)
            || (null !== $this->shortDescription && '' !== $this->shortDescription)
            || (null !== $this->description && '' !== $this->description)
            || (null !== $this->pricingType && '' !== $this->pricingType)
            || null !== $this->priceFrom
            || null !== $this->priceTo
            || (null !== $this->priceUnit && '' !== $this->priceUnit)
            || (null !== $this->additionalInfo && '' !== $this->additionalInfo);
    }

    public function getDisplayTitle(): string
    {
        if (null !== $this->title && '' !== trim($this->title)) {
            return $this->title;
        }

        return $this->service?->getName() ?? 'Service';
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();

        if (null === $this->createdAt) {
            $this->createdAt = $now;
        }

        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
