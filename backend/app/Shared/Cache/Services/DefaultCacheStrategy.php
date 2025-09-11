<?php

declare(strict_types=1);

namespace App\Shared\Cache\Services;

use App\Shared\Cache\Contracts\CacheDriverInterface;
use App\Shared\Cache\Contracts\CacheStrategyInterface;
use Exception;

/**
 * 預設快取策略實作。
 *
 * 提供基本的快取決策邏輯，包含 TTL 管理、值大小限制和排除模式
 */
class DefaultCacheStrategy implements CacheStrategyInterface
{
    /** @var array<string, mixed> 配置 */
    private array $config;

    /** @var array<string, int> 統計資料 */
    private array $stats = [
        'cache_decisions' => 0,
        'cache_allowed' => 0,
        'cache_denied' => 0,
        'ttl_adjustments' => 0,
        'size_rejections' => 0,
        'pattern_exclusions' => 0,
    ];

    /** @var int 最小 TTL (秒) */
    private int $minTtl;

    /** @var int 最大 TTL (秒) */
    private int $maxTtl;

    /** @var int 最大值大小 (位元組) */
    private int $maxValueSize;

    /** @var array<string> 排除模式 */
    private array $excludePatterns;

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->initializeFromConfig();
    }

    public function shouldCache(string $key, mixed $value, int $ttl): bool
    {
        $this->stats['cache_decisions']++;

        // 檢查排除模式
        if ($this->isExcluded($key)) {
            $this->stats['pattern_exclusions']++;
            $this->stats['cache_denied']++;

            return false;
        }

        // 檢查值大小
        if (!$this->isValueSizeAcceptable($value)) {
            $this->stats['size_rejections']++;
            $this->stats['cache_denied']++;

            return false;
        }

        // 檢查 TTL 範圍
        if ($ttl < $this->minTtl || ($this->maxTtl > 0 && $ttl > $this->maxTtl)) {
            $this->stats['cache_denied']++;

            return false;
        }

        $this->stats['cache_allowed']++;

        return true;
    }

    public function calculateTtl(string $key, mixed $value, int $requestedTtl): int
    {
        // 確保 TTL 在允許範圍內
        $ttl = $requestedTtl;

        if ($ttl < $this->minTtl) {
            $ttl = $this->minTtl;
            $this->stats['ttl_adjustments']++;
        }

        if ($this->maxTtl > 0 && $ttl > $this->maxTtl) {
            $ttl = $this->maxTtl;
            $this->stats['ttl_adjustments']++;
        }

        // 基於鍵模式調整 TTL
        $ttl = $this->adjustTtlByKeyPattern($key, $ttl);

        return $ttl;
    }

    public function selectDriver(array $availableDrivers, string $operation, array $context = []): ?CacheDriverInterface
    {
        if (empty($availableDrivers)) {
            return null;
        }

        // 簡單的策略：優先選擇第一個可用的驅動
        foreach ($availableDrivers as $driver) {
            if ($driver instanceof CacheDriverInterface) {
                return $driver;
            }
        }

        return null;
    }

    public function handleFailure(string $operation, string $key, Exception $exception): mixed
    {
        // 記錄錯誤
        error_log("Cache operation '{$operation}' failed for key '{$key}': " . $exception->getMessage());

        // 根據操作類型返回合適的預設值
        return match ($operation) {
            'get' => null,
            'has' => false,
            'put', 'forget', 'flush' => false,
            default => null,
        };
    }

    /**
     * 取得策略統計資訊。
     *
     * @return array
     */
    public function getStats(): array
    {
        $totalDecisions = $this->stats['cache_decisions'];
        $allowRate = $totalDecisions > 0 ? ($this->stats['cache_allowed'] / $totalDecisions) * 100 : 0;

        return [
            'decisions_total' => $totalDecisions,
            'allowed' => $this->stats['cache_allowed'],
            'denied' => $this->stats['cache_denied'],
            'allow_rate_percent' => round($allowRate, 2),
            'ttl_adjustments' => $this->stats['ttl_adjustments'],
            'size_rejections' => $this->stats['size_rejections'],
            'pattern_exclusions' => $this->stats['pattern_exclusions'],
            'config' => $this->config,
        ];
    }

    /**
     * 重設統計資料。
     */
    public function resetStats(): void
    {
        $this->stats = [
            'cache_decisions' => 0,
            'cache_allowed' => 0,
            'cache_denied' => 0,
            'ttl_adjustments' => 0,
            'size_rejections' => 0,
            'pattern_exclusions' => 0,
        ];
    }

    /**
     * 取得配置。
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * 更新配置。
     *
     * @param array $config
     */
    public function updateConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        $this->initializeFromConfig();
    }

    /**
     * 檢查鍵是否被排除。
     */
    private function isExcluded(string $key): bool
    {
        foreach ($this->excludePatterns as $pattern) {
            if ($this->matchesPattern($key, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 檢查值大小是否可接受。
     */
    private function isValueSizeAcceptable(mixed $value): bool
    {
        if ($this->maxValueSize <= 0) {
            return true;
        }

        $size = strlen(serialize($value));

        return $size <= $this->maxValueSize;
    }

    /**
     * 基於鍵模式調整 TTL。
     */
    private function adjustTtlByKeyPattern(string $key, int $ttl): int
    {
        // 特殊模式的 TTL 調整
        $adjustments = $this->config['ttl_adjustments'] ?? [];

        foreach ($adjustments as $pattern => $multiplier) {
            if ($this->matchesPattern($key, $pattern)) {
                return (int) ($ttl * $multiplier);
            }
        }

        return $ttl;
    }

    /**
     * 檢查鍵是否符合模式。
     */
    private function matchesPattern(string $key, string $pattern): bool
    {
        // 支援簡單的萬用字元模式
        $pattern = str_replace(['*', '?'], ['.*', '.'], $pattern);

        return preg_match('/^' . $pattern . '$/', $key) === 1;
    }

    /**
     * 從配置初始化屬性。
     */
    private function initializeFromConfig(): void
    {
        $this->minTtl = (int) ($this->config['min_ttl'] ?? 60);
        $this->maxTtl = (int) ($this->config['max_ttl'] ?? 86400);
        $this->maxValueSize = (int) ($this->config['max_value_size'] ?? 1024 * 1024);
        $this->excludePatterns = (array) ($this->config['exclude_patterns'] ?? []);
    }

    /**
     * 取得預設配置。
     *
     * @return array
     */
    private function getDefaultConfig(): array
    {
        return [
            'min_ttl' => 60,        // 1 分鐘
            'max_ttl' => 86400,     // 24 小時
            'max_value_size' => 1024 * 1024, // 1MB
            'exclude_patterns' => [
                'temp => *',
                'debug => *',
                'test => *',
            ],
            'ttl_adjustments' => [
                'user => *' => 0.5,     // 使用者相關資料較短的 TTL
                'system => *' => 2.0,   // 系統資料較長的 TTL
                'static => *' => 5.0,   // 靜態資料更長的 TTL
            ],
        ];
    }
}
