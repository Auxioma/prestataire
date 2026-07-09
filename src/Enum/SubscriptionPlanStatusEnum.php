<?php

namespace App\Enum;

enum SubscriptionPlanStatusEnum: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::ACTIVE => 'Actif',
            self::ARCHIVED => 'Archivé',
        };
    }
}
