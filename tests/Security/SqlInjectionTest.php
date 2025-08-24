<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Domains\Post\Repositories\PostRepository;
use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use TypeError;

class SqlInjectionTest extends TestCase
{
    protected PostRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // 初始化mock對象
        $this->repository = Mockery::mock(PostRepository::class);
        $this->logger = Mockery::mock(LoggingSecurityServiceInterface::class);

        // 設定預設的mock行為
        $this->logger->shouldReceive('logSecurityEvent')->byDefault();
        $this->logger->shouldReceive('enrichSecurityContext')->byDefault()->andReturn([]);
    }

    protected \App\Domains\Security\Contracts\LoggingSecurityServiceInterface|MockInterface $logger;

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
                publish_date DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME DEFAULT NULL
            )
        ');
    }

    /** @test */
    public function shouldPreventSqlInjectionInTitleSearch(): void
    {
        // 準備測試資料
        $maliciousTitle = "' OR '1'='1";

        // 執行測試
        $results = $this->repository->searchByTitle($maliciousTitle);

        // 驗證結果：確保沒有資料被意外洩露
        $this->assertEmpty($results);
    }

    /** @test */
    public function shouldHandleSpecialCharactersInContent(): void
    {
        // 準備含有特殊字元的測試資料
        $content = "Test's content with \"quotes\" and -- comments";
        $data = [
            'uuid' => 'test-uuid',
            'title' => 'Test Post',
            'content' => $content,
            'user_id' => 1,
        ];

        // 執行測試
        $post = $this->repository->create($data);

        // 驗證結果：確保特殊字元被正確處理但不影響原始內容
        $originalContent = html_entity_decode($post->getContent(), ENT_QUOTES, 'UTF-8');
        $this->assertEquals($content, $originalContent);
    }

    /** @test */
    public function shouldPreventSqlInjectionInUserIdFilter(): void
    {
        // 準備測試資料
        $maliciousUserId = '1; DROP TABLE posts;';

        try {
            // 執行測試
            $this->repository->findByUserId($maliciousUserId);
            $this->fail('應該要拋出型別錯誤例外');
        } catch (TypeError $e) {
            // 預期會拋出型別錯誤，因為 user_id 必須是整數
            $this->assertTrue(true);
        }

        // 確認資料表仍然存在
        $tableExists = $this->db->query("
            SELECT name FROM sqlite_master
            WHERE type='table' AND name='posts'
        ")->fetch();

        $this->assertNotEmpty($tableExists);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
