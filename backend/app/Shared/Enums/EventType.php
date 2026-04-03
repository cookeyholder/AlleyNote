<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum EventType: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';
    case VIEWED = 'viewed';
}
