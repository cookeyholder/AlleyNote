<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\CacheService;
use Tests\TestCase;

/**
 * @requires extension redis
 */
class RedisIntegrationTest extends TestCase
{
    private CacheService $cacheService;
    
    /**
     * @var \Redis
     */
    private $redis;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 確保 Redis 擴充模組已安裝
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis 擴充模組未安裝');
        }

        $this->redis = new \Redis();
        try {
            $this->redis->connect(
                getenv('REDIS_HOST') ?: 'redis',
                (int) (getenv('REDIS_PORT') ?: 6379)
            );
        } catch (\RedisException $e) {
            $this->markTestSkipped('無法連線到 Redis 伺服器');
        }
        
        $this->cacheService = new CacheService();
    }

    /** @test */
    public function should_connect_to_redis_in_docker(): void
    {
        $this->assertTrue($this->redis->ping() === '+PONG');
    }

    /** @test */
    public function should_store_and_retrieve_data_from_redis(): void
    {
        $key = 'test:' . uniqid();
        $value = ['id' => 1, 'name' => '測試資料'];
        $ttl = 3600;

        // 儲存資料
        $result = $this->cacheService->remember($key, function () use ($value) {
            return $value;
        }, $ttl);

        // 驗證儲存結果
        $this->assertEquals($value, $result);

        // 直接從 Redis 讀取
        $storedValue = json_decode($this->redis->get($key), true);
        $this->assertEquals($value, $storedValue);

        // 檢查 TTL
        $remainingTtl = $this->redis->ttl($key);
        $this->assertGreaterThan(0, $remainingTtl);
        $this->assertLessThanOrEqual($ttl, $remainingTtl);
    }

    /** @test */
    public function should_handle_concurrent_access(): void
    {
        $key = 'test:concurrent:' . uniqid();
        $value = ['counter' => 0];
        $iterations = 10;

        // 模擬多個並發請求
        for ($i = 0; $i < $iterations; $i++) {
            $this->cacheService->remember($key, function () use ($value, $i) {
                $value['counter'] = $i;
                return $value;
            }, 60);
        }

        // 驗證最終結果
        $finalValue = json_decode($this->redis->get($key), true);
        $this->assertEquals($iterations - 1, $finalValue['counter']);
    }

    /** @test */
    public function should_handle_large_data_sets(): void
    {
        $key = 'test:large:' . uniqid();
        $largeArray = [];
        
        // 建立大量測試資料
        for ($i = 0; $i < 1000; $i++) {
            $largeArray[] = [
                'id' => $i,
                'name' => "測試資料 {$i}",
                'data' => str_repeat('x', 100)
            ];
        }

        // 儲存大量資料
        $result = $this->cacheService->remember($key, function () use ($largeArray) {
            return $largeArray;
        }, 3600);

        // 驗證資料完整性
        $this->assertEquals($largeArray, $result);
        $this->assertEquals(
            $largeArray,
            json_decode($this->redis->get($key), true)
        );
    }

    /** @test */
    public function should_handle_cache_expiration(): void
    {
        $key = 'test:expiration:' . uniqid();
        $value = ['id' => 1, 'name' => '測試資料'];
        $shortTtl = 1; // 1 秒

        // 儲存短期快取
        $this->cacheService->remember($key, function () use ($value) {
            return $value;
        }, $shortTtl);

        // 驗證資料已儲存
        $this->assertEquals(
            $value,
            json_decode($this->redis->get($key), true)
        );

        // 等待快取過期
        sleep(2);

        // 驗證資料已過期
        $this->assertNull($this->redis->get($key));
    }

    /** @test */
    public function should_handle_cache_deletion(): void
    {
        $key = 'test:deletion:' . uniqid();
        $value = ['id' => 1, 'name' => '測試資料'];

        // 儲存資料
        $this->cacheService->remember($key, function () use ($value) {
            return $value;
        }, 3600);

        // 驗證資料已儲存
        $this->assertNotNull($this->redis->get($key));

        // 刪除快取
        $this->cacheService->delete($key);

        // 驗證資料已刪除
        $this->assertNull($this->redis->get($key));
    }

    protected function tearDown(): void
    {
        // 清理測試資料
        if (isset($this->redis)) {
            $this->redis->flushDB();
            $this->redis->close();
        }
        parent::tearDown();
    }
}
