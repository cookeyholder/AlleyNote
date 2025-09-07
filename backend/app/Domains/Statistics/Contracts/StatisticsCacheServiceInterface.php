<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Cache\Contracts\TaggedCacheInterface;
use DateTimeInterface;

/**
 * 統計快取服務介面。
 *
 * 定義統計資料快取管理的標準介面，包含多層次快取、標籤管理和失效策略
 */
interface StatisticsCacheServiceInterface
{
    /**
     * 取得快取資料。
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 設定快取資料。
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * 刪除快取。
     */
    public function delete(string $key): bool;

    /**
     * 檢查快取是否存在。
     */
    public function has(string $key): bool;

    /**
     * 記憶化取得快取。
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed;

    /**
     * 取得標籤化快取實例。
     * @param string[] $tags 快取標籤
     */
    public function tags(array $tags): TaggedCacheInterface;

    /**
     * 依標籤清除快取。
     * @param string[] $tags 快取標籤
     */
    public function invalidateByTags(array $tags): bool;

    /**
     * 預熱快取。
     * @param array<string, callable> $callbacks 預熱回調函式
     * @return array<string, mixed>
     */
    public function warmup(array $callbacks): array;

    // 快取鍵產生方法

    /**
     * 取得統計概覽快取鍵。
     */
    public function getOverviewCacheKey(StatisticsPeriod $period): string;

    /**
     * 取得統計快照快取鍵。
     */
    public function getSnapshotCacheKey(StatisticsPeriod $period, DateTimeInterface $date): string;

    /**
     * 取得熱門內容快取鍵。
     */
    public function getPopularContentCacheKey(StatisticsPeriod $period, int $limit): string;

    /**
     * 取得統計報告快取鍵。
     */
    public function getReportCacheKey(StatisticsPeriod $period, string $reportType): string;

    /**
     * 取得趨勢分析快取鍵。
     */
    public function getTrendCacheKey(StatisticsPeriod $period, string $metric): string;

    /**
     * 取得分布統計快取鍵。
     */
    public function getDistributionCacheKey(StatisticsPeriod $period, string $type): string;

    /**
     * 取得系統統計快取鍵。
     */
    public function getSystemCacheKey(string $metric): string;

    /**
     * 取得使用者統計快取鍵。
     */
    public function getUserCacheKey(int $userId, StatisticsPeriod $period): string;

    /**
     * 取得文章統計快取鍵。
     */
    public function getPostCacheKey(int $postId, StatisticsPeriod $period): string;

    // 快取失效方法

    /**
     * 清除統計概覽快取。
     */
    public function invalidateOverviewCache(?StatisticsPeriod $period = null): bool;

    /**
     * 清除統計快照快取。
     */
    public function invalidateSnapshotCache(?StatisticsPeriod $period = null): bool;

    /**
     * 清除熱門內容快取。
     */
    public function invalidatePopularContentCache(?StatisticsPeriod $period = null): bool;

    /**
     * 清除統計報告快取。
     */
    public function invalidateReportCache(string $reportType, ?StatisticsPeriod $period = null): bool;

    /**
     * 清除所有統計快取。
     */
    public function invalidateAllCache(): bool;

    // 監控和管理方法

    /**
     * 取得快取統計資訊。
     * @return array<string, mixed>
     *                              manager_stats: array,
     *                              cache_keys: array,
     *                              ttl_config: array,
     *                              tag_config: array,
     *                              health_status: array
     *                              }
     */
    public function getStats(): array;

    /**
     * 檢查快取服務是否健康。
     */
    public function isHealthy(): bool;

    /**
     * 清理過期的快取項目。
     * @return array<string, mixed> 清理結果
     */
    public function cleanup(): array;
}
