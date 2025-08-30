<?php

declare(strict_types=1);

namespace App\Shared\Cache\Services;

use App\Shared\Cache\Contracts\CacheDriverInterface;
use App\Shared\Cache\Contracts\CacheStrategyInterface;

/**
 * 預設快取策略。
 *
 * 提供基本的快取策略實作
 */
class DefaultCacheStrategy implements CacheStrategyInterface
{
    /** @var array<string, int> 統計資料 */
    private array $stats = [
        'cache_decisions' => 0,
        'cache_allowed' => 0,
        'cache_denied' => 0,
        'driver_selections' => 0,
        'ttl_adjustments' => 0,
        'miss_handles' => 0,
        'failure_handles' => 0,
    ];

    /** @var int 最小快取 TTL */
    private int $minTtl;

    /** @var int 最大快取 TTL */
    private int $maxTtl;

    /** @var array<string> 不快取的鍵模式 */
    private array $excludePatterns;

    /** @var int 最大值大小（位元組） */
    private int $maxValueSize;

    public function __construct(array $config = [])
    {
        $this->minTtl = $config['min_ttl'] ?? 60;
        $this->maxTtl = $config['max_ttl'] ?? 86400;
        $this->excludePatterns = $config['exclude_patterns'] ?? [];
        $this->maxValueSize = $config['max_value_size'] ?? 1024 * 1024; // 1MB
    }

    public function shouldCache(string $key, mixed $value, int $ttl): bool
    {
        $this->stats['cache_decisions']++;

        // 檢查是否在排除模式中
        foreach ($this->excludePatterns as $pattern) {
            if ($this->matchesPattern($key, $pattern)) {
                $this->stats['cache_denied']++;
                return false;
            }
        }

        // 檢查值大小
        $serializedValue = serialize($value);
        if (strlen($serializedValue) > $this->maxValueSize) {
            $this->stats['cache_denied']++;
            return false;
        }

        // 檢查 TTL 範圍
        if ($ttl > 0 && ($ttl < $this->minTtl || $ttl > $this->maxTtl)) {
            $this->stats['cache_denied']++;
            return false;
        }

        // 檢查值類型
        if (is_resource($value) || (is_object($value) && !method_exists($value, '__sleep'))) {
            $this->stats['cache_denied']++;
            return false;
        }

        $this->stats['cache_allowed']++;
        return true;
    }

    public function selectDriver(array $drivers, string $key, mixed $value): ?CacheDriverInterface
    {
        $this->stats['driver_selections']++;

        if (empty($drivers)) {
            return null;
        }

        $serializedValue = serialize($value);
        $valueSize = strlen($serializedValue);

        // 根據資料大小選擇驅動
        foreach ($drivers as $name => $driver) {
            if (!$driver->isAvailable()) {
                continue;
            }

            // 小資料優先使用記憶體快取
            if ($valueSize <= 1024 && str_contains(get_class($driver), 'Memory')) {
                return $driver;
            }

            // 中等資料使用 Redis
            if ($valueSize <= 10240 && str_contains(get_class($driver), 'Redis')) {
                return $driver;
            }

            // 大資料使用檔案快取
            if (str_contains(get_class($driver), 'File')) {
                return $driver;
            }
        }

        // 回退到第一個可用的驅動
        foreach ($drivers as $driver) {
            if ($driver->isAvailable()) {
                return $driver;
            }
        }

        return null;
    }

    public function decideTtl(string $key, mixed $value, int $requestedTtl): int
    {
        $this->stats['ttl_adjustments']++;

        // 如果請求的 TTL 為 0（永不過期），保持原樣
        if ($requestedTtl === 0) {
            return 0;
        }

        // 調整 TTL 在合理範圍內
        $adjustedTtl = max($this->minTtl, min($this->maxTtl, $requestedTtl));

        // 根據資料特性調整 TTL
        $serializedValue = serialize($value);
        $valueSize = strlen($serializedValue);

        // 小資料可以快取更長時間
        if ($valueSize <= 1024) {
            $adjustedTtl = min($this->maxTtl, $adjustedTtl * 2);
        }

        // 大資料縮短快取時間
        if ($valueSize > 10240) {
            $adjustedTtl = max($this->minTtl, (int)($adjustedTtl * 0.5));
        }

        // 根據鍵類型調整
        if (str_contains($key, 'session:') || str_contains($key, 'user:')) {
            // 會話相關資料較短時間
            $adjustedTtl = min(3600, $adjustedTtl);
        } elseif (str_contains($key, 'config:') || str_contains($key, 'settings:')) {
            // 配置相關資料較長時間
            $adjustedTtl = max(7200, $adjustedTtl);
        }

        return $adjustedTtl;
    }

