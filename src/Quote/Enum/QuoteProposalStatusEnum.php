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

namespace App\Quote\Enum;

enum QuoteProposalStatusEnum: string
{
    case DRAFT = 'draft';
    case FINALIZED = 'finalized';
    case ACCEPTED = 'accepted';
    case ARCHIVED = 'archived';
    case DELETED = 'deleted';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::FINALIZED => 'Finalisé',
            self::ACCEPTED => 'Accepté',
            self::ARCHIVED => 'Archivé',
            self::DELETED => 'Supprimé',
        };
    }

    public function isDraft(): bool
    {
        return self::DRAFT === $this;
    }

    public function isFinalized(): bool
    {
        return self::FINALIZED === $this;
    }

    public function isAccepted(): bool
    {
        return self::ACCEPTED === $this;
    }

    public function isArchived(): bool
    {
        return self::ARCHIVED === $this;
    }

    public function isDeleted(): bool
    {
        return self::DELETED === $this;
    }
}
