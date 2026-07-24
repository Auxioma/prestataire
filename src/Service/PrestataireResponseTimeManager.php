<?php

namespace App\Service;

use App\Entity\PrestataireProfile;
use App\Repository\MessageRepository;

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
