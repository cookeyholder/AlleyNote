<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

/**
 * 慢查詢監控服務合約介面.
 *
 * 定義慢查詢監控的標準操作。
 */
interface SlowQueryMonitoringServiceInterface
{
    /**
     * 記錄慢查詢.
     */
    public function recordSlowQuery(
        string $queryType,
        string $query,
        float $executionTime,
        array $parameters = [],
    ): bool;

    /**
     * 取得慢查詢統計資料.
     *
     * @return array<array{query_type: string, slow_query_count: int}>
     */
    public function getSlowQueryStats(int $days = 7): array;

    /**
     * 取得慢查詢詳細資料.
     *
     * @return array<array{
     *     id: int,
     *     query_type: string,
     *     query: string,
     *     execution_time: float,
     *     parameters: array,
     *     created_at: string
     * }>
     */
    public function getSlowQueryDetails(int $limit = 50): array;

    /**
     * 清理舊的慢查詢記錄.
     */
    public function cleanupOldRecords(int $days = 30): int;
}
