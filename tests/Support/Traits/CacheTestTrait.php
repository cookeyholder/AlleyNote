<?php

declare(strict_types=1);

namespace Tests\Support\Traits;

use App\Infrastructure\Services\CacheService;
use Mockery;
use Mockery\MockInterface;

/**
 * 快取測試功能 Trait.
 *
 * 提供模擬快取服務的設定和測試輔助方法
 */
trait CacheTestTrait
{
    protected CacheService|MockInterface $cache;

    /**
     * @var array<mixed>
     */
    protected static array<mixed> $cacheStorage = [];

    /**
     * 設定模擬快取服務.
     */
    protected function setUpCache(): void
    {
        // 清除舊的快取資料
        self::$cacheStorage = [];

        // 建立快取服務模擬物件
        $this->cache = Mockery::mock(CacheService::class);

        // 設定 get 方法
        $this->cache->shouldReceive('get')
            ->andReturnUsing(function (string $key) {
                return array_key_exists($key, self::$cacheStorage) ? self::$cacheStorage[$key] : null;
            });

        // 設定 set 方法
        $this->cache->shouldReceive('set')
            ->andReturnUsing(function (string $key, $value, ?int $ttl = null): bool {
                self::$cacheStorage[$key] = $value;

                return true;
            });

        // 設定 put 方法 (set 的別名)
        $this->cache->shouldReceive('put')
            ->andReturnUsing(function (string $key, $value, ?int $ttl = null): bool {
                self::$cacheStorage[$key] = $value;

                return true;
            });

        // 設定 has 方法
        $this->cache->shouldReceive('has')
            ->andReturnUsing(function (string $key): bool {
                return array_key_exists($key, self::$cacheStorage);
            });

        // 設定 forget/delete 方法
        $this->cache->shouldReceive('forget')
            ->andReturnUsing(function (string $key): bool {
                unset(self::$cacheStorage[$key]);

                return true;
            });

        $this->cache->shouldReceive('delete')
            ->andReturnUsing(function (string $key): bool {
                unset(self::$cacheStorage[$key]);

                return true;
            });

        // 設定 clear 方法
        $this->cache->shouldReceive('clear')
            ->andReturnUsing(function (): bool {
                self::$cacheStorage = [];

                return true;
            });

        // 設定 tags 方法
        $this->cache->shouldReceive('tags')
            ->andReturn($this->cache);

        // 設定 remember 方法
        $this->cache->shouldReceive('remember')
            ->andReturnUsing(function (string $key, callable $callback) {
                if (!array_key_exists($key, self::$cacheStorage)) {
                    self::$cacheStorage[$key] = $callback();
                }

                return self::$cacheStorage[$key];
            });
    }

    /**
     * 清理快取.
     */
    protected function tearDownCache(): void
    {
        self::$cacheStorage = [];
    }

    /**
     * 取得目前快取中的所有資料.
     *
     * @return array<mixed>
     */
    protected function getCacheStorage(): array<mixed>
    {
        return self::$cacheStorage;
    }

    /**
     * 檢查特定的快取鍵是否存在.
     */
    protected function assertCacheHasKey(string $key): void
    {
        $this->assertArrayHasKey($key, self::$cacheStorage, "快取中應該存在鍵: {$key}");
    }

    /**
     * 檢查特定的快取鍵是否不存在.
     */
    protected function assertCacheNotHasKey(string $key): void
    {
        $this->assertArrayNotHasKey($key, self::$cacheStorage, "快取中不應該存在鍵: {$key}");
    }

    /**
     * 檢查快取值是否相符.
     */
    protected function assertCacheValue(string $key, mixed $expectedValue): void
    {
        $this->assertCacheHasKey($key);
        $this->assertEquals($expectedValue, self::$cacheStorage[$key], "快取值不符合預期: {$key}");
    }

    /**
     * 檢查快取是否為空.
     */
    protected function assertCacheIsEmpty(): void
    {
        $this->assertEmpty(self::$cacheStorage, '快取應該為空');
    }

    /**
     * 設定特定快取值（用於測試準備）.
     */
    protected function setCacheValue(string $key, mixed $value): void
    {
        self::$cacheStorage[$key] = $value;
    }
}
