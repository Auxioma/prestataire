<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class ResponseTimeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('response_time', [$this, 'formatResponseTime']),
        ];
    }

    public function formatResponseTime(?int $minutes): string
    {
        if (null === $minutes || $minutes <= 0) {
            return '';
        }

        if ($minutes > 60 * 24 * 7 * 4) {
            $value = (int) round($minutes / (60 * 24 * 7 * 4));

            return sprintf('%d %s', $value, $value > 1 ? 'mois' : 'mois');
        }

        if ($minutes > 60 * 24 * 7) {
            $value = (int) round($minutes / (60 * 24 * 7));

            return sprintf('%d %s', $value, $value > 1 ? 'semaines' : 'semaine');
        }

        if ($minutes > 60 * 24) {
            $value = (int) round($minutes / (60 * 24));

            return sprintf('%d %s', $value, $value > 1 ? 'jours' : 'jour');
        }

        if ($minutes > 60) {
            $value = (int) round($minutes / 60);

            return sprintf('%d %s', $value, $value > 1 ? 'heures' : 'heure');
        }

        return sprintf('%d %s', $minutes, $minutes > 1 ? 'min' : 'min');
    }
}
