<?php

namespace App\Enum;

enum UserStatusEnum: string
{
    case PENDING = 'PENDING';
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';
    case BANNED = 'BANNED';
    case DELETED = 'DELETED';
}