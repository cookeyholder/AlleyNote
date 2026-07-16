<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services;

use App\Infrastructure\Services\CacheService;
use App\Infrastructure\Services\RateLimitService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\UnitTestCase;

#[CoversClass(RateLimitService::class)]
class RateLimitServiceTest extends UnitTestCase
{
    #[Test]
    public function checkLimitReturnsFailClosedWhenCacheThrowsException(): void
    {
        $cache = $this->createMock(CacheService::class);
        $cache->method('get')
            ->willThrowException(new RuntimeException('Redis 連線失敗'));

        $service = new RateLimitService($cache);
        $result = $service->checkLimit('192.168.1.1');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allowed', $result);
        $this->assertFalse($result['allowed']);
        $this->assertArrayHasKey('remaining', $result);
        $this->assertSame(0, $result['remaining']);
        $this->assertArrayHasKey('reset', $result);
        $this->assertGreaterThan(time(), $result['reset']);
    }

    #[Test]
    public function isAllowedReturnsFalseWhenCacheThrowsException(): void
    {
        $cache = $this->createMock(CacheService::class);
        $cache->method('get')
            ->willThrowException(new RuntimeException('Redis 連線失敗'));

        $service = new RateLimitService($cache);
        $result = $service->isAllowed('192.168.1.1');

        $this->assertFalse($result);
    }
}
