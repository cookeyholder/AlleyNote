<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\CacheService;
use Tests\TestCase;
use Mockery;

/**
 * @requires extension redis
 */
class CacheServiceTest extends TestCase
{
    private CacheService $cacheService;
    
    /**
     * @var \Redis|\Mockery\MockInterface
     */
    private $redis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redis = Mockery::mock(\Redis::class);
        $this->cacheService = new CacheService();
    }

    /** @test */
    public function should_store_and_retrieve_data(): void
    {
        // 準備測試資料
        $key = 'test_key';
        $value = ['id' => 1, 'name' => '測試資料'];
        $ttl = 3600;

        // 設定 Redis mock 期望行為
        $this->redis->shouldReceive('connect')
            ->once()
            ->with('redis', 6379)
            ->andReturn(true);

        $this->redis->shouldReceive('setex')
            ->once()
            ->with($key, $ttl, json_encode($value))
            ->andReturn(true);

        $this->redis->shouldReceive('get')
            ->once()
            ->with($key)
            ->andReturn(json_encode($value));

        // 執行測試
        $result = $this->cacheService->remember($key, function () use ($value) {
            return $value;
        }, $ttl);

        // 驗證結果
        $this->assertEquals($value, $result);
    }

    /** @test */
    public function should_handle_connection_failure(): void
    {
        // 設定 Redis mock 期望行為
        $this->redis->shouldReceive('connect')
            ->times(3)  // 重試 3 次
            ->andThrow(new \RedisException('Connection failed'));

        // 準備測試資料
        $key = 'test_key';
        $value = ['id' => 1, 'name' => '測試資料'];

        // 執行測試
        $result = $this->cacheService->remember($key, function () use ($value) {
            return $value;
        });

        // 驗證結果：連線失敗時應該直接返回原始資料
        $this->assertEquals($value, $result);
    }

    /** @test */
    public function should_handle_redis_errors(): void
    {
        // 設定 Redis mock 期望行為
        $this->redis->shouldReceive('connect')
            ->once()
            ->andReturn(true);

        $this->redis->shouldReceive('setex')
            ->once()
            ->andThrow(new \RedisException('Redis error'));

        // 準備測試資料
        $key = 'test_key';
        $value = ['id' => 1, 'name' => '測試資料'];

        // 執行測試
        $result = $this->cacheService->remember($key, function () use ($value) {
            return $value;
        });

        // 驗證結果：Redis 錯誤時應該直接返回原始資料
        $this->assertEquals($value, $result);
    }

    /** @test */
    public function should_handle_concurrent_requests(): void
    {
        // 準備測試資料
        $key = 'test_key';
        $value = ['id' => 1, 'name' => '測試資料'];
        $ttl = 3600;

        // 設定 Redis mock 期望行為
        $this->redis->shouldReceive('connect')
            ->once()
            ->andReturn(true);

        $this->redis->shouldReceive('get')
            ->times(3)
            ->with($key)
            ->andReturn(null, null, json_encode($value));

        $this->redis->shouldReceive('setex')
            ->once()
            ->with($key, $ttl, json_encode($value))
            ->andReturn(true);

        // 模擬三個並發請求
        $results = [];
        for ($i = 0; $i < 3; $i++) {
            $results[] = $this->cacheService->remember($key, function () use ($value) {
                return $value;
            }, $ttl);
        }

        // 驗證結果：所有請求都應該得到相同的結果
        foreach ($results as $result) {
            $this->assertEquals($value, $result);
        }
    }

    /** @test */
    public function should_clear_cache(): void
    {
        // 設定 Redis mock 期望行為
        $this->redis->shouldReceive('connect')
            ->once()
            ->andReturn(true);

        $this->redis->shouldReceive('flushDB')
            ->once()
            ->andReturn(true);

        // 執行測試
        $result = $this->cacheService->clear();

        // 驗證結果
        $this->assertTrue($result);
    }

    /** @test */
    public function should_delete_specific_key(): void
    {
        // 設定 Redis mock 期望行為
        $this->redis->shouldReceive('connect')
            ->once()
            ->andReturn(true);

        $this->redis->shouldReceive('del')
            ->once()
            ->with('test_key')
            ->andReturn(1);

        // 執行測試
        $result = $this->cacheService->delete('test_key');

        // 驗證結果
        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
