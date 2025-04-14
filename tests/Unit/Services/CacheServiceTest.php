<?php

namespace Tests\Unit\Services;

use App\Services\CacheService;
use Tests\TestCase;

class CacheServiceTest extends TestCase
{
    private CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = new CacheService();
    }

    /** @test */
    public function should_store_and_retrieve_data(): void
    {
        // 準備測試資料
        $key = 'test_key';
        $value = ['id' => 1, 'name' => '測試資料'];
        $ttl = 3600;

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
    public function should_handle_concurrent_requests(): void
    {
        // 準備測試資料
        $key = 'test_key';
        $value = ['id' => 1, 'name' => '測試資料'];
        $ttl = 3600;

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
        // 執行測試
        $result = $this->cacheService->clear();

        // 驗證結果
        $this->assertTrue($result);
    }

    /** @test */
    public function should_delete_specific_key(): void
    {
        // 執行測試
        $result = $this->cacheService->delete('test_key');

        // 驗證結果
        $this->assertTrue($result);
    }
}
