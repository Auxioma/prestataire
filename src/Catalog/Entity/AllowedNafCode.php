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

namespace App\Catalog\Entity;

use App\Catalog\Repository\AllowedNafCodeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AllowedNafCodeRepository::class)]
#[UniqueEntity(fields: ['code'], message: 'Ce code NAF est déjà autorisé.')]
class AllowedNafCode
{
    #[ORM\Id]
    #[ORM\Column(length: 6)]
    #[Assert\NotBlank(message: 'Le code NAF est obligatoire.')]
    #[Assert\Regex(
        pattern: '/^\d{2}\.\d{2}[A-Z]$/',
        message: 'Le code NAF doit respecter le format 43.22A.'
    )]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le libellé est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'Le libellé ne peut pas dépasser {{ limit }} caractères.')]
    private string $label = '';

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    public function __toString(): string
    {
        return null === $this->code ? $this->label : \sprintf('%s — %s', $this->code, $this->label);
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = mb_strtoupper(mb_trim($code));

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = mb_trim($label);

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }
}
