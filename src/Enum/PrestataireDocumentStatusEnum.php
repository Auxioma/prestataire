<?php

namespace App\Enum;

enum PrestataireDocumentStatusEnum: string
{
    case UPLOADED = 'UPLOADED';
    case AVAILABLE_TO_CLIENT = 'AVAILABLE_TO_CLIENT';
    case EXPIRED = 'EXPIRED';
    case REJECTED = 'REJECTED';
}