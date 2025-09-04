<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Exceptions;

use RuntimeException;

/**
 * 統計查詢異常
 *
 * 當統計查詢操作失敗時拋出的異常
 */
final class StatisticsQueryException extends RuntimeException
{
    public static function invalidDateRange(): self
    {
        return new self('無效的日期範圍');
    }

    public static function invalidPaginationParams(): self
    {
        return new self('無效的分頁參數');
    }

    public static function invalidSearchQuery(): self
    {
        return new self('無效的搜尋查詢');
    }

    public static function queryExecutionFailed(string $reason): self
    {
        return new self("查詢執行失敗: {$reason}");
    }

    public static function unsupportedMetric(string $metric): self
    {
        return new self("不支援的指標: {$metric}");
    }

    public static function unsupportedSortField(string $field): self
    {
        return new self("不支援的排序欄位: {$field}");
    }

    public static function cacheOperationFailed(string $operation): self
    {
        return new self("快取操作失敗: {$operation}");
    }
}
