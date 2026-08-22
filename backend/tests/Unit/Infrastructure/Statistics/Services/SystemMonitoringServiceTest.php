<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Services;

use App\Infrastructure\Statistics\Services\SystemMonitoringService;
use Exception;
use Mockery;
use PDO;
use Predis\ClientInterface as RedisClientInterface;
use Tests\Support\UnitTestCase;

/**
 * 系統監控服務測試.
 */
final class SystemMonitoringServiceTest extends UnitTestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec('
            CREATE TABLE users (id INTEGER PRIMARY KEY);
            CREATE TABLE posts (id INTEGER PRIMARY KEY);
            CREATE TABLE user_activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                action_type TEXT NOT NULL,
                status TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE post_views (id INTEGER PRIMARY KEY);
            CREATE TABLE comments (id INTEGER PRIMARY KEY);
            CREATE TABLE tags (id INTEGER PRIMARY KEY);
            CREATE TABLE refresh_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                token TEXT NOT NULL,
                expires_at DATETIME NOT NULL
            );
        ');
    }

    public function testGetSystemHealthStatusWithoutRedis(): void
    {
        // 插入測試資料
        $this->pdo->exec("
            INSERT INTO user_activity_logs (action_type, status, created_at)
            VALUES 
                ('auth.login', 'success', datetime('now')),
                ('auth.login', 'failure', datetime('now')),
                ('auth.login_failed', 'failed', datetime('now'));
            INSERT INTO refresh_tokens (token, expires_at)
            VALUES ('tok1', datetime('now', '+1 hour'));
        ");

        $service = new SystemMonitoringService($this->pdo, null);
        $status = $service->getSystemHealthStatus();

        $this->assertArrayHasKey('cpu', $status);
        $this->assertArrayHasKey('memory', $status);
        $this->assertArrayHasKey('disk', $status);
        $this->assertArrayHasKey('database', $status);
        $this->assertArrayHasKey('cache', $status);
        $this->assertArrayHasKey('php_runtime', $status);
        $this->assertArrayHasKey('system', $status);
        $this->assertArrayHasKey('activity_summary', $status);
        $this->assertArrayHasKey('container', $status);
        $this->assertArrayHasKey('timestamp', $status);

        // 驗證 database 指標
        $this->assertSame('sqlite', $status['database']['driver']);
        $this->assertSame('healthy', $status['database']['status']);

        // 驗證 cache 指標 (無 redis)
        $this->assertFalse($status['cache']['redis_connected']);
        $this->assertSame(1, $status['cache']['active_sessions']);

        // 驗證 activity summary
        $this->assertSame(3, $status['activity_summary']['total_activities_24h']);
        $this->assertSame(1, $status['activity_summary']['login_success_24h']);
        $this->assertSame(2, $status['activity_summary']['login_failure_24h']);
    }

    public function testGetSystemHealthStatusWithRedis(): void
    {
        $redis = Mockery::mock(RedisClientInterface::class);
        $redis->shouldReceive('info')
            ->once()
            ->andReturn([
                'Memory' => ['used_memory' => 10485760],
                'Server' => ['uptime_in_seconds' => 172800],
            ]);

        $service = new SystemMonitoringService($this->pdo, $redis);
        $status = $service->getSystemHealthStatus();

        $this->assertTrue($status['cache']['redis_connected']);
        $this->assertSame(10485760, $status['cache']['redis_used_memory']);
        $this->assertSame(2.0, $status['cache']['redis_uptime_days']);
        $this->assertSame('healthy', $status['cache']['status']);
    }

    public function testGetSystemHealthStatusWithRedisException(): void
    {
        $redis = Mockery::mock(RedisClientInterface::class);
        $redis->shouldReceive('info')
            ->once()
            ->andThrow(new Exception('Redis connection refused'));

        $service = new SystemMonitoringService($this->pdo, $redis);
        $status = $service->getSystemHealthStatus();

        $this->assertFalse($status['cache']['redis_connected']);
        $this->assertSame('warning', $status['cache']['status']);
    }
}
