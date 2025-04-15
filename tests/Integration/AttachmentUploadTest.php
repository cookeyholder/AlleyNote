<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\AttachmentService;
use App\Repositories\AttachmentRepository;
use App\Repositories\PostRepository;
use App\Models\Attachment;
use App\Exceptions\ValidationException;
use Tests\TestCase;
use Mockery;

class AttachmentUploadTest extends TestCase
{
    protected AttachmentService $attachmentService;
    protected string $uploadDir;
    protected AttachmentRepository $attachmentRepo;
    protected PostRepository $postRepo;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試用目錄
        $this->uploadDir = sys_get_temp_dir() . '/alleynote_test_' . uniqid();
        mkdir($this->uploadDir);

        // 初始化測試依賴
        $this->attachmentRepo = new AttachmentRepository($this->db, $this->cache);
        $this->postRepo = new PostRepository($this->db, $this->cache);

        // 初始化測試對象
        $this->attachmentService = new AttachmentService(
            $this->attachmentRepo,
            $this->postRepo,
            $this->cache,
            $this->uploadDir
        );

        $this->createTestTables();

        // 插入一筆 id=1 的測試文章（補齊所有必要欄位）
        $now = date('Y-m-d H:i:s');
        $uuid = 'test-uuid-1';
        $seq = 1;
        $this->db->exec("INSERT INTO posts (id, uuid, seq_number, title, content, user_id, user_ip, views, is_pinned, status, publish_date, created_at, updated_at) VALUES (
            1,
            '$uuid',
            $seq,
            '測試文章',
            '內容',
            1,
            '127.0.0.1',
            0,
            0,
            'published',
            '$now',
            '$now',
            '$now'
        )");
    }

    protected function createUploadedFileMock(string $filename, string $mimeType, int $size): \Psr\Http\Message\UploadedFileInterface
    {
        $file = Mockery::mock(\Psr\Http\Message\UploadedFileInterface::class);
        $file->shouldReceive('getClientFilename')
            ->andReturn($filename);
        $file->shouldReceive('getClientMediaType')
            ->andReturn($mimeType);
        $file->shouldReceive('getSize')
            ->andReturn($size);
        $stream = Mockery::mock(\Psr\Http\Message\StreamInterface::class);
        $stream->shouldReceive('getContents')
            ->andReturn(str_repeat('x', 1024));
        $stream->shouldReceive('rewind')
            ->andReturn(true);
        
        $file->shouldReceive('getStream')
            ->andReturn($stream);
        $file->shouldReceive('moveTo')
            ->andReturnUsing(function ($path) {
                $directory = dirname($path);
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }
                file_put_contents($path, str_repeat('x', 1024));
                return true;
            });
        return $file;
    }

    /** @test */
    public function should_handle_concurrent_uploads(): void
    {
        $postId = 1;
        $uploadCount = 5;
        $uploads = [];

        // 建立多個測試檔案
        for ($i = 0; $i < $uploadCount; $i++) {
            $uploads[] = $this->createUploadedFileMock(
                "test{$i}.jpg",
                'image/jpeg',
                1024
            );
        }

        // 並發上傳
        $results = [];
        foreach ($uploads as $file) {
            $pid = pcntl_fork();
            if ($pid == 0) {  // 子程序
                try {
                    $attachment = $this->attachmentService->upload($postId, $file);
                    exit(0);
                } catch (\Exception $e) {
                    exit(1);
                }
            } else {  // 父程序
                $results[] = $pid;
            }
        }

        // 等待所有子程序完成
        foreach ($results as $pid) {
            $status = 0;
            pcntl_waitpid($pid, $status);
            $this->assertEquals(0, $status, '並發上傳應該成功完成');
        }
    }

    /** @test */
    public function should_handle_large_file_upload(): void
    {
        $postId = 1;
        $fileSize = 10 * 1024 * 1024; // 10MB

        $file = $this->createUploadedFileMock(
            'large_file.jpg',
            'image/jpeg',
            $fileSize
        );

        $attachment = $this->attachmentService->upload($postId, $file);

        $this->assertInstanceOf(Attachment::class, $attachment);
        $this->assertEquals($fileSize, $attachment->getFileSize());
    }

    /** @test */
    public function should_validate_file_types(): void
    {
        $postId = 1;
        $invalidTypes = [
            'text/html' => 'test.html',
            'application/x-php' => 'test.php',
            'application/x-javascript' => 'test.js',
            'application/x-msdownload' => 'test.exe'
        ];

        foreach ($invalidTypes as $mimeType => $filename) {
            $file = $this->createUploadedFileMock($filename, $mimeType, 1024);

            try {
                $this->attachmentService->upload($postId, $file);
                $this->fail("應該拒絕 {$mimeType} 類型的檔案");
            } catch (ValidationException $e) {
                $this->assertStringContainsString('不支援的檔案類型', $e->getMessage());
            }
        }
    }

    /** @test */
    public function should_handle_disk_full_error(): void
    {
        $postId = 1;
        $file = Mockery::mock(\Psr\Http\Message\UploadedFileInterface::class);
        $file->shouldReceive('getClientFilename')->andReturn('test.jpg');
        $file->shouldReceive('getClientMediaType')->andReturn('image/jpeg');
        $file->shouldReceive('getSize')->andReturn(1024);
        
        $stream = Mockery::mock(\Psr\Http\Message\StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn(str_repeat('x', 1024));
        $stream->shouldReceive('rewind')->andReturn(true);
        $file->shouldReceive('getStream')->andReturn($stream);
        
        $file->shouldReceive('moveTo')
            ->once()
            ->andThrow(new \RuntimeException('No space left on device'));

        $this->expectException(\App\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('檔案上傳失敗');

        $this->attachmentService->upload($postId, $file);
    }

    /** @test */
    public function should_handle_permission_error(): void
    {
        $postId = 1;
        $file = Mockery::mock(\Psr\Http\Message\UploadedFileInterface::class);
        $file->shouldReceive('getClientFilename')->andReturn('test.jpg');
        $file->shouldReceive('getClientMediaType')->andReturn('image/jpeg');
        $file->shouldReceive('getSize')->andReturn(1024);
        
        $stream = Mockery::mock(\Psr\Http\Message\StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn(str_repeat('x', 1024));
        $stream->shouldReceive('rewind')->andReturn(true);
        $file->shouldReceive('getStream')->andReturn($stream);
        
        $file->shouldReceive('moveTo')
            ->once()
            ->andThrow(new \RuntimeException('Permission denied'));

        $this->expectException(\App\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('檔案上傳失敗');

        $this->attachmentService->upload($postId, $file);
    }

    protected function tearDown(): void
    {
        // 清理測試檔案
        if (is_dir($this->uploadDir)) {
            chmod($this->uploadDir, 0755); // 恢復權限以便刪除
            $files = glob($this->uploadDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                } elseif (is_dir($file)) {
                    // 遞迴刪除資料夾
                    $subFiles = glob($file . '/*');
                    foreach ($subFiles as $subFile) {
                        if (is_file($subFile)) {
                            unlink($subFile);
                        }
                    }
                    rmdir($file);
                }
            }
            rmdir($this->uploadDir);
        }
        parent::tearDown();
        Mockery::close();
    }

    protected function createTestTables(): void
    {
        // 先嘗試刪除已存在的資料表
        $this->db->exec('DROP TABLE IF EXISTS attachments');
        $this->db->exec('DROP TABLE IF EXISTS posts');

        // 建立 posts 資料表（schema 與主程式一致）
        $this->db->exec('
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid VARCHAR(36) NOT NULL UNIQUE,
                seq_number INTEGER NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                user_id INTEGER NOT NULL,
                user_ip VARCHAR(45),
                views INTEGER NOT NULL DEFAULT 0,
                is_pinned INTEGER NOT NULL DEFAULT 0,
                status VARCHAR(20) NOT NULL DEFAULT "draft",
                publish_date DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )
        ');

        // 建立 attachments 資料表
        $this->db->exec('
            CREATE TABLE attachments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid VARCHAR(36) NOT NULL,
                post_id INTEGER NOT NULL,
                filename VARCHAR(255) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                mime_type VARCHAR(255) NOT NULL,
                file_size INTEGER NOT NULL,
                storage_path VARCHAR(255) NOT NULL,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');
    }
}
