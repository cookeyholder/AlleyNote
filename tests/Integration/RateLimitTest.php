<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Infrastructure\Services\CacheService;
use App\Infrastructure\Services\RateLimitService;
use Exception;
use Mockery;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    private RateLimitService $rateLimitService;

    private CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock CacheService
        $this->cacheService = Mockery::mock(CacheService::class);
        $this->rateLimitService = new RateLimitService($this->cacheService);

        // 設定快取預設行為
        $this->cacheService->shouldReceive('get')
            ->andReturn(null)
            ->byDefault();

        $this->cacheService->shouldReceive('set')
            ->andReturn(true)
            ->byDefault();

        $this->cacheService->shouldReceive('increment')
            ->andReturn(1)
            ->byDefault();
    }

    /** @test */
    public function shouldLimitRateSuccessfully(): void
    {
        $ip = '192.168.1.1';
        $maxAttempts = 60;

        // 模擬正常請求
        $this->cacheService->shouldReceive('get')
            ->with("rate_limit:{$ip}")
            ->andReturn(null);

        $this->cacheService->shouldReceive('increment')
            ->with("rate_limit:{$ip}")
            ->andReturn(1);

        $result = $this->rateLimitService->checkLimit($ip);

        $this->assertTrue($result['allowed'], '正常請求應該被允許');
    }

    /** @test */
    public function shouldResetLimitAfterTimeWindow(): void
    {
        $ip = '192.168.1.2';

        // 模擬達到限制
        $this->cacheService->shouldReceive('get')
            ->with("rate_limit:{$ip}")
            ->andReturn(['count' => 61, 'reset' => time() + 60]); // 超過限制

        $result = $this->rateLimitService->checkLimit($ip);

        $this->assertFalse($result['allowed'], '超過限制的請求應該被拒絕');

        // 模擬時間窗口重置 - 返回過期的資料，讓服務重置計數器
        $this->cacheService->shouldReceive('get')
            ->with("rate_limit:{$ip}")
            ->andReturn(['count' => 61, 'reset' => time() - 10]); // 已過期

        $this->cacheService->shouldReceive('set')
            ->with("rate_limit:{$ip}", Mockery::any(), 60)
            ->andReturn(true);

        $resetResult = $this->rateLimitService->checkLimit($ip);

        $this->assertTrue($resetResult['allowed'], '重置後的請求應該被允許');
    }

    /** @test */
    public function shouldHandleDifferentIpsIndependently(): void
    {
        $ip1 = '192.168.1.3';
        $ip2 = '192.168.1.4';

        // IP1 正常
        $this->cacheService->shouldReceive('get')
            ->with("rate_limit:{$ip1}")
            ->andReturn(['count' => 30, 'reset' => time() + 60]);

        $this->cacheService->shouldReceive('set')
            ->with("rate_limit:{$ip1}", Mockery::any(), 60)
            ->andReturn(true);

        $result1 = $this->rateLimitService->checkLimit($ip1);

        // IP2 正常
        $this->cacheService->shouldReceive('get')
            ->with("rate_limit:{$ip2}")
            ->andReturn(['count' => 10, 'reset' => time() + 60]);

        $this->cacheService->shouldReceive('set')
            ->with("rate_limit:{$ip2}", Mockery::any(), 60)
            ->andReturn(true);

        $result2 = $this->rateLimitService->checkLimit($ip2);

        $this->assertTrue($result1['allowed'], 'IP1 應該被允許');
        $this->assertTrue($result2['allowed'], 'IP2 應該被允許');
    }

    /** @test */
    public function shouldHandleServiceUnavailability(): void
    {
        $ip = '192.168.1.5';

        // 模擬快取服務錯誤
        $this->cacheService->shouldReceive('get')
            ->with("rate_limit:{$ip}")
            ->andThrow(new Exception('快取錯誤'));

        // 當快取服務不可用時，應該允許請求以確保服務可用性
        $result = $this->rateLimitService->checkLimit($ip);

        $this->assertTrue($result['allowed'], '快取服務錯誤時應該允許請求');
    }

    /** @test */
    public function shouldIncrementCounterCorrectly(): void
    {
        $ip = '192.168.1.6';

        // 第一次請求
        $this->cacheService->shouldReceive('get')
            ->with("rate_limit:{$ip}")
            ->andReturn(null);

        $this->cacheService->shouldReceive('set')
            ->with("rate_limit:{$ip}", Mockery::any(), 60)
            ->andReturn(true);

        $this->rateLimitService->checkLimit($ip);

        // 第二次請求
        $this->cacheService->shouldReceive('get')
            ->with("rate_limit:{$ip}")
            ->andReturn(['count' => 1, 'reset' => time() + 60]);

        $this->cacheService->shouldReceive('set')
            ->with("rate_limit:{$ip}", Mockery::any(), 60)
            ->andReturn(true);

        $result = $this->rateLimitService->checkLimit($ip);

        $this->assertTrue($result['allowed'], '計數器應該正確遞增');
    }

    /** @test */
    public function shouldHandleMaxAttemptsReached(): void
    {
        $ip = '192.168.1.7';
        $maxAttempts = 60;

        // 模擬達到最大嘗試次數
        $this->cacheService->shouldReceive('get')
            ->with("rate_limit:{$ip}")
            ->andReturn(['count' => $maxAttempts, 'reset' => time() + 60]);

        $result = $this->rateLimitService->checkLimit($ip);

        $this->assertFalse($result['allowed'], '達到最大嘗試次數時應該被拒絕');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
