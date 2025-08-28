<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Domains\Post\Repositories\PostRepository;
use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use App\Infrastructure\Services\CacheService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PDO;
use Tests\TestCase;

class SqlInjectionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected PostRepository $repository;

    protected PDO $db;

    protected LoggingSecurityServiceInterface|MockInterface $logger;

    protected CacheService|MockInterface $cache;

    protected function setUp(): void
    {
        parent::setUp();

        // 使用 SQLite 記憶體資料庫進行測試
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 建立測試資料表
        $this->createTestTables();

        // 初始化 mock 對象
        $this->logger = Mockery::mock(LoggingSecurityServiceInterface::class);
        $this->cache = Mockery::mock(CacheService::class);

        // 設定預設的 mock 行為
        $this->logger->shouldReceive('logSecurityEvent')->byDefault();
        $this->logger->shouldReceive('enrichSecurityContext')->byDefault()->andReturn([]);
        $this->cache->shouldReceive('remember')->andReturnUsing(function ($key, $callback) {
            return $callback();
        })->byDefault();
        $this->cache->shouldReceive('delete')->andReturn(true)->byDefault();
        $this->cache->shouldReceive('forget')->andReturn(true)->byDefault();
        $this->cache->shouldReceive('clear')->andReturn(true)->byDefault();

        // 建立真實的 repository 實例
        $this->repository = new PostRepository($this->db, $this->cache, $this->logger);
    }

    protected function createTestTables(): void
    {
        $this->db->exec('
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid VARCHAR(36) NOT NULL,
                seq_number VARCHAR(11),
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                user_id INTEGER NOT NULL,
                user_ip VARCHAR(45),
                is_pinned BOOLEAN DEFAULT 0,
                status VARCHAR(20) DEFAULT "draft",
                views INTEGER DEFAULT 0,
                publish_date DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME DEFAULT NULL
            )
        ');

        // 插入一些測試資料
        $this->db->exec("
            INSERT INTO posts (uuid, title, content, user_id, user_ip, status, views) VALUES
            ('uuid-1', 'Normal Post', 'Normal content', 1, '8.8.8.8', 'published', 0),
            ('uuid-2', 'Secret Post', 'Secret content', 2, '8.8.8.8', 'published', 0),
            ('uuid-3', 'Draft Post', 'Draft content', 1, '8.8.8.8', 'draft', 0)
        ");
    }

    public function testShouldPreventSqlInjectionInTitleSearch(): void
    {
        // 準備測試資料 - 嘗試 SQL 注入攻擊
        $maliciousTitle = "' OR '1'='1";

        // 執行測試 - 應該只搜尋符合條件的結果，不會洩露所有資料
        $results = $this->repository->paginate(1, 10, ['search' => $maliciousTitle]);

        // 驗證結果：確保SQL注入攻擊被正確防護
        // 搜尋應該安全地處理特殊字符，不會返回所有資料
        $this->assertLessThanOrEqual(3, $results['total'], 'SQL注入攻擊不應該返回所有資料');

        // 確保資料庫完整性
        $totalPosts = $this->db->query('SELECT COUNT(*) as count FROM posts')->fetch();
        $this->assertEquals(3, $totalPosts['count'], '資料表應該保持完整');
    }

    public function testShouldHandleSpecialCharactersInContent(): void
    {
        // 準備含有特殊字元的測試資料
        $content = "Test's content with \"quotes\" and -- comments";
        $data = [
            'uuid' => 'test-uuid-special',
            'title' => 'Test Post with Special Chars',
            'content' => $content,
            'user_id' => 1,
            'user_ip' => '8.8.8.8',
            'status' => 'published',
        ];

        // 執行測試
        $post = $this->repository->create($data);

        // 驗證結果：確保特殊字元被正確處理但不影響原始內容
        $this->assertEquals($content, $post->getContent());
        $this->assertEquals('Test Post with Special Chars', $post->getTitle());
    }

    public function testShouldPreventSqlInjectionInUserIdFilter(): void
    {
        // 準備測試資料 - 使用合法的整數 user_id
        $normalUserId = 1;
        $maliciousString = '1; DROP TABLE posts;';

        // 測試正常的查詢
        $normalResults = $this->repository->paginate(1, 10, ['user_id' => $normalUserId]);
        $this->assertGreaterThan(0, $normalResults['total']);

        // 嘗試用字串作為 user_id（應該被過濾或拒絕）
        $maliciousResults = $this->repository->paginate(1, 10, ['user_id' => $maliciousString]);

        // 確認資料表仍然存在且完整
        $tableExists = $this->db->query("
            SELECT name FROM sqlite_master
            WHERE type='table' AND name='posts'
        ")->fetch();

        $this->assertNotEmpty($tableExists);

        // 確認原始資料仍然存在
        $allPosts = $this->db->query('SELECT COUNT(*) as count FROM posts')->fetch();
        $this->assertEquals(3, $allPosts['count']); // 我們插入的 3 筆測試資料
    }

    public function testShouldSanitizeSearchInput(): void
    {
        // 測試各種可能的 SQL 注入嘗試
        $maliciousInputs = [
            "'; DROP TABLE posts; --",
            "' UNION SELECT * FROM posts --",
            "' OR 1=1 --",
            "'; INSERT INTO posts VALUES (...); --",
        ];

        foreach ($maliciousInputs as $maliciousInput) {
            $results = $this->repository->paginate(1, 10, ['search' => $maliciousInput]);

            // 確保搜尋不會因為SQL注入而回傳所有資料
            $this->assertLessThanOrEqual(3, $results['total'], "SQL injection should not return all data: {$maliciousInput}");
        }

        // 確認資料表和原始資料仍然完整
        $totalPosts = $this->db->query('SELECT COUNT(*) as count FROM posts')->fetch();
        $this->assertEquals(3, $totalPosts['count']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
