<?php

declare(strict_types=1);

namespace App\Application\Services\Statistics;

use Exception;

/**
 * Statistics Query 相關服務例外.
 */
class StatisticsQueryException extends Exception
{
    /**
     * 建立 Statistics Query 相關的例外.
     *
     * @param string $message 錯誤訊息
     * @param int $code 錯誤碼
     * @param Exception|null $previous 前一個例外
     */
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
