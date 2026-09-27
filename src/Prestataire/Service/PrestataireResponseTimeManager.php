<?php

namespace App\Prestataire\Service;

use App\Prestataire\Entity\PrestataireProfile;
use App\Messaging\Repository\MessageRepository;

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
