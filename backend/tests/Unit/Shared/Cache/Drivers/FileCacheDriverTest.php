<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Cache\Drivers;

use App\Shared\Cache\Drivers\FileCacheDriver;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * FileCacheDriver 單元測試.
 *
 * 使用暫存目錄實測檔案讀寫、TTL 過期與清理行為。
 */
final class FileCacheDriverTest extends UnitTestCase
{
    private string $tempDir;

    private FileCacheDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/alleynote_file_cache_test_' . uniqid();
        $this->driver = new FileCacheDriver($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    #[Test]
    public function constructorCreatesDefaultDirectoryWhenEmptyPathGiven(): void
    {
        $driver = new FileCacheDriver('');
        $path = $driver->getCachePath();
        $this->assertDirectoryExists($path);
        $this->assertTrue($driver->isAvailable());
    }

    #[Test]
    public function putAndRetrieveValue(): void
    {
        $this->assertTrue($this->driver->put('key1', 'value1', 60));

        $this->assertSame('value1', $this->driver->get('key1'));
        $this->assertTrue($this->driver->has('key1'));

        // 支援複雜型別
        $arrayData = ['a' => 1, 'b' => [2, 3]];
        $this->driver->put('complex', $arrayData, 60);
        $this->assertSame($arrayData, $this->driver->get('complex'));
    }

    #[Test]
    public function getReturnsDefaultForMissingKey(): void
    {
        $this->assertNull($this->driver->get('missing'));
        $this->assertSame('fallback', $this->driver->get('missing', 'fallback'));
    }

    #[Test]
    public function getTreatsExpiredEntryAsMissAndRemovesFile(): void
    {
        $this->writeRawEntry('expired', 'old-value', time() - 10);

        $this->assertFalse($this->driver->has('expired'));
        $this->assertNull($this->driver->get('expired'));
        // 檔案應已被刪除
        $this->assertFalse(is_file($this->buildFilePath('expired')));
    }

    #[Test]
    public function getHandlesStructurallyInvalidCacheContent(): void
    {
        // 內容可反序列化但結構不符快取格式，應視為未命中且不觸發警告
        $filePath = $this->buildFilePath('corrupted');
        file_put_contents($filePath, serialize('plain-string'));

        $this->assertNull($this->driver->get('corrupted'));
        $this->assertFalse($this->driver->has('corrupted'));
    }

    #[Test]
    public function hasReturnsFalseForMissingKey(): void
    {
        $this->assertFalse($this->driver->has('nothing'));
    }

    #[Test]
    public function forgetRemovesEntry(): void
    {
        $this->driver->put('gone', 'value', 60);
        $this->assertTrue($this->driver->forget('gone'));
        $this->assertFalse($this->driver->has('gone'));
        $this->assertSame(1, $this->driver->getStats()['deletes']);

        // 刪除不存在的鍵仍視為成功
        $this->assertTrue($this->driver->forget('never-existed'));
    }

    #[Test]
    public function flushClearsAllEntries(): void
    {
        $this->driver->put('f1', 1, 60);
        $this->driver->put('f2', 2, 60);

        $this->assertTrue($this->driver->flush());
        $this->assertFalse($this->driver->has('f1'));
        $this->assertFalse($this->driver->has('f2'));
        $stats = $this->driver->getStats();
        $this->assertSame(1, $stats['clears']);
        $this->assertSame(0, $stats['total_files']);
    }

    #[Test]
    public function manyAndPutManyOperateInBulk(): void
    {
        $this->assertTrue($this->driver->putMany(['m1' => 'v1', 'm2' => 'v2'], 60));

        $result = $this->driver->many(['m1', 'm2', 'm3']);
        $this->assertSame('v1', $result['m1']);
        $this->assertSame('v2', $result['m2']);
        $this->assertNull($result['m3']);
    }

    #[Test]
    public function forgetManyDeletesKeysInBatch(): void
    {
        $this->driver->putMany(['d1' => 1, 'd2' => 2], 60);

        $this->assertTrue($this->driver->forgetMany(['d1', 'd2']));
        $this->assertFalse($this->driver->has('d1'));
        $this->assertFalse($this->driver->has('d2'));
    }

    #[Test]
    public function incrementAndDecrementUpdateNumericEntries(): void
    {
        $this->assertSame(5, $this->driver->increment('counter', 5));
        $this->assertSame(8, $this->driver->increment('counter', 3));
        $this->assertSame(6, $this->driver->decrement('counter', 2));

        // 非數值內容：遞增直接以 value 覆覆寫，遞減以 -value 起始
        $this->driver->put('text', 'abc', 60);
        $this->assertSame(4, $this->driver->increment('text', 4));
        $this->assertSame(-7, $this->driver->decrement('fresh', 7));
    }

    #[Test]
    public function rememberExecutesCallbackOnlyOnMiss(): void
    {
        $calls = 0;
        $callback = static function () use (&$calls): string {
            $calls++;

            return 'computed';
        };

        $first = $this->driver->remember('memo', $callback, 60);
        $second = $this->driver->remember('memo', $callback, 60);
        $this->assertSame('computed', $first);
        $this->assertSame('computed', $second);
        $this->assertSame(1, $calls);
    }

    #[Test]
    public function rememberForeverStoresWithoutExpiry(): void
    {
        $value = $this->driver->rememberForever('forever', static fn(): string => 'kept');
        $this->assertSame('kept', $value);

        // 直接檢查底層資料：expires_at 為 0 表示永不過期
        $content = file_get_contents($this->buildFilePath('forever'));
        $this->assertIsString($content);
        /** @var array{value: mixed, expires_at: int}|false $data */
        $data = unserialize($content);
        $this->assertIsArray($data);
        $this->assertSame(0, $data['expires_at']);
    }

    #[Test]
    public function rememberDoesNotStoreNullResult(): void
    {
        $result = $this->driver->remember('null-memo', static fn(): ?string => null, 60);
        $this->assertNull($result);
        $this->assertFalse($this->driver->has('null-memo'));
    }

    #[Test]
    public function getStatsReportsCountersAndFiles(): void
    {
        $this->driver->put('s1', 'v', 60);
        $this->driver->get('s1');
        $this->driver->get('missing-key');

        $stats = $this->driver->getStats();
        $this->assertSame(1, $stats['hits']);
        $this->assertSame(1, $stats['misses']);
        $this->assertSame(1, $stats['sets']);
        $this->assertSame(50.0, $stats['hit_rate']);
        $this->assertSame(1, $stats['total_files']);
        $this->assertGreaterThan(0, $stats['total_size']);
        $this->assertSame($this->tempDir, $stats['cache_path']);
        $this->assertSame(0, $stats['expired_files']);
    }

    #[Test]
    public function getStatsCountsExpiredFiles(): void
    {
        $this->writeRawEntry('stale', 'v', time() - 5);
        $this->driver->put('fresh-entry', 'v', 3600);

        $stats = $this->driver->getStats();
        $this->assertSame(1, $stats['expired_files']);
        $this->assertSame(2, $stats['total_files']);
    }

    #[Test]
    public function getConnectionReturnsCachePath(): void
    {
        $this->assertSame($this->tempDir, $this->driver->getConnection());
    }

    #[Test]
    public function cleanupRemovesOnlyExpiredEntries(): void
    {
        $this->writeRawEntry('e1', 'v', time() - 100);
        $this->writeRawEntry('e2', 'v', time() - 200);
        $this->driver->put('keep', 'v', 3600);
        // 永不過期的項目（expires_at 為 0）不應被清理
        $this->driver->put('permanent', 'v', 0);

        $cleaned = $this->driver->cleanup();
        $this->assertSame(2, $cleaned);
        $this->assertTrue($this->driver->has('keep'));
        $this->assertTrue($this->driver->has('permanent'));
        $this->assertFalse($this->driver->has('e1'));
        $this->assertFalse($this->driver->has('e2'));
    }

    #[Test]
    public function forgetPatternMatchesHashBasedFilenames(): void
    {
        $keyA = 'alpha';
        $keyB = 'beta';
        $this->driver->put($keyA, 'v', 60);
        $this->driver->put($keyB, 'v', 60);

        // 檔名為鍵的 sha256 雜湊值，因此模式需以雜湊字首匹配
        $prefixOfA = substr(hash('sha256', $keyA), 0, 3) . '*';
        $deleted = $this->driver->forgetPattern($prefixOfA);
        $this->assertSame(1, $deleted);
        $this->assertFalse($this->driver->has($keyA));
        $this->assertTrue($this->driver->has($keyB));

        // 無符合項目時回傳 0
        $this->assertSame(0, $this->driver->forgetPattern('zzz*'));
    }

    #[Test]
    public function setCachePathSwitchesStorageLocation(): void
    {
        $newPath = sys_get_temp_dir() . '/alleynote_file_cache_alt_' . uniqid();

        try {
            $this->driver->setCachePath($newPath);
            $this->assertSame($newPath, $this->driver->getCachePath());
            $this->assertDirectoryExists($newPath);

            $this->driver->put('moved', 'v', 60);
            $this->assertSame('v', $this->driver->get('moved'));
        } finally {
            $this->removeDirectory($newPath);
        }
    }

    #[Test]
    public function resetStatsRestoresInitialCounters(): void
    {
        $this->driver->put('r1', 'v', 60);
        $this->driver->get('r1');
        $this->driver->get('missing');
        $this->driver->resetStats();

        $stats = $this->driver->getStats();
        $this->assertSame(0, $stats['hits']);
        $this->assertSame(0, $stats['misses']);
        $this->assertSame(0, $stats['sets']);
        $this->assertSame(0, $stats['deletes']);
        $this->assertSame(0, $stats['clears']);
    }

    /**
     * 取得指定鍵對應的快取檔案路徑（與生產碼演算法一致）。
     */
    private function buildFilePath(string $key): string
    {
        return $this->tempDir . '/' . hash('sha256', $key) . '.cache';
    }

    /**
     * 直接寫入指定過期時間的快取項目，避免測試等待真實 TTL。
     *
     * @param array<string, mixed>|string $value
     */
    private function writeRawEntry(string $key, mixed $value, int $expiresAt): void
    {
        $filePath = $this->buildFilePath($key);
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0o755, true);
        }
        $payload = [
            'value'      => $value,
            'expires_at' => $expiresAt,
            'created_at' => time(),
        ];
        file_put_contents($filePath, serialize($payload));
    }

    /**
     * 遞迴刪除目錄及其內容。
     */
    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $items = scandir($directory);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $fullPath = $directory . '/' . $item;
            if (is_dir($fullPath)) {
                $this->removeDirectory($fullPath);
            } elseif (is_file($fullPath)) {
                unlink($fullPath);
            }
        }
        rmdir($directory);
    }
}
