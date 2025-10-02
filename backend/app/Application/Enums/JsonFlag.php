<?php

declare(strict_types=1);

namespace App\Application\Enums;

enum JsonFlag: int
{
    case DEFAULT = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;
    case COMPACT = JSON_UNESCAPED_UNICODE;
    case DEBUG = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR;
}
