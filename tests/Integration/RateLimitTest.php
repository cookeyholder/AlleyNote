<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\RateLimitService;
use App\Services\CacheService;
use Tests\TestCase;

/**
 * @requires extension redis
 */
class RateLimitTest extends TestCase
{
    private RateLimitService $rateLimitService;
    private CacheService $cacheService;
    
    /**
     * @var \Redis|\Mockery\MockInterface
     */
    private $redis;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 確保 Redis 擴充模組已安裝
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis 擴充模組未安裝');
        }

        try {
            $this->redis = new \Redis();
            $this->redis->connect(
                getenv('REDIS_HOST') ?: 'redis',
                (int) (getenv('REDIS_PORT') ?: 6379)
            );
        } catch (\RedisException $e) {
            $this->markTestSkipped('無法連線到 Redis 伺服器: ' . $e->getMessage());
        }

        $this->cacheService = new CacheService();
        $this->rateLimitService = new RateLimitService($this->cacheService);
    }

    /** @test */
    public function should_handle_concurrent_requests(): void
    {
        $ip = '192.168.1.1';
        $maxAttempts = 60;
        $results = [];
        $successCount = 0;

        // 模擬多個並發請求
        for ($i = 0; $i < 100; $i++) {
            $pid = pcntl_fork();
            if ($pid == 0) {  // 子程序
                try {
                    $allowed = $this->rateLimitService->isAllowed($ip);
                    exit($allowed ? 0 : 1);
                } catch (\Exception $e) {
                    exit(2);
                }
            } else {  // 父程序
                $results[] = $pid;
            }
        }

        // 等待所有子程序完成並統計結果
        foreach ($results as $pid) {
            $status = 0;
            pcntl_waitpid($pid, $status);
            if (pcntl_wexitstatus($status) === 0) {
                $successCount++;
            }
        }

        // 驗證結果：成功請求數應該等於速率限制
        $this->assertEquals($maxAttempts, $successCount, '並發請求處理錯誤');
    }

    /** @test */
    public function should_reset_limit_after_time_window(): void
    {
        $ip = '192.168.1.2';
        $maxAttempts = 60;
        
        // 先發送最大限制次數的請求
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->assertTrue($this->rateLimitService->isAllowed($ip));
        }
        
        // 確認已達到限制
        $this->assertFalse($this->rateLimitService->isAllowed($ip));
        
        // 等待時間窗口過期
        sleep(61);
        
        // 確認限制已重置
        $this->assertTrue($this->rateLimitService->isAllowed($ip));
    }

    /** @test */
    public function should_handle_multiple_ips_independently(): void
    {
        $ips = ['192.168.1.3', '192.168.1.4', '192.168.1.5'];
        $requests = 30;  // 每個 IP 發送的請求數

        foreach ($ips as $ip) {
            // 對每個 IP 發送請求
            for ($i = 0; $i < $requests; $i++) {
                $this->assertTrue(
                    $this->rateLimitService->isAllowed($ip),
                    "IP {$ip} 的第 {$i} 個請求應該被允許"
                );
            }
        }

        // 驗證每個 IP 都還能發送請求
        foreach ($ips as $ip) {
            $this->assertTrue(
                $this->rateLimitService->isAllowed($ip),
                "IP {$ip} 應該還能發送請求"
            );
        }
    }

    /** @test */
    public function should_handle_redis_connection_failure(): void
    {
        $ip = '192.168.1.6';

        // 關閉 Redis 連線模擬故障
        $this->redis->close();

        // 即使 Redis 連線失敗，系統也應該允許請求
        $this->assertTrue($this->rateLimitService->isAllowed($ip));
    }

    protected function tearDown(): void
    {
        // 清理測試資料
        if (isset($this->redis)) {
            try {
                $pattern = 'rate_limit:*';
                $keys = $this->redis->keys($pattern);
                if (!empty($keys)) {
                    $this->redis->del($keys);
                }
                $this->redis->close();
            } catch (\RedisException $e) {
                // 忽略清理過程中的錯誤
            }
        }
        parent::tearDown();
    }
}
