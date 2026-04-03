<?php

declare(strict_types=1);

namespace App\Domains\Security\Enums;

enum ActivityStatus: string
{
    case SUCCESS = 'SUCCESS';
    case FAILED = 'FAILED';
    case BLOCKED = 'BLOCKED';
}
