<?php

namespace App\Enum;

enum InvoiceStatusEnum: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::ISSUED => 'Émise',
        };
    }

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    public function isIssued(): bool
    {
        return $this === self::ISSUED;
    }
}
