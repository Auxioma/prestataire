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

namespace App\Search\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

// Written with DBAL inside Doctrine's entity transaction; no FK so deletions can be indexed.
#[ORM\Entity]
#[ORM\Table(name: 'prestataire_search_job')]
#[ORM\Index(name: 'idx_search_job_available', columns: ['available_at'])]
class PrestataireSearchJob
{
    #[ORM\Id]
    #[ORM\Column(type: Types::BIGINT)]
    private string $profileId;

    #[ORM\Column(options: ['default' => 0])]
    private int $attempts = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $availableAt;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastError = null;
}
