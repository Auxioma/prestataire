<?php

namespace App\Enum;

enum MessageTypeEnum: string
{
    case SYSTEM = 'system';
    case USER = 'user';

    public function getLabel(): string
    {
        return match ($this) {
            self::SYSTEM => 'Système',
            self::USER => 'Utilisateur',
        };
    }
}