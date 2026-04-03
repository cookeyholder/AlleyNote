<?php

declare(strict_types=1);

namespace App\Application\Enums;

enum JsonFlag: int
{
    case DEFAULT = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    case PRETTY = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
}
