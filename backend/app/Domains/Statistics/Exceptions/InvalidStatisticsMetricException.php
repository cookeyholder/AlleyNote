<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Exceptions;

use InvalidArgumentException;

/**
 * 無效統計指標例外
 * 當統計指標參數不正確時拋出.
 */
class InvalidStatisticsMetricException extends InvalidArgumentException
{
    public function __construct(
        string $message = '無效的統計指標',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
