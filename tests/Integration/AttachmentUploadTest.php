<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\AttachmentService;
use App\Repositories\AttachmentRepository;
use App\Repositories\PostRepository;
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
    }

    /** @test */
    public function should_handle_concurrent_uploads(): void
    {
        $postId = 1;
        $uploadCount = 5;
        $uploads = [];

        // 建立多個測試檔案
        for ($i = 0; $i < $uploadCount; $i++) {
            $filePath = $this->uploadDir . "/test{$i}.jpg";
            file_put_contents($filePath, str_repeat('x', 1024)); // 1KB 檔案

            $file = Mockery::mock(UploadedFileInterface::class);
            $file->shouldReceive('getClientFilename')
                ->andReturn("test{$i}.jpg");
            $file->shouldReceive('getClientMediaType')
                ->andReturn('image/jpeg');
            $file->shouldReceive('getSize')
                ->andReturn(1024);
            $file->shouldReceive('moveTo')
                ->andReturnUsing(function ($path) use ($filePath) {
                    copy($filePath, $path);
                    return true;
                });

            $uploads[] = $file;
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
        $filePath = $this->uploadDir . '/large_file.bin';
        $fileSize = 10 * 1024 * 1024; // 10MB

        // 建立大檔案
        $handle = fopen($filePath, 'w');
        fseek($handle, $fileSize - 1);
        fwrite($handle, 'x');
        fclose($handle);

        $file = Mockery::mock(UploadedFileInterface::class);
        $file->shouldReceive('getClientFilename')
            ->andReturn('large_file.bin');
        $file->shouldReceive('getClientMediaType')
            ->andReturn('application/octet-stream');
        $file->shouldReceive('getSize')
            ->andReturn($fileSize);
        $file->shouldReceive('moveTo')
            ->andReturnUsing(function ($path) use ($filePath) {
                copy($filePath, $path);
                return true;
            });

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
            $filePath = $this->uploadDir . '/' . $filename;
            file_put_contents($filePath, 'test content');

            $file = Mockery::mock(UploadedFileInterface::class);
            $file->shouldReceive('getClientFilename')
                ->andReturn($filename);
            $file->shouldReceive('getClientMediaType')
                ->andReturn($mimeType);
            $file->shouldReceive('getSize')
                ->andReturn(12);

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
        $filePath = $this->uploadDir . '/test.jpg';
        file_put_contents($filePath, str_repeat('x', 1024));

        $file = Mockery::mock(UploadedFileInterface::class);
        $file->shouldReceive('getClientFilename')
            ->andReturn('test.jpg');
        $file->shouldReceive('getClientMediaType')
            ->andReturn('image/jpeg');
        $file->shouldReceive('getSize')
            ->andReturn(1024);
        $file->shouldReceive('moveTo')
            ->andThrow(new \RuntimeException('No space left on device'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('檔案儲存失敗');

        $this->attachmentService->upload($postId, $file);
    }

    /** @test */
    public function should_handle_permission_error(): void
    {
        $postId = 1;
        $filePath = $this->uploadDir . '/test.jpg';
        file_put_contents($filePath, str_repeat('x', 1024));
        chmod($this->uploadDir, 0444); // 設定為唯讀

        $file = Mockery::mock(UploadedFileInterface::class);
        $file->shouldReceive('getClientFilename')
            ->andReturn('test.jpg');
        $file->shouldReceive('getClientMediaType')
            ->andReturn('image/jpeg');
        $file->shouldReceive('getSize')
            ->andReturn(1024);
        $file->shouldReceive('moveTo')
            ->andThrow(new \RuntimeException('Permission denied'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('檔案儲存失敗');

        $this->attachmentService->upload($postId, $file);
    }

    protected function tearDown(): void
    {
        // 清理測試檔案
        if (is_dir($this->uploadDir)) {
            chmod($this->uploadDir, 0755); // 恢復權限以便刪除
            $files = glob($this->uploadDir . '/*');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->uploadDir);
        }
        parent::tearDown();
        Mockery::close();
    }

    private function createTestTables(): void
    {
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

        $this->db->exec('
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        $this->db->exec('
            INSERT INTO posts (id, title, content, created_at, updated_at)
            VALUES (1, "測試文章", "測試內容", datetime("now"), datetime("now"))
        ');
    }
}
