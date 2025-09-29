<?php

declare(strict_types=1);

namespace App\Shared\Enums;

/**
 * ValidationRule enum.
 *
 * 一個集中定義常用驗證規則名稱的 enum，方便在程式中以類型安全的方式使用。
 */
enum ValidationRule: string
{
    case REQUIRED = 'required';
    case EMAIL = 'email';
    case MIN_LENGTH = 'min_length';
    case MAX_LENGTH = 'max_length';
    case IN = 'in';
    case NUMERIC = 'numeric';
    case INTEGER = 'integer';
    case BOOLEAN = 'boolean';
    case URL = 'url';
    case REGEX = 'regex';
    case ALPHA = 'alpha';
    case ALPHANUM = 'alphanum';
    case DATE = 'date';
    case JSON = 'json';
}
