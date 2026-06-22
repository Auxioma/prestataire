<?php

namespace App\Enum;

enum QuoteRequestStatusEnum: string
{
    case SUBMITTED = 'submitted';
    case ANSWERED = 'answered';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::SUBMITTED => 'Demande envoyée',
            self::ANSWERED => 'Réponse reçue',
            self::CLOSED => 'Clôturée',
            self::CANCELLED => 'Annulée',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::SUBMITTED => 'warning',
            self::ANSWERED => 'info',
            self::CLOSED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}