<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum JsonFlag: int
{
    // Common JSON encoding flag sets used throughout the application
    case DEFAULT = 0;
    case PRETTY = JSON_PRETTY_PRINT;
    case UNESCAPED_UNICODE = JSON_UNESCAPED_UNICODE;
    case UNESCAPED_SLASHES = JSON_UNESCAPED_SLASHES;
    case NUMERIC_CHECK = JSON_NUMERIC_CHECK;
    case FORCE_OBJECT = JSON_FORCE_OBJECT;

    public function flags(): int
    {
        return $this->value;
    }
}
