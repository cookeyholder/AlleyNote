<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum ValidationRule: string
{
    case REQUIRED = 'required';
    case STRING = 'string';
    case INTEGER = 'integer';
    case EMAIL = 'email';
    case MAX = 'max';
    case MIN = 'min';
    case IN = 'in';
}
