<?php

namespace App\Enum;

enum QuoteProposalStatusEnum: string
{
    case DRAFT = 'draft';
    case FINALIZED = 'finalized';
    case DELETED = 'deleted';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::FINALIZED => 'Finalisé',
            self::DELETED => 'Supprimé',
        };
    }

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    public function isFinalized(): bool
    {
        return $this === self::FINALIZED;
    }

    public function isDeleted(): bool
    {
        return $this === self::DELETED;
    }
}