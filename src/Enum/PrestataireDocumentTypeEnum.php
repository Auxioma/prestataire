<?php

namespace App\Enum;

enum PrestataireDocumentTypeEnum: string
{
    case KBIS = 'KBIS';
    case RC_PRO = 'RC_PRO';
    case DECENNALE = 'DECENNALE';
    case VIGILANCE = 'VIGILANCE';
    case IDENTITE = 'IDENTITE';
    case AUTRE = 'AUTRE';

    public function getLabel(): string
    {
        return match ($this) {
            self::KBIS => 'Extrait Kbis / Justificatif d’entreprise',
            self::RC_PRO => 'Assurance RC Pro',
            self::DECENNALE => 'Attestation décennale',
            self::VIGILANCE => 'Attestation de vigilance',
            self::IDENTITE => 'Pièce d’identité',
            self::AUTRE => 'Autre document',
        };
    }
}
