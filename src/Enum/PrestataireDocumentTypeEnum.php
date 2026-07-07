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
}