<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Cache;

use App\Infrastructure\Routing\Contracts\RouteCacheInterface;
use App\Infrastructure\Routing\Contracts\RouteCollectionInterface;
use JsonException;

/**
 * 檔案快取實作.
 *
 * 使用檔案系統存儲路由快取資料
 */
class FileRouteCache implements RouteCacheInterface
{
    private int $ttl = 3600; // 預設 1 小時

    /**
     * @var array{hits: int, misses: int, size: int, created_at: int, last_used: int}
     */
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'size' => 0,
        'created_at' => 0,
        'last_used' => 0,
    ];

    /**
     * @param string $cachePath 快取檔案路徑
     */
    public function __construct(
        private readonly string $cachePath,
    ) {
        $this->ensureCacheDirectory();
        $this->loadStats();
    }

    public function isValid(): bool
    {
        if (!file_exists($this->getCacheFile())) {
            return false;
        }

        $mtime = filemtime($this->getCacheFile());
        if ($mtime === false) {
            return false;
        }

        // 檢查是否過期
        if ($this->ttl > 0 && (time() - $mtime) > $this->ttl) {
            return false;
        }

        return true;
    }

    public function load(): ?RouteCollectionInterface
    {
        if (!$this->isValid()) {
            $this->stats['misses']++;
            $this->saveStats();

            return null;
        }

        $content = file_get_contents($this->getCacheFile());
        if ($content === false) {
            $this->stats['misses']++;
            $this->saveStats();

            return null;
        }

        $data = unserialize($content);
        if (!($data instanceof RouteCollectionInterface)) {
            $this->stats['misses']++;
            $this->saveStats();

            return null;
        }

        $this->stats['hits']++;
        $this->stats['last_used'] = time();
        $this->saveStats();

        return $data;
    }

    public function store(RouteCollectionInterface $routes): bool
    {
        $content = serialize($routes);
        $result = file_put_contents($this->getCacheFile(), $content, LOCK_EX);

        if ($result !== false) {
            $this->stats['size'] = strlen($content);
            $this->stats['created_at'] = time();
            $this->saveStats();

            return true;
        }

        return false;
    }

    public function clear(): bool
    {
        $cacheFile = $this->getCacheFile();
        $statsFile = $this->getStatsFile();

        $result = true;
        if (file_exists($cacheFile)) {
            $result = unlink($cacheFile);
        }

        if (file_exists($statsFile)) {
            $result = $result && unlink($statsFile);
        }

        // 重置統計
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'size' => 0,
            'created_at' => 0,
            'last_used' => 0,
        ];

        return $result;
    }

    public function getCachePath(): string
    {
        return $this->cachePath;
    }

    /**
     * 設定快取存活時間.
     *
     * @param int $ttl 快取存活時間（秒）
     */
    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    /**
     * 取得快取存活時間.
     *
     * @return int 快取存活時間（秒）
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * 取得快取統計資料.
     *
     * @return array{hits: int, misses: int, size: int, created_at: int, last_used: int}
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * 取得快取檔案路徑.
     */
    private function getCacheFile(): string
    {
        return $this->cachePath . '/routes.cache';
    }

    /**
     * 取得統計檔案路徑.
     */
    private function getStatsFile(): string
    {
        return $this->cachePath . '/routes.stats';
    }

    /**
     * 確保快取目錄存在.
     */
    private function ensureCacheDirectory(): void
    {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0o755, true);
        }
    }

    /**
     * 載入統計資料.
     */
    private function loadStats(): void
    {
        $statsFile = $this->getStatsFile();
        if (file_exists($statsFile)) {
            $content = file_get_contents($statsFile);
            if ($content !== false) {
                $stats = json_decode($content, true);
                if (is_array($stats)) {
                    /** @var array{hits?: int, misses?: int, size?: int, created_at?: int, last_used?: int} $stats */
                    $filteredStats = array_intersect_key($stats, $this->stats);
                    $this->stats = array_merge($this->stats, $filteredStats);
                }
            }
        }
    }

    /**
     * 儲存統計資料.
     */
    private function saveStats(): void
    {
        try {
            $content = json_encode($this->stats, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            file_put_contents($this->getStatsFile(), $content, LOCK_EX);
        } catch (JsonException $e) {
            // 忽略 JSON 編碼錯誤
        }
    }
}
