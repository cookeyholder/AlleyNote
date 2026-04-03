<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum ErrorCode: string
{
    case VALIDATION_FAILED = 'validation_failed';
    case NOT_FOUND = 'not_found';
    case UNAUTHORIZED = 'unauthorized';
    case FORBIDDEN = 'forbidden';
    case INTERNAL_ERROR = 'internal_error';
}
