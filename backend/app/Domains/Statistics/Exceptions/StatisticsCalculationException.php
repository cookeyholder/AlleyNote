<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Exceptions;

use Exception;

/**
 * 統計計算例外
 * 當統計計算過程中發生錯誤時拋出.
 */
class StatisticsCalculationException extends Exception
{
    public function __construct(
        string $message = '統計計算發生錯誤',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
