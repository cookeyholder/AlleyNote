<?php

declare(strict_types=1);

namespace App\Shared\Cache\Drivers;

use App\Shared\Cache\Contracts\CacheDriverInterface;

/**
 * 檔案快取驅動。
 *
 * 使用檔案系統存儲快取資料，支援持久化存儲
 */
class FileCacheDriver implements CacheDriverInterface
{
    /** @var string 快取目錄路徑 */
    private string $cachePath;

    /** @var array<string, int> 統計資料 */
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
        'clears' => 0,
    ];

    /** @var string 快取檔案副檔名 */
    private const CACHE_EXTENSION = '.cache';

    /** @var int 預設 TTL */
    private const DEFAULT_TTL = 3600;

    public function __construct(string $cachePath = '')
    {
        $this->cachePath = $cachePath ?: dirname(__DIR__, 4) . '/storage/cache/files';
        $this->ensureDirectoryExists($this->cachePath);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $filePath = $this->getCacheFilePath($key);

        if (!file_exists($filePath)) {
            $this->stats['misses']++;
            return $default;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            $this->stats['misses']++;
            return $default;
        }

        $data = unserialize($content);
        if (!is_array($data) || !isset($data['value'], $data['expires_at'])) {
            $this->stats['misses']++;
            return $default;
        }

        // 檢查過期
        if ($data['expires_at'] !== 0 && time() > $data['expires_at']) {
            unlink($filePath);
            $this->stats['misses']++;
            return $default;
        }

        $this->stats['hits']++;
        return $data['value'];
    }

    public function put(string $key, mixed $value, int $ttl = self::DEFAULT_TTL): bool
    {
        $filePath = $this->getCacheFilePath($key);
        $this->ensureDirectoryExists(dirname($filePath));

        $data = [
            'value' => $value,
            'expires_at' => $ttl > 0 ? time() + $ttl : 0,
            'created_at' => time(),
        ];

        $result = file_put_contents($filePath, serialize($data), LOCK_EX) !== false;

        if ($result) {
            $this->stats['sets']++;
        }

        return $result;
    }

    public function has(string $key): bool
    {
        $filePath = $this->getCacheFilePath($key);

        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return false;
        }

        $data = unserialize($content);
        if (!is_array($data) || !isset($data['expires_at'])) {
            return false;
        }

        // 檢查過期
        if ($data['expires_at'] !== 0 && time() > $data['expires_at']) {
            unlink($filePath);
            return false;
        }

        return true;
    }

    public function forget(string $key): bool
    {
        $filePath = $this->getCacheFilePath($key);

        if (file_exists($filePath)) {
            $result = unlink($filePath);
            if ($result) {
                $this->stats['deletes']++;
            }
            return $result;
        }

        return true;
    }

    public function flush(): bool
    {
        $success = true;
        $files = glob($this->cachePath . '/*' . self::CACHE_EXTENSION);

        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }

        if ($success) {
            $this->stats['clears']++;
        }

        return $success;
    }

    public function many(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    public function putMany(array $values, int $ttl = self::DEFAULT_TTL): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->put($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }

    public function forgetMany(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->forget($key)) {
                $success = false;
            }
        }
        return $success;
    }

    public function forgetPattern(string $pattern): int
    {
        $deleted = 0;
        $files = glob($this->cachePath . '/*' . self::CACHE_EXTENSION);

        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            $key = $this->getKeyFromFilePath($file);
            if ($this->matchesPattern($key, $pattern)) {
                if (unlink($file)) {
                    $deleted++;
                    $this->stats['deletes']++;
                }
            }
        }

        return $deleted;
    }

    public function increment(string $key, int $value = 1): int
    {
        $current = $this->get($key, 0);
        $newValue = (int) $current + $value;
        $this->put($key, $newValue);
        return $newValue;
    }

    public function decrement(string $key, int $value = 1): int
    {
        $current = $this->get($key, 0);
        $newValue = (int) $current - $value;
        $this->put($key, $newValue);
        return $newValue;
    }

    public function remember(string $key, callable $callback, int $ttl = self::DEFAULT_TTL): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        if ($value !== null) {
            $this->put($key, $value, $ttl);
        }

        return $value;
    }

    public function rememberForever(string $key, callable $callback): mixed
    {
        return $this->remember($key, $callback, 0);
    }

    public function getStats(): array
    {
        $totalRequests = $this->stats['hits'] + $this->stats['misses'];
        $hitRate = $totalRequests > 0 ? ($this->stats['hits'] / $totalRequests) * 100 : 0;

        return array_merge($this->stats, [
            'total_files' => $this->getTotalFiles(),
            'total_size' => $this->getTotalSize(),
            'hit_rate' => round($hitRate, 2),
            'cache_path' => $this->cachePath,
            'expired_files' => $this->getExpiredFilesCount(),
        ]);
    }

    public function getConnection(): mixed
    {
        return $this->cachePath;
    }

    public function isAvailable(): bool
    {
        return is_dir($this->cachePath) && is_writable($this->cachePath);
    }

    public function cleanup(): int
    {
        $cleaned = 0;
        $currentTime = time();
        $files = glob($this->cachePath . '/*' . self::CACHE_EXTENSION);

        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $data = unserialize($content);
            if (!is_array($data) || !isset($data['expires_at'])) {
                continue;
            }

            if ($data['expires_at'] !== 0 && $currentTime > $data['expires_at']) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }

    /**
     * 取得快取檔案路徑。
     */
    private function getCacheFilePath(string $key): string
    {
        $hash = hash('sha256', $key);
        $subDir = substr($hash, 0, 2);
        return $this->cachePath . '/' . $subDir . '/' . $hash . self::CACHE_EXTENSION;
    }

    /**
     * 從檔案路徑取得快取鍵。
     */
    private function getKeyFromFilePath(string $filePath): string
    {
        $fileName = basename($filePath, self::CACHE_EXTENSION);
        return $fileName; // 這裡返回雜湊值，實際應用中可能需要維護鍵對映
    }

    /**
     * 確保目錄存在。
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
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
     * 取得總檔案數。
     */
    private function getTotalFiles(): int
    {
        $files = glob($this->cachePath . '/*' . self::CACHE_EXTENSION);
        return $files !== false ? count($files) : 0;
    }

    /**
     * 取得總大小。
     */
    private function getTotalSize(): int
    {
        $totalSize = 0;
        $files = glob($this->cachePath . '/*' . self::CACHE_EXTENSION);

        if ($files !== false) {
            foreach ($files as $file) {
                $totalSize += filesize($file);
            }
        }

        return $totalSize;
    }

    /**
     * 取得過期檔案數量。
     */
    private function getExpiredFilesCount(): int
    {
        $expired = 0;
        $currentTime = time();
        $files = glob($this->cachePath . '/*' . self::CACHE_EXTENSION);

        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $data = unserialize($content);
            if (!is_array($data) || !isset($data['expires_at'])) {
                continue;
            }

            if ($data['expires_at'] !== 0 && $currentTime > $data['expires_at']) {
                $expired++;
            }
        }

        return $expired;
    }

    /**
     * 取得快取路徑。
     */
    public function getCachePath(): string
    {
        return $this->cachePath;
    }

    /**
     * 設定快取路徑。
     */
    public function setCachePath(string $path): void
    {
        $this->cachePath = $path;
        $this->ensureDirectoryExists($path);
    }

    /**
     * 重設統計資料。
     */
    public function resetStats(): void
    {
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'deletes' => 0,
            'clears' => 0,
        ];
    }
}
