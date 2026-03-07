<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Infrastructure\Services\CacheService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

class CacheServiceTest extends UnitTestCase
{
    private CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = new CacheService();
    }

    #[Test]
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

    #[Test]
    public function handleConnectionFailure(): void
    {
        $key = 'test_key';
        $value = ['id' => 1, 'name' => '測試資料'];

        $result = $this->cacheService->remember($key, function () use ($value) {
            return $value;
        });

        $this->assertEquals($value, $result);
    }

    #[Test]
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

    #[Test]
    public function clearCache(): void
    {
        $result = $this->cacheService->clear();

        $this->assertTrue($result);
    }

    #[Test]
    public function deleteSpecificKey(): void
    {
        $result = $this->cacheService->delete('test_key');

        $this->assertTrue($result);
    }

    #[Test]
    public function deletePatternRemovesOnlyMatchingNamespaceKeys(): void
    {
        $this->cacheService->set('alleynote:post:1', 'post1');
        $this->cacheService->set('alleynote:post:2', 'post2');
        $this->cacheService->set('alleynote:user:1', 'user1');
        $this->cacheService->set('other:post:1', 'other');

        $deleted = $this->cacheService->deletePattern('alleynote:post:*');

        $this->assertSame(2, $deleted);
        $this->assertFalse($this->cacheService->has('alleynote:post:1'));
        $this->assertFalse($this->cacheService->has('alleynote:post:2'));
        $this->assertTrue($this->cacheService->has('alleynote:user:1'));
        $this->assertTrue($this->cacheService->has('other:post:1'));
    }
}
