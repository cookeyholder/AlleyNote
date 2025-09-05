<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Exceptions;

use App\Shared\Exceptions\DomainException;
use Throwable;

/**
 * 統計領域例外基礎類別.
 *
 * 統計相關的所有例外都應該繼承此類別，
 * 提供統一的例外處理機制。
 */
abstract class StatisticsException extends DomainException
{
    /**
     * 建立統計領域例外.
     *
     * @param string $message 例外訊息
     * @param int $code 例外代碼
     * @param Throwable|null $previous 前一個例外
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 取得領域名稱.
     *
     * @return string 領域名稱
     */
    public function getDomainName(): string
    {
        return 'Statistics';
    }
}
