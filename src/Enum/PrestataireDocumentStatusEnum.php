<?php

namespace App\Enum;

enum PrestataireDocumentStatusEnum: string
{
    case UPLOADED = 'UPLOADED';
    case AVAILABLE_TO_CLIENT = 'AVAILABLE_TO_CLIENT';
    case EXPIRED = 'EXPIRED';
    case REJECTED = 'REJECTED';

    public function getLabel(): string
    {
        return match ($this) {
            self::UPLOADED => 'Non vérifié',
            self::AVAILABLE_TO_CLIENT => 'Vérifié',
            self::EXPIRED => 'Expiré',
            self::REJECTED => 'Refusé',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::AVAILABLE_TO_CLIENT => 'success',
            self::UPLOADED => 'secondary',
            self::EXPIRED => 'warning',
            self::REJECTED => 'danger',
        };
    }
}
