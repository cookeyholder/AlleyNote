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
    public function shouldAllowFirstRequest(): void
    {
        $ip = '127.0.0.1';
        $timeNow = time();

        $this->cacheMock->shouldReceive('get')
            ->once()
            ->with("rate_limit:{$ip}")
            ->andReturn(null);

        $this->cacheMock->shouldReceive('set')
            ->once()
            ->with("rate_limit:{$ip}", Mockery::on(function ($data) use ($timeNow) {
                return $data['count'] === 1 && $data['reset'] >= $timeNow;
            }), 60);

        $result = $this->rateLimitService->isAllowed($ip);
        $this->assertTrue($result);
    }

    /** @test */
    public function shouldRejectWhenLimitExceeded(): void
    {
        $ip = '127.0.0.1';
        $timeNow = time();
        $this->cacheMock->shouldReceive('get')
            ->once()
            ->with("rate_limit:{$ip}")
            ->andReturn(['count' => 60, 'reset' => $timeNow + 60]);

        // 當超過限制時，不應該再呼叫 set
        $result = $this->rateLimitService->isAllowed($ip);
        $this->assertFalse($result);
    }

    /** @test */
    public function shouldHandleCacheFailureGracefully(): void
    {
        $ip = '127.0.0.1';
        $this->cacheMock->shouldReceive('get')
            ->once()
            ->with("rate_limit:{$ip}")
            ->andThrow(new \RuntimeException('快取錯誤'));

        // 不需要設定 set 的預期，因為在異常情況下不會呼叫 set
        $result = $this->rateLimitService->isAllowed($ip);
        $this->assertTrue($result);
        $this->assertTrue($result, '當快取服務失敗時應該允許請求');
    }

    /** @test */
    public function shouldIncrementRequestCount(): void
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
    public function shouldHandleSetFailure(): void
    {
        $ip = '127.0.0.1';
        $timeNow = time();
        $this->cacheMock->shouldReceive('get')
            ->once()
            ->with("rate_limit:{$ip}")
            ->andReturn(['count' => 5, 'reset' => $timeNow + 60]);

        $this->cacheMock->shouldReceive('set')
            ->once()
            ->with("rate_limit:{$ip}", Mockery::any(), 60)
            ->andThrow(new \RuntimeException('快取更新失敗'));

        // 快取更新失敗時應該允許請求
        $result = $this->rateLimitService->isAllowed($ip);
        $this->assertTrue($result);
        $this->assertTrue($result, '當快取更新失敗時應該允許請求');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