    public function handleMiss(string $key, callable $callback): mixed
    {
        $this->stats['miss_handles']++;

        // 簡單的重試機制
        $maxRetries = 3;
        $retryDelay = 100000; // 100ms

        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                return $callback();
            } catch (\Exception $e) {
                if ($i === $maxRetries - 1) {
                    throw $e;
                }
                usleep($retryDelay * ($i + 1)); // 指數退避
            }
        }

        return null;
    }

    public function handleDriverFailure(
        CacheDriverInterface $failedDriver,
        array $availableDrivers,
        string $operation,
        array $params
    ): mixed {
        $this->stats['failure_handles']++;

        // 尋找替代驅動
        foreach ($availableDrivers as $driver) {
            if ($driver === $failedDriver || !$driver->isAvailable()) {
                continue;
            }

            try {
                return match ($operation) {
                    'get' => $driver->get($params['key'] ?? '', $params['default'] ?? null),
                    'put' => $driver->put($params['key'] ?? '', $params['value'] ?? null, $params['ttl'] ?? 3600),
                    'has' => $driver->has($params['key'] ?? ''),
                    'forget' => $driver->forget($params['key'] ?? ''),
                    'flush' => $driver->flush(),
                    default => null,
                };
            } catch (\Exception) {
                // 繼續嘗試下一個驅動
                continue;
            }
        }

        // 所有驅動都失敗，根據操作返回合適的預設值
        return match ($operation) {
            'get' => $params['default'] ?? null,
            'put', 'forget', 'flush' => false,
            'has' => false,
            default => null,
        };
    }

    public function getStats(): array
    {
        $totalDecisions = $this->stats['cache_decisions'];
        $allowRate = $totalDecisions > 0 ? ($this->stats['cache_allowed'] / $totalDecisions) * 100 : 0;

        return array_merge($this->stats, [
            'cache_allow_rate' => round($allowRate, 2),
            'min_ttl' => $this->minTtl,
            'max_ttl' => $this->maxTtl,
            'max_value_size' => $this->maxValueSize,
            'exclude_patterns_count' => count($this->excludePatterns),
        ]);
    }

    public function resetStats(): void
    {
        $this->stats = [
            'cache_decisions' => 0,
            'cache_allowed' => 0,
            'cache_denied' => 0,
            'driver_selections' => 0,
            'ttl_adjustments' => 0,
            'miss_handles' => 0,
            'failure_handles' => 0,
        ];
    }

    /**
     * 檢查鍵是否符合模式。
     */
    private function matchesPattern(string $key, string $pattern): bool
    {
        $pattern = str_replace(['*', '?'], ['.*', '.'], $pattern);
        return preg_match('/^' . $pattern . '$/', $key) === 1;
    }

    /**
     * 新增排除模式。
     */
    public function addExcludePattern(string $pattern): void
    {
        if (!in_array($pattern, $this->excludePatterns, true)) {
            $this->excludePatterns[] = $pattern;
        }
    }

    /**
     * 移除排除模式。
     */
    public function removeExcludePattern(string $pattern): bool
    {
        $key = array_search($pattern, $this->excludePatterns, true);

        if ($key !== false) {
            unset($this->excludePatterns[$key]);
            $this->excludePatterns = array_values($this->excludePatterns);
            return true;
        }

        return false;
    }

    /**
     * 取得排除模式。
     */
    public function getExcludePatterns(): array
    {
        return $this->excludePatterns;
    }

    /**
     * 設定 TTL 範圍。
     */
    public function setTtlRange(int $minTtl, int $maxTtl): void
    {
        if ($minTtl <= 0 || $maxTtl <= 0 || $minTtl > $maxTtl) {
            throw new \InvalidArgumentException('無效的 TTL 範圍');
        }

        $this->minTtl = $minTtl;
        $this->maxTtl = $maxTtl;
    }

    /**
     * 設定最大值大小。
     */
    public function setMaxValueSize(int $maxValueSize): void
    {
        if ($maxValueSize <= 0) {
            throw new \InvalidArgumentException('最大值大小必須大於 0');
        }

        $this->maxValueSize = $maxValueSize;
    }
}
