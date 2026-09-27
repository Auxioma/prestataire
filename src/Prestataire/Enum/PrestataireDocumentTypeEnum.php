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

namespace App\Prestataire\Enum;

enum PrestataireDocumentTypeEnum: string
{
    case CERTIFICATION = 'CERTIFICATION';
    case KBIS = 'KBIS';
    case RC_PRO = 'RC_PRO';
    case DECENNALE = 'DECENNALE';
    case VIGILANCE = 'VIGILANCE';
    case IDENTITE = 'IDENTITE';
    case AUTRE = 'AUTRE';

    public function getLabel(): string
    {
        return match ($this) {
            self::CERTIFICATION => 'Certification / Diplôme',
            self::KBIS => 'Extrait Kbis / Justificatif d’entreprise',
            self::RC_PRO => 'Assurance RC Pro',
            self::DECENNALE => 'Attestation décennale',
            self::VIGILANCE => 'Attestation de vigilance',
            self::IDENTITE => 'Pièce d’identité',
            self::AUTRE => 'Autre document',
        };
    }
}
