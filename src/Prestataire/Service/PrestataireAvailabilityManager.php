<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 AUXIOMA Web Agency.
 *
 * Projet : TrouveMoi
 *
 * Tous droits réservés.
 *
 * Ce fichier fait partie du projet TrouveMoi,
 * développé par AUXIOMA Web Agency.
 *
 * Toute reproduction, modification, distribution ou utilisation,
 * totale ou partielle, sans autorisation écrite préalable,
 * est strictement interdite.
 */

namespace App\Prestataire\Service;

use App\Prestataire\Entity\PrestataireAvailability;
use App\Prestataire\Entity\PrestataireProfile;

final class PrestataireAvailabilityManager
{
    private const DEFAULT_MORNING_START = '08:30';
    private const DEFAULT_MORNING_END = '12:00';
    private const DEFAULT_AFTERNOON_START = '13:30';
    private const DEFAULT_AFTERNOON_END = '17:00';

    public function prepareForPersistence(PrestataireProfile $prestataireProfile): void
    {
        if (!$prestataireProfile->isOnVacation()) {
            $prestataireProfile->setVacationReturnDate(null);
        }

        foreach ($prestataireProfile->getAvailabilities() as $availability) {
            $this->applySlotDefaults($availability);
            $availability->setUpdatedAt(new \DateTime());
        }
    }

    private function applySlotDefaults(PrestataireAvailability $availability): void
    {
        if ($availability->isMorningEnabled()) {
            if (null === $availability->getMorningStart()) {
                $availability->setMorningStart($this->createTime(self::DEFAULT_MORNING_START));
            }

            if (null === $availability->getMorningEnd()) {
                $availability->setMorningEnd($this->createTime(self::DEFAULT_MORNING_END));
            }
        }

        if ($availability->isAfternoonEnabled()) {
            if (null === $availability->getAfternoonStart()) {
                $availability->setAfternoonStart($this->createTime(self::DEFAULT_AFTERNOON_START));
            }

            if (null === $availability->getAfternoonEnd()) {
                $availability->setAfternoonEnd($this->createTime(self::DEFAULT_AFTERNOON_END));
            }
        }
    }

    private function createTime(string $time): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('H:i', $time) ?: new \DateTimeImmutable($time);
    }
}
