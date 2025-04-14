<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use App\Database\DatabaseConnection;
use App\Services\CacheService;
use Mockery;
use PDO;
use Psr\Http\Message\ResponseInterface;

abstract class TestCase extends BaseTestCase
{
    protected PDO $db;
    protected CacheService $cache;

    /**
     * 初始化測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 設定測試環境變數
        putenv('APP_ENV=testing');
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');

        // 建立記憶體資料庫連線
        $this->db = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // 啟用外鍵約束
        $this->db->exec('PRAGMA foreign_keys = ON');

        // 設定全域資料庫連線實例
        DatabaseConnection::setInstance($this->db);

        // 模擬快取服務
        $this->cache = Mockery::mock(CacheService::class);
        $this->cache->shouldReceive('get')->andReturn(null)->byDefault();
        $this->cache->shouldReceive('put')->andReturn(true)->byDefault();
        $this->cache->shouldReceive('has')->andReturn(false)->byDefault();
        $this->cache->shouldReceive('forget')->andReturn(true)->byDefault();
        $this->cache->shouldReceive('remember')
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            })
            ->byDefault();
        $this->cache->shouldReceive('tags')->andReturn($this->cache)->byDefault();
    }

    /**
     * 建立 HTTP 回應的模擬物件
     */
    protected function createResponseMock(): ResponseInterface
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('withJson')
            ->andReturnUsing(function ($data) use ($response) {
                return $response;
            });
        $response->shouldReceive('withStatus')
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->andReturnSelf();
        return $response;
    }

    /**
     * 清理測試環境
     */
    protected function tearDown(): void
    {
        // 清理資料庫連線
        DatabaseConnection::reset();
        $this->db = null;

        parent::tearDown();
        if ($container = Mockery::getContainer()) {
            $container->mockery_close();
        }
    }
}
