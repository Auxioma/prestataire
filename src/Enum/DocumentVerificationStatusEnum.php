<?php

namespace App\Enum;

enum DocumentVerificationStatusEnum: string
{
    case NOT_SUBMITTED = 'NOT_SUBMITTED';
    case PENDING_REVIEW = 'PENDING_REVIEW';
    case VERIFIED = 'VERIFIED';
    case REJECTED = 'REJECTED';
}