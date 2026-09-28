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

namespace App\Prestataire\Dto;

use App\Prestataire\Entity\PrestataireDocument;
use Symfony\Component\Form\FormInterface;

final class PrestataireSettingsForms
{
    public function __construct(
        public readonly FormInterface $userForm,
        public readonly FormInterface $companyForm,
        public readonly FormInterface $publicProfileForm,
        public readonly FormInterface $certificationForm,
        public readonly FormInterface $availabilityForm,
        public readonly FormInterface $notificationForm,
        public readonly FormInterface $passwordForm,
        public readonly FormInterface $deletionForm,
        public readonly FormInterface $zoneForm,
        public readonly FormInterface $documentForm,
        public readonly PrestataireDocument $certificationEntity,
        public readonly PrestataireDocument $documentEntity,
    ) {
    }
}
