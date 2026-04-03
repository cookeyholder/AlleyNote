<?php

declare(strict_types=1);

namespace App\Infrastructure\Enums;

enum SanitizerMode: string
{
    case HTML = 'html';
    case TITLE = 'title';
    case PRESERVE_NEWLINES = 'preserve_newlines';
    case TRUNCATE = 'truncate';
}
