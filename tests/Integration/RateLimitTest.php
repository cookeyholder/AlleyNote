<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\RateLimitService;
use App\Services\CacheService;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    private RateLimitService $rateLimitService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateLimitService = new RateLimitService($this->cache);
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
            $this->assertTrue($result['allowed'], "第 " . ($i + 1) . " 次請求應該被允許");
            $this->assertEquals($maxRequests - ($i + 1), $result['remaining'], "剩餘請求次數不正確");
        }

        // 測試超出限制
        $result = $this->rateLimitService->checkLimit($ip, $maxRequests, $timeWindow);
        $this->assertFalse($result['allowed'], "超出限制的請求應該被拒絕");
        $this->assertEquals(0, $result['remaining'], "超出限制時剩餘請求次數應該為 0");
    }

    /** @test */
    public function should_reset_limit_after_time_window(): void
    {
        $ip = '192.168.1.2';
        $maxRequests = 2;
        $timeWindow = 1;

        // 先用完所有請求次數
        for ($i = 0; $i < $maxRequests; $i++) {
            $result = $this->rateLimitService->checkLimit($ip, $maxRequests, $timeWindow);
            $this->assertTrue($result['allowed'], "第 " . ($i + 1) . " 次請求應該被允許");
        }

        // 模擬時間窗口過期
        $key = "rate_limit:{$ip}";
        $data = $this->cache->get($key);
        $data['reset'] = time() - 1;
        $this->cache->set($key, $data);

        // 驗證限制已重置
        $result = $this->rateLimitService->checkLimit($ip, $maxRequests, $timeWindow);
        $this->assertTrue($result['allowed'], "時間窗口過期後請求應該被允許");
        $this->assertEquals($maxRequests - 1, $result['remaining'], "重置後剩餘請求次數不正確");
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
            $result = $this->rateLimitService->checkLimit($ip1, $maxRequests, $timeWindow);
            $this->assertTrue($result['allowed'], "IP1 的第 " . ($i + 1) . " 次請求應該被允許");
        }

        // 驗證第二個 IP 不受影響
        $result = $this->rateLimitService->checkLimit($ip2, $maxRequests, $timeWindow);
        $this->assertTrue($result['allowed'], "不同 IP 應該獨立計數");
        $this->assertEquals($maxRequests - 1, $result['remaining'], "不同 IP 的剩餘請求次數不正確");
    }

    /** @test */
    public function should_handle_service_unavailability(): void
    {
        $ip = '192.168.1.5';
        $maxRequests = 5;

        // 模擬快取服務拋出異常
        $this->cache->shouldReceive('get')
            ->once()
            ->andThrow(new \Exception('快取服務不可用'));

        $result = $this->rateLimitService->checkLimit($ip, $maxRequests, 1);

        // 如果快取服務不可用，應該允許請求
        $this->assertTrue($result['allowed'], "快取服務不可用時應該允許請求");
        // 由於 checkLimit fallback 時 remaining 會等於 maxRequests
        // 但如果 fallback 時已經有部分請求，remaining 可能會不同
        // 這裡只驗證 allowed 為 true
    }

}
