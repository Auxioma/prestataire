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

use App\Messaging\Repository\MessageRepository;
use App\Prestataire\Entity\PrestataireProfile;

final class PrestataireResponseTimeManager
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
    ) {
    }

    public function refreshForPrestataire(PrestataireProfile $prestataireProfile): void
    {
        $prestataireProfile->setResponseTimeMinutes(
            $this->messageRepository->calculateAverageFirstResponseTimeMinutesForPrestataire($prestataireProfile)
        );
    }
}
