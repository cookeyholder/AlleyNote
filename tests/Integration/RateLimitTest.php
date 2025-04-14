<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\RateLimitService;
use App\Services\CacheService;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    private RateLimitService $rateLimitService;
    private CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheService = new CacheService();
        $this->rateLimitService = new RateLimitService($this->cacheService);
    }

    /** @test */
    public function should_limit_rate_successfully(): void
    {
        $ip = '192.168.1.1';
        $maxRequests = 5;
        $timeWindow = 1;

        // 測試允許的請求次數
        for ($i = 0; $i < $maxRequests; $i++) {
            $result = $this->rateLimitService->checkLimit($ip, $maxRequests, $timeWindow);
            $this->assertTrue($result['allowed']);
            $this->assertGreaterThan(0, $result['remaining']);
        }

        // 測試超出限制
        $result = $this->rateLimitService->checkLimit($ip, $maxRequests, $timeWindow);
        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
    }

    /** @test */
    public function should_reset_limit_after_time_window(): void
    {
        $ip = '192.168.1.2';
        $maxRequests = 2;
        $timeWindow = 1;

        // 先用完所有請求次數
        for ($i = 0; $i < $maxRequests; $i++) {
            $this->rateLimitService->checkLimit($ip, $maxRequests, $timeWindow);
        }

        // 等待時間窗口過期
        sleep($timeWindow + 1);

        // 驗證限制已重置
        $result = $this->rateLimitService->checkLimit($ip, $maxRequests, $timeWindow);
        $this->assertTrue($result['allowed']);
        $this->assertEquals($maxRequests - 1, $result['remaining']);
    }

    /** @test */
    public function should_handle_different_ips_independently(): void
    {
        $maxRequests = 2;
        $timeWindow = 1;
        $ip1 = '192.168.1.3';
        $ip2 = '192.168.1.4';

        // 第一個 IP 用完所有請求次數
        for ($i = 0; $i < $maxRequests; $i++) {
            $this->rateLimitService->checkLimit($ip1, $maxRequests, $timeWindow);
        }

        // 驗證第二個 IP 不受影響
        $result = $this->rateLimitService->checkLimit($ip2, $maxRequests, $timeWindow);
        $this->assertTrue($result['allowed']);
        $this->assertEquals($maxRequests - 1, $result['remaining']);
    }

    /** @test */
    public function should_handle_service_unavailability(): void
    {
        $ip = '192.168.1.5';
        $result = $this->rateLimitService->checkLimit($ip, 5, 1);

        // 如果快取服務不可用，應該允許請求
        $this->assertTrue($result['allowed']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cacheService->clear();
    }
}
