<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Domains\Attachment\Repositories\AttachmentRepository;
use App\Infrastructure\Services\CacheService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PDO;
use Tests\TestCase;

class AttachmentRepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected AttachmentRepository $repository;

    protected PDO $db;

    protected \App\Infrastructure\Services\CacheService|MockInterface $cache;

    protected function setUp(): void
    {
        parent::setUp();

        // 使用 SQLite 記憶體資料庫進行測試
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 建立測試用資料表
        $this->createTestTables();

        // 模擬快取服務
        $this->cache = Mockery::mock(CacheService::class);
        $this->cache->shouldReceive('remember')
            ->byDefault()
            ->andReturnUsing(function ($key, $callback) {
                return $callback();
            });
        $this->cache->shouldReceive('delete')->byDefault();

        $this->repository = new AttachmentRepository($this->db, $this->cache);
    }

    protected function createTestTables(): void
    {
        // 建立附件資料表
        $this->db->exec('
            CREATE TABLE attachments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid VARCHAR(36) NOT NULL,
                post_id INTEGER NOT NULL,
                filename VARCHAR(255) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                mime_type VARCHAR(127) NOT NULL,
                file_size INTEGER NOT NULL,
                storage_path VARCHAR(512) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME DEFAULT NULL
            )
        ');

        // 建立索引
        $this->db->exec('CREATE INDEX idx_attachments_post_id ON attachments(post_id)');
        $this->db->exec('CREATE INDEX idx_attachments_uuid ON attachments(uuid)');
    }

    public function testShouldCreateAttachmentSuccessfully(): void
    {
        // 準備測試資料
        $data = [
            'post_id' => 1,
            'filename' => 'test.jpg',
            'original_name' => '測試圖片.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'storage_path' => '/storage/attachments/2025/04/test.jpg',
        ];

        // 執行測試
        $attachment = $this->repository->create($data);

        // 驗證結果
        $this->assertNotNull($attachment->getId());
        $this->assertNotNull($attachment->getUuid());
        $this->assertEquals($data['post_id'], $attachment->getPostId());
        $this->assertEquals($data['filename'], $attachment->getFilename());
        $this->assertEquals($data['original_name'], $attachment->getOriginalName());
        $this->assertEquals($data['mime_type'], $attachment->getMimeType());
        $this->assertEquals($data['file_size'], $attachment->getFileSize());
        $this->assertEquals($data['storage_path'], $attachment->getStoragePath());
    }

    public function testShouldFindAttachmentById(): void
    {
        // 建立測試資料
        $data = [
            'post_id' => 1,
            'filename' => 'test.jpg',
            'original_name' => '測試圖片.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'storage_path' => '/storage/attachments/2025/04/test.jpg',
        ];
        $created = $this->repository->create($data);

        // 執行測試
        $found = $this->repository->find($created->getId());

        // 驗證結果
        $this->assertEquals($created->getId(), $found->getId());
        $this->assertEquals($created->getUuid(), $found->getUuid());
    }

    public function testShouldFindAttachmentByUuid(): void
    {
        // 建立測試資料
        $data = [
            'post_id' => 1,
            'filename' => 'test.jpg',
            'original_name' => '測試圖片.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'storage_path' => '/storage/attachments/2025/04/test.jpg',
        ];
        $created = $this->repository->create($data);

        // 執行測試
        $found = $this->repository->findByUuid($created->getUuid());

        // 驗證結果
        $this->assertEquals($created->getId(), $found->getId());
        $this->assertEquals($created->getUuid(), $found->getUuid());
    }

    public function testShouldReturnNullForNonExistentId(): void
    {
        $this->assertNull($this->repository->find(999));
    }

    public function testShouldReturnNullForNonExistentUuid(): void
    {
        $this->assertNull($this->repository->findByUuid('non-existent-uuid'));
    }

    public function testShouldGetAttachmentsByPostId(): void
    {
        // 建立多個附件
        $postId = 1;
        for ($i = 0; $i < 3; $i++) {
            $this->repository->create([
                'post_id' => $postId,
                'filename' => "test{$i}.jpg",
                'original_name' => "測試圖片{$i}.jpg",
                'mime_type' => 'image/jpeg',
                'file_size' => 1024,
                'storage_path' => "/storage/attachments/2025/04/test{$i}.jpg",
            ]);
        }

        // 執行測試
        $attachments = $this->repository->getByPostId($postId);

        // 驗證結果
        $this->assertCount(3, $attachments);
        foreach ($attachments as $attachment) {
            $this->assertEquals($postId, $attachment->getPostId());
        }
    }

    public function testShouldSoftDeleteAttachment(): void
    {
        // 建立測試資料
        $data = [
            'post_id' => 1,
            'filename' => 'test.jpg',
            'original_name' => '測試圖片.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'storage_path' => '/storage/attachments/2025/04/test.jpg',
        ];
        $attachment = $this->repository->create($data);

        // 執行軟刪除
        $result = $this->repository->delete($attachment->getId());

        // 驗證結果
        $this->assertTrue($result);

        // 確認已被軟刪除
        $deleted = $this->repository->find($attachment->getId());
        $this->assertNotNull($deleted->getDeletedAt());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
