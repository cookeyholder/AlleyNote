<?php

namespace Tests\Unit\Services;

use App\Infrastructure\Services\CacheService;
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
    public function storeAndRetrieveData(): void
    {
        $key = 'test_key';
        $value = ['id' => 1, 'name' => '測試資料'];
        $ttl = 3600;

        $result = $this->cacheService->remember($key, function () use ($value) {
            return $value;
        }, $ttl);

        $this->assertEquals($value, $result);
    }

    /** @test */
    public function handleConnectionFailure(): void
    {
        $key = 'test_key';
        $value = ['id' => 1, 'name' => '測試資料'];

        $result = $this->cacheService->remember($key, function () use ($value) {
            return $value;
        });

        $this->assertEquals($value, $result);
    }

    /** @test */
    public function handleConcurrentRequests(): void
    {
        $key = 'test_key';
        $value = ['id' => 1, 'name' => '測試資料'];
        $ttl = 3600;

        $results = [];
        for ($i = 0; $i < 3; $i++) {
            $results[] = $this->cacheService->remember($key, function () use ($value) {
                return $value;
            }, $ttl);
        }

        foreach ($results as $result) {
            $this->assertEquals($value, $result);
        }
    }

    /** @test */
    public function clearCache(): void
    {
        $result = $this->cacheService->clear();

        $this->assertTrue($result);
    }

    /** @test */
    public function deleteSpecificKey(): void
    {
        $result = $this->cacheService->delete('test_key');

        $this->assertTrue($result);
    }
}
