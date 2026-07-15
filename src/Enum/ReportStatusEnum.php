<?php

namespace App\Enum;

enum ReportStatusEnum: string
{
    case NEW = 'new';
    case IN_REVIEW = 'in_review';
    case RESOLVED = 'resolved';
    case DISMISSED = 'dismissed';

    public function getLabel(): string
    {
        return match ($this) {
            self::NEW => 'Nouveau',
            self::IN_REVIEW => 'En cours',
            self::RESOLVED => 'Traité',
            self::DISMISSED => 'Classé sans suite',
        };
    }
}
