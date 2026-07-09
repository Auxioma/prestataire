<?php

namespace App\Enum;

enum SubscriptionBillingPeriodEnum: string
{
    case MONTHLY = 'monthly';
    case ANNUAL = 'annual';

    public function getLabel(): string
    {
        return match ($this) {
            self::MONTHLY => 'Mensuel',
            self::ANNUAL => 'Annuel',
        };
    }

    public function getMonthsCount(): int
    {
        return match ($this) {
            self::MONTHLY => 1,
            self::ANNUAL => 12,
        };
    }
}
