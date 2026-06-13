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

#[ORM\Entity(repositoryClass: PrestataireServiceRepository::class)]
#[ORM\Table(name: 'prestataire_profile_service')]
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

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => 0])]
    private ?string $prixCatalogue = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $tauxReduction = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $promotionCreatedAt = null;

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
}
