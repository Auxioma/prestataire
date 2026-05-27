<?php

namespace App\Enum;

enum PrestataireProfileStatusEnum: string
{
    case DRAFT = 'DRAFT';
    case PENDING_VALIDATION = 'PENDING_VALIDATION';
    case ACTIVE = 'ACTIVE';
    case INCOMPLETE = 'INCOMPLETE';
    case SUSPENDED = 'SUSPENDED';
    case REFUSED = 'REFUSED';
    case DELETED = 'DELETED';
}