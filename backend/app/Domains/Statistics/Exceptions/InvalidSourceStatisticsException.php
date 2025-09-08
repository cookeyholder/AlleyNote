<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Exceptions;

use InvalidArgumentException;
use Throwable;

/**
 * 無效來源統計例外
 * 當來源統計參數不正確時拋出.
 */
class InvalidSourceStatisticsException extends InvalidArgumentException

{
    public function __construct(
        string $message = '無效的來源統計',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
