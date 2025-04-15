<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\RateLimitService;
use App\Services\CacheService;
use Tests\TestCase;
use Mockery;

class RateLimitServiceTest extends TestCase
{
    private RateLimitService $rateLimitService;
    private $cacheMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheMock = Mockery::mock(CacheService::class);
        $this->rateLimitService = new RateLimitService($this->cacheMock);
    }

    /** @test */
    public function should_allow_first_request(): void
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

    /** @test */
    public function should_reject_when_limit_exceeded(): void
    {
        $ip = '127.0.0.1';
        $this->cacheMock->shouldReceive('get')
            ->once()
            ->with("rate_limit:{$ip}")
            ->andReturn(['count' => 60, 'reset' => time() + 60]);

        $result = $this->rateLimitService->isAllowed($ip);
        $this->assertFalse($result);
    }

    /** @test */
    public function should_handle_cache_failure_gracefully(): void
    {
        $ip = '127.0.0.1';
        $this->cacheMock->shouldReceive('get')
            ->once()
            ->with("rate_limit:{$ip}")
            ->andThrow(new \RuntimeException('快取錯誤'));

        // 快取失敗時應該允許請求
        $result = $this->rateLimitService->isAllowed($ip);
        $this->assertTrue($result);
    }

    /** @test */
    public function should_increment_request_count(): void
    {
        $ip = '127.0.0.1';
        $timeNow = time();
        $this->cacheMock->shouldReceive('get')
            ->once()
            ->with("rate_limit:{$ip}")
            ->andReturn(['count' => 5, 'reset' => $timeNow + 60]);

        $this->cacheMock->shouldReceive('set')
            ->once()
            ->with("rate_limit:{$ip}", Mockery::on(function ($data) use ($timeNow) {
                return $data['count'] === 6 && $data['reset'] >= $timeNow;
            }), 60);

        $result = $this->rateLimitService->isAllowed($ip);
        $this->assertTrue($result);
    }

    /** @test */
    public function should_handle_increment_failure(): void
    {
        $ip = '127.0.0.1';
        $this->cacheMock->shouldReceive('get')
            ->once()
            ->with("rate_limit:{$ip}")
            ->andReturn(['count' => 5, 'reset' => time() + 60]);

        $this->cacheMock->shouldReceive('set')
            ->once()
            ->with("rate_limit:{$ip}", Mockery::any(), 60)
            ->andThrow(new \RuntimeException('增量更新失敗'));

        // 增加計數失敗時應該允許請求
        $result = $this->rateLimitService->isAllowed($ip);
        $this->assertTrue($result);
    }

    /** @test */
    public function should_handle_expire_failure(): void
    {
        $ip = '127.0.0.1';
        $this->cacheMock->shouldReceive('get')
            ->once()
            ->with("rate_limit:{$ip}")
            ->andReturn(['count' => 5, 'reset' => time() + 60]);

        $this->cacheMock->shouldReceive('set')
            ->once()
            ->with("rate_limit:{$ip}", Mockery::any(), 60)
            ->andThrow(new \RuntimeException('設定過期時間失敗'));

        // 設定過期時間失敗時應該允許請求
        $result = $this->rateLimitService->isAllowed($ip);
        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
