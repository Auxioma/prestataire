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
