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
