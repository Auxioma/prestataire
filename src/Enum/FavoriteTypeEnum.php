<?php

namespace App\Enum;

enum FavoriteTypeEnum: string
{
    case PRESTATAIRE = 'prestataire';
    case PRESTATION = 'prestation';
    case BON_PLAN = 'bon_plan';

    public function getLabel(): string
    {
        return match ($this) {
            self::PRESTATAIRE => 'Prestataire',
            self::PRESTATION => 'Prestation',
            self::BON_PLAN => 'Bon plan',
        };
    }
}