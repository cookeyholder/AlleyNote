<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum CacheType: string
{
    case MEMORY = 'memory';
    case FILE = 'file';
    case REDIS = 'redis';
}
