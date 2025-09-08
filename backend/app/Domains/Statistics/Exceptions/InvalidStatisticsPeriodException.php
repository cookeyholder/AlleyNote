<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Exceptions;

use InvalidArgumentException;
use Throwable;

/**
 * 無效統計週期例外
 * 當統計週期參數不正確時拋出.
 */
class InvalidStatisticsPeriodException extends InvalidArgumentException



{
    public function __construct(
        string $message = '無效的統計週期',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
