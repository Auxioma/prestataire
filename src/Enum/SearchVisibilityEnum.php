<?php

namespace App\Enum;

enum SearchVisibilityEnum: string
{
    case NORMAL = 'NORMAL';
    case BOOSTED = 'BOOSTED';
    case PREMIUM = 'PREMIUM';
    case HIDDEN = 'HIDDEN';
}