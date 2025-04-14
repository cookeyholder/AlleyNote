<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\RateLimitService;
use App\Services\CacheService;
use Mockery;

class RateLimitServiceTest extends TestCase
{
    private $cacheMock;
    private $rateLimitService;

    protected function setUp(): void
    {
        $this->cacheMock = Mockery::mock(CacheService::class);
        $this->rateLimitService = new RateLimitService($this->cacheMock);
    }

    public function testIsAllowedReturnsTrueForFirstRequest()
    {
        $ip = '127.0.0.1';
        $this->cacheMock->shouldReceive('get')
            ->once()
            ->with("rate_limit:{$ip}")
            ->andReturn(null);

        $this->cacheMock->shouldReceive('increment')
            ->once()
            ->with("rate_limit:{$ip}");

        $this->cacheMock->shouldReceive('expire')
            ->once()
            ->with("rate_limit:{$ip}", 60);

        $result = $this->rateLimitService->isAllowed($ip);
        $this->assertTrue($result);
    }

    public function testIsAllowedReturnsFalseWhenLimitExceeded()
    {
        $ip = '127.0.0.1';
        $this->cacheMock->shouldReceive('get')
            ->once()
            ->with("rate_limit:{$ip}")
            ->andReturn(60);

        $result = $this->rateLimitService->isAllowed($ip);
        $this->assertFalse($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
