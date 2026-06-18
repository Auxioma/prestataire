<?php

namespace App\Enum;

enum PrestataireAppointmentStatusEnum: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::CONFIRMED => 'Confirmé',
            self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé',
        };
    }

    public function getCalendarColor(): string
    {
        return match ($this) {
            self::PENDING => '#f59e0b',
            self::CONFIRMED => '#0d9488',
            self::COMPLETED => '#2563eb',
            self::CANCELLED => '#dc2626',
        };
    }
}