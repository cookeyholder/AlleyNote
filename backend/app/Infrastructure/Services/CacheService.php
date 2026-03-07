<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Shared\Contracts\CacheServiceInterface;

class CacheService implements CacheServiceInterface
{
    private string $cachePath;

    private const TTL = 3600;

    /** @var array{hits: int, misses: int, sets: int, deletes: int, size: int} */
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
        'size' => 0,
    ];

    public function __construct()
    {
        $this->cachePath = dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0o755, true);
        }
    }

    public function get(string $key): mixed
    {
        $filename = $this->getCacheFilename($key);
        if (!file_exists($filename)) {
            $this->incrementStat('misses');

            return null;
        }

        $data = file_get_contents($filename);
        if ($data === false) {
            $this->incrementStat('misses');

            return null;
        }

        $cacheData = json_decode($data, true);
        if (!is_array($cacheData) || !isset($cacheData['expiry'], $cacheData['data'])) {
            $this->incrementStat('misses');

            return null;
        }

        $expiry = $cacheData['expiry'];
        if (!is_int($expiry)) {
            if (is_string($expiry) && ctype_digit($expiry)) {
                $expiry = (int) $expiry;
            } else {
                $this->incrementStat('misses');

                return null;
            }
        }

        if (time() > $expiry) {
            $this->delete($key);
            $this->incrementStat('misses');

            return null;
        }

        $this->incrementStat('hits');

        return $cacheData['data'];
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $filename = $this->getCacheFilename($key);
        $this->incrementStat('sets');

        $cacheData = [
            'key' => $key,
            'expiry' => time() + ($ttl ?: self::TTL),
            'data' => $value,
        ];

        $result = file_put_contents($filename, (json_encode($cacheData) ?: '')) !== false;
        if ($result) {
            $this->updateIndex($key);
        }

        return $result;
    }

    public function delete(string $key): bool
    {
        $filename = $this->getCacheFilename($key);
        if (!file_exists($filename)) {
            return true;
        }

        $result = unlink($filename);
        if ($result) {
            $this->removeFromIndex($key);
            $this->incrementStat('deletes');
        }

        return $result;
    }

    public function deletePattern(string $pattern): int
    {
        if (strpos($pattern, '*') === false) {
            return $this->delete($pattern) ? 1 : 0;
        }

        // 注意：此方法會遍歷索引中的所有 key，當 key 數量很大時會有 I/O 與 CPU 成本。
        // 若需要高頻率/大量 pattern 刪除，建議改用支援原生 pattern matching 的快取驅動（例如 Redis）。
        $quotedPattern = preg_quote($pattern, '/');
        $regex = '/^' . str_replace('\\*', '.*', $quotedPattern) . '$/';

        $index = $this->getIndex();
        $deletedCount = 0;

        foreach ($index as $key) {
            if (preg_match($regex, $key)) {
                if ($this->delete($key)) {
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }

    public function clear(): bool
    {
        $files = glob($this->cachePath . '/*');
        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $this->clearIndex();

        return true;
    }

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $value = $this->get($key);
        if ($value === null) {
            $value = $callback();
            if ($value !== null) {
                $this->set($key, $value, $ttl ?: self::TTL);
            }
        }

        return $value;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function getMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeKey($key);
            $result[$normalizedKey] = $this->get($normalizedKey);
        }

        return $result;
    }

    public function setMultiple(array $values, int $ttl = 3600): bool
    {
        foreach ($values as $key => $value) {
            $this->set($this->normalizeKey($key), $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($this->normalizeKey($key));
        }

        return true;
    }

    private function getCacheFilename(string $key): string
    {
        return $this->cachePath . '/' . hash('sha256', $key) . '.cache';
    }

    // --- 索引管理邏輯 ---

    private function getIndexPath(): string
    {
        return $this->cachePath . '/_index.json';
    }

    /**
     * @return array<int, string>
     */
    private function getIndex(): array
    {
        $path = $this->getIndexPath();
        if (!file_exists($path)) {
            return [];
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return [];
        }

        $decoded = json_decode($contents, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn(mixed $value): ?string => is_string($value) ? $value : null,
            $decoded,
        )));
    }

    /** @param 'hits'|'misses'|'sets'|'deletes'|'size' $key */
    private function incrementStat(string $key): void
    {
        $this->stats[$key]++;
    }

    private function normalizeKey(mixed $key): string
    {
        if (is_string($key) || is_int($key)) {
            return (string) $key;
        }

        return '';
    }

    private function updateIndex(string $key): void
    {
        $index = $this->getIndex();
        if (!in_array($key, $index, true)) {
            $index[] = $key;
            file_put_contents($this->getIndexPath(), json_encode($index));
        }
    }

    private function removeFromIndex(string $key): void
    {
        $index = $this->getIndex();
        $pos = array_search($key, $index, true);
        if ($pos !== false) {
            unset($index[$pos]);
            file_put_contents($this->getIndexPath(), json_encode(array_values($index)));
        }
    }

    private function clearIndex(): void
    {
        if (file_exists($this->getIndexPath())) {
            unlink($this->getIndexPath());
        }
    }

    public function getStats(): array
    {
        return $this->stats; // 簡化回傳，僅作示範
    }
}
