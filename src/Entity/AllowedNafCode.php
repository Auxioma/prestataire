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

use App\Repository\AllowedNafCodeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AllowedNafCodeRepository::class)]
class AllowedNafCode
{
    #[ORM\Id]
    #[ORM\Column(length: 6)]
    private string $code;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    public function getCode(): string
    {
        return $this->code;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
