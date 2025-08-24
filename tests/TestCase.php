<?php

namespace Tests;

use App\Infrastructure\Database\DatabaseConnection;
use App\Infrastructure\Services\CacheService;
use Mockery;
use Mockery\MockInterface;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected PDO $db;

    protected \App\Infrastructure\Services\CacheService|MockInterface $cache;

    /**
     * 初始化測試環境.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 設定測試環境變數
        putenv('APP_ENV=testing');
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');

        // 建立記憶體資料庫連線
        try {
            $this->db = new PDO('sqlite::memory:', null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // 啟用外鍵約束
            $this->db->exec('PRAGMA foreign_keys = ON');

            // 建立測試用資料表
            $this->createTestTables();

            // 設定全域資料庫連線實例
            DatabaseConnection::setInstance($this->db);
        } catch (PDOException $e) {
            throw new RuntimeException('無法建立測試資料庫連線：' . $e->getMessage());
        }

        // 用於儲存快取資料的靜態變數
        static $storage = [];

        // 清除舊的快取資料
        $storage = [];

        // 模擬快取服務
        $this->cache = Mockery::mock(CacheService::class);
        $this->cache->shouldReceive('get')
            ->andReturnUsing(function ($key) use (&$storage) {
                return $storage[$key] ?? null;
            });
        $this->cache->shouldReceive('set')
            ->andReturnUsing(function ($key, $value, $ttl = null) use (&$storage) {
                $storage[$key] = $value;

                return true;
            });
        $this->cache->shouldReceive('put')
            ->andReturnUsing(function ($key, $value, $ttl = null) use (&$storage) {
                $storage[$key] = $value;

                return true;
            });
        $this->cache->shouldReceive('has')
            ->andReturnUsing(function ($key) use (&$storage) {
                return isset($storage[$key]);
            });
        $this->cache->shouldReceive('forget')
            ->andReturnUsing(function ($key) use (&$storage) {
                unset($storage[$key]);

                return true;
            });
        $this->cache->shouldReceive('clear')
            ->andReturnUsing(function () use (&$storage) {
                $storage = [];

                return true;
            });
        $this->cache->shouldReceive('delete')
            ->andReturnUsing(function ($key) use (&$storage) {
                unset($storage[$key]);

                return true;
            });
        $this->cache->shouldReceive('tags')
            ->andReturn($this->cache);
        $this->cache->shouldReceive('remember')
            ->andReturnUsing(function ($key, $callback) use (&$storage) {
                if (!isset($storage[$key])) {
                    $storage[$key] = $callback();
                }

                return $storage[$key];
            });
    }

    /**
     * 建立測試用資料表.
     */
    protected function createTestTables(): void
    {
        // 建立基本資料表
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                seq_number INTEGER NOT NULL UNIQUE,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                user_id INTEGER NOT NULL,
                user_ip TEXT,
                views INTEGER NOT NULL DEFAULT 0,
                is_pinned INTEGER NOT NULL DEFAULT 0,
                status INTEGER NOT NULL DEFAULT 1,
                publish_date TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )
        ');

        // 建立 IP 黑白名單資料表
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS ip_lists (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                ip_address TEXT NOT NULL,
                type INTEGER NOT NULL DEFAULT 0,
                unit_id INTEGER,
                description TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )
        ');

        // 建立附件資料表
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS attachments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                post_id INTEGER NOT NULL,
                filename TEXT NOT NULL,
                original_name TEXT NOT NULL,
                mime_type TEXT NOT NULL,
                file_size INTEGER NOT NULL,
                storage_path TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                deleted_at TEXT,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
            )
        ');

        // 建立所需的索引
        $this->createIndices();
    }

    /**
     * 建立資料表索引.
     */
    private function createIndices(): void
    {
        // Posts 索引
        $this->db->exec('
            CREATE INDEX IF NOT EXISTS idx_posts_uuid ON posts(uuid);
            CREATE INDEX IF NOT EXISTS idx_posts_title ON posts(title);
            CREATE INDEX IF NOT EXISTS idx_posts_publish_date ON posts(publish_date);
            CREATE INDEX IF NOT EXISTS idx_posts_user_id ON posts(user_id);
            CREATE INDEX IF NOT EXISTS idx_posts_views ON posts(views)
        ');

        // IP Lists 索引
        $this->db->exec('
            CREATE INDEX IF NOT EXISTS idx_ip_lists_uuid ON ip_lists(uuid);
            CREATE INDEX IF NOT EXISTS idx_ip_lists_ip_address ON ip_lists(ip_address);
            CREATE INDEX IF NOT EXISTS idx_ip_lists_type ON ip_lists(type)
        ');

        // Attachments 索引
        $this->db->exec('
            CREATE INDEX IF NOT EXISTS idx_attachments_uuid ON attachments(uuid);
            CREATE INDEX IF NOT EXISTS idx_attachments_post_id ON attachments(post_id);
            CREATE INDEX IF NOT EXISTS idx_attachments_created_at ON attachments(created_at)
        ');
    }

    /**
     * 建立 HTTP 回應的模擬物件.
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
     * 清理測試環境.
     */
    protected function tearDown(): void
    {
        // 清理資料庫連線
        if ($this->db instanceof PDO) {
            DatabaseConnection::reset();
            $this->db = new PDO('sqlite::memory:');
        }

        if ($container = Mockery::getContainer()) {
            $container->mockery_close();
        }

        parent::tearDown();
    }
}
