<?php

namespace App\Enum;

enum SubscriptionInvoiceStatusEnum: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case PAID = 'paid';
    case UNCOLLECTIBLE = 'uncollectible';
    case VOID = 'void';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::OPEN => 'Ouverte',
            self::PAID => 'Payée',
            self::UNCOLLECTIBLE => 'Irrécouvrable',
            self::VOID => 'Annulée',
        };
    }
}
