<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Services;

use PDO;
use PDOException;
use RuntimeException;

/**
 * 慢查詢監控服務.
 *
 * 監控統計查詢的執行效能，記錄慢查詢並提供效能分析。
 */
final class SlowQueryMonitoringService
{
    /** 慢查詢閾值（秒） */
    private const SLOW_QUERY_THRESHOLD = 1.0;

    /** 記錄保存天數 */
    private const LOG_RETENTION_DAYS = 30;

    public function __construct(
        private readonly PDO $db,
    ) {}

    /**
     * 執行查詢並監控效能.
     *
     * @param string $query SQL 查詢語句
     * @param array<string, mixed> $params 查詢參數
     * @param string $queryType 查詢類型標識
     * @return mixed 查詢結果
     */
    public function executeAndMonitor(string $query, array $params = [], string $queryType = 'unknown'): mixed
    {
        $startTime = microtime(true);
        $queryHash = $this->generateQueryHash($query);

        try {
            $stmt = $this->db->prepare($query);

            // 綁定參數
            foreach ($params as $key => $value) {
                $paramName = str_starts_with($key, ':') ? $key : ':' . $key;
                $stmt->bindValue($paramName, $value);
            }

            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $executionTime = microtime(true) - $startTime;

            // 記錄查詢效能
            $this->recordQueryPerformance($query, $queryType, $executionTime, $queryHash, count($result));

            // 如果是慢查詢，額外記錄詳細資訊
            if ($executionTime > self::SLOW_QUERY_THRESHOLD) {
                $this->recordSlowQuery($query, $queryType, $executionTime, $params, $queryHash);
            }

            return $result;
        } catch (PDOException $e) {
            $executionTime = microtime(true) - $startTime;
            $this->recordFailedQuery($query, $queryType, $executionTime, $e->getMessage(), $queryHash);

            throw new RuntimeException('查詢執行失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 獲取慢查詢統計.
     *
     * @param int $days 查詢最近幾天的資料
     * @return array<string, mixed>
     */
    public function getSlowQueryStats(int $days = 7): array
    {
        try {
            $sql = 'SELECT
                        query_type,
                        COUNT(*) as slow_query_count,
                        AVG(execution_time) as avg_execution_time,
                        MAX(execution_time) as max_execution_time,
                        MIN(execution_time) as min_execution_time
                    FROM statistics_slow_queries
                    WHERE created_at >= :since_date
                    GROUP BY query_type
                    ORDER BY slow_query_count DESC';

            $stmt = $this->db->prepare($sql);
            $timestamp = strtotime("-{$days} days");
            if ($timestamp === false) {
                throw new RuntimeException('無效的日期格式');
            }
            $stmt->bindValue(':since_date', date('Y-m-d H:i:s', $timestamp));
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException('獲取慢查詢統計失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 獲取查詢效能趨勢.
     *
     * @param string $queryType 查詢類型
     * @param int $days 查詢天數
     * @return array<string, mixed>
     */
    public function getPerformanceTrend(string $queryType, int $days = 30): array
    {
        try {
            $sql = 'SELECT
                        DATE(created_at) as date,
                        COUNT(*) as query_count,
                        AVG(execution_time) as avg_execution_time,
                        MAX(execution_time) as max_execution_time,
                        SUM(CASE WHEN execution_time > :threshold THEN 1 ELSE 0 END) as slow_count
                    FROM statistics_query_performance
                    WHERE query_type = :query_type
                    AND created_at >= :since_date
                    GROUP BY DATE(created_at)
                    ORDER BY date';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':query_type', $queryType);
            $stmt->bindValue(':threshold', self::SLOW_QUERY_THRESHOLD);
            $stmt->bindValue(':since_date', date('Y-m-d H:i:s', strtotime("-{$days} days")));
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException('獲取效能趨勢失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 獲取最慢的查詢列表.
     *
     * @param int $limit 返回數量
     * @param int $days 查詢最近幾天的資料
     * @return array<string, mixed>
     */
    public function getSlowestQueries(int $limit = 10, int $days = 7): array
    {
        try {
            $sql = 'SELECT
                        query_hash,
                        query_type,
                        execution_time,
                        result_count,
                        query_params,
                        created_at
                    FROM statistics_slow_queries
                    WHERE created_at >= :since_date
                    ORDER BY execution_time DESC
                    LIMIT :limit';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':since_date', date('Y-m-d H:i:s', strtotime("-{$days} days")));
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException('獲取最慢查詢失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 分析查詢效能問題.
     *
     * @param string $queryHash 查詢雜湊
     * @return array<string, mixed>
     */
    public function analyzeQueryPerformance(string $queryHash): array
    {
        try {
            // 獲取查詢執行歷史
            $historySql = 'SELECT execution_time, result_count, created_at
                          FROM statistics_query_performance
                          WHERE query_hash = :query_hash
                          ORDER BY created_at DESC
                          LIMIT 100';

            $historyStmt = $this->db->prepare($historySql);
            $historyStmt->bindValue(':query_hash', $queryHash);
            $historyStmt->execute();
            $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($history)) {
                return ['error' => '找不到查詢記錄'];
            }

            // 計算統計指標
            $executionTimes = array_column($history, 'execution_time');
            $resultCounts = array_column($history, 'result_count');

            return [
                'query_hash' => $queryHash,
                'total_executions' => count($history),
                'avg_execution_time' => round(array_sum($executionTimes) / count($executionTimes), 4),
                'min_execution_time' => min($executionTimes),
                'max_execution_time' => max($executionTimes),
                'avg_result_count' => round(array_sum($resultCounts) / count($resultCounts), 2),
                'slow_query_rate' => round(count(array_filter($executionTimes, fn($time) => $time > self::SLOW_QUERY_THRESHOLD)) / count($executionTimes) * 100, 2),
                'last_execution' => $history[0]['created_at'],
                'performance_trend' => $this->calculatePerformanceTrend($history),
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('分析查詢效能失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 清理舊的監控記錄.
     */
    public function cleanupOldRecords(): int
    {
        try {
            $cutoffDate = date('Y-m-d H:i:s', strtotime('-' . self::LOG_RETENTION_DAYS . ' days'));

            // 清理效能記錄
            $performanceSql = 'DELETE FROM statistics_query_performance WHERE created_at < :cutoff_date';
            $performanceStmt = $this->db->prepare($performanceSql);
            $performanceStmt->bindValue(':cutoff_date', $cutoffDate);
            $performanceStmt->execute();
            $performanceDeleted = $performanceStmt->rowCount();

            // 清理慢查詢記錄
            $slowSql = 'DELETE FROM statistics_slow_queries WHERE created_at < :cutoff_date';
            $slowStmt = $this->db->prepare($slowSql);
            $slowStmt->bindValue(':cutoff_date', $cutoffDate);
            $slowStmt->execute();
            $slowDeleted = $slowStmt->rowCount();

            return $performanceDeleted + $slowDeleted;
        } catch (PDOException $e) {
            throw new RuntimeException('清理舊記錄失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 記錄查詢效能.
     */
    private function recordQueryPerformance(
        string $query,
        string $queryType,
        float $executionTime,
        string $queryHash,
        int $resultCount,
    ): void {
        try {
            $sql = 'INSERT INTO statistics_query_performance
                    (query_hash, query_type, execution_time, result_count, created_at)
                    VALUES (:query_hash, :query_type, :execution_time, :result_count, :created_at)';

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'query_hash' => $queryHash,
                'query_type' => $queryType,
                'execution_time' => $executionTime,
                'result_count' => $resultCount,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            // 記錄失敗不應該影響主要查詢，只記錄錯誤
            error_log('記錄查詢效能失敗: ' . $e->getMessage());
        }
    }

    /**
     * 記錄慢查詢.
     *
     * @param array<string, mixed> $params
     */
    private function recordSlowQuery(
        string $query,
        string $queryType,
        float $executionTime,
        array $params,
        string $queryHash,
    ): void {
        try {
            $sql = 'INSERT INTO statistics_slow_queries
                    (query_hash, query_type, query_sql, execution_time, query_params, created_at)
                    VALUES (:query_hash, :query_type, :query_sql, :execution_time, :query_params, :created_at)';

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'query_hash' => $queryHash,
                'query_type' => $queryType,
                'query_sql' => $query,
                'execution_time' => $executionTime,
                'query_params' => json_encode($params, JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            error_log('記錄慢查詢失敗: ' . $e->getMessage());
        }
    }

    /**
     * 記錄失敗查詢.
     */
    private function recordFailedQuery(
        string $query,
        string $queryType,
        float $executionTime,
        string $errorMessage,
        string $queryHash,
    ): void {
        try {
            $sql = 'INSERT INTO statistics_failed_queries
                    (query_hash, query_type, query_sql, execution_time, error_message, created_at)
                    VALUES (:query_hash, :query_type, :query_sql, :execution_time, :error_message, :created_at)';

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'query_hash' => $queryHash,
                'query_type' => $queryType,
                'query_sql' => $query,
                'execution_time' => $executionTime,
                'error_message' => $errorMessage,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            error_log('記錄失敗查詢失敗: ' . $e->getMessage());
        }
    }

    /**
     * 產生查詢雜湊.
     */
    private function generateQueryHash(string $query): string
    {
        // 移除參數值，保留查詢結構
        $normalizedQuery = preg_replace('/:\w+/', '?', $query);
        if ($normalizedQuery === null) {
            $normalizedQuery = $query; // 如果正則表達式失敗，使用原始查詢
        }
        $normalizedQuery = preg_replace('/\s+/', ' ', trim($normalizedQuery));
        if ($normalizedQuery === null) {
            $normalizedQuery = trim($query); // 如果正則表達式失敗，使用去除空白的原始查詢
        }

        return md5($normalizedQuery);
    }

    /**
     * 計算效能趨勢.
     *
     * @param array<array<string, mixed>> $history
     */
    private function calculatePerformanceTrend(array $history): string
    {
        if (count($history) < 5) {
            return 'insufficient_data';
        }

        $recent = array_slice($history, 0, 10);
        $older = array_slice($history, -10, 10);

        $recentAvg = array_sum(array_column($recent, 'execution_time')) / count($recent);
        $olderAvg = array_sum(array_column($older, 'execution_time')) / count($older);

        $change = ($recentAvg - $olderAvg) / $olderAvg * 100;

        if ($change > 20) {
            return 'deteriorating';
        } elseif ($change < -20) {
            return 'improving';
        } else {
            return 'stable';
        }
    }
}
