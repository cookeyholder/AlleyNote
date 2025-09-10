<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domains\Attachment\Models\Attachment;
use App\Domains\Attachment\Repositories\AttachmentRepository;
use App\Domains\Attachment\Services\AttachmentService;
use App\Domains\Auth\Services\AuthorizationService;
use App\Domains\Post\Repositories\PostRepository;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use App\Shared\Exceptions\ValidationException;
use Exception;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Tests\TestCase;

#[Group('failing')]
class AttachmentUploadTest extends TestCase



{
    protected AttachmentService $attachmentService;

    protected \App\Domains\Auth\Services\AuthorizationService|MockInterface $authService;

    protected \App\Domains\Security\Contracts\ActivityLoggingServiceInterface|MockInterface $activityLogger;

    protected \App\Domains\Security\Contracts\LoggingSecurityServiceInterface|MockInterface $logger;

    protected string $uploadDir;

    protected AttachmentRepository $attachmentRepo;

    protected PostRepository $postRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupMockServices();
        $this->setupUploadDirectory();
        $this->setupRepositories();
        $this->setupAttachmentService();
        $this->createTestTables();
        $this->insertTestPostData();
    }

    private function setupMockServices(): void
    {
        $this->authService = Mockery::mock(AuthorizationService::class);
        $this->activityLogger = Mockery::mock(ActivityLoggingServiceInterface::class);
        $this->logger = Mockery::mock(LoggingSecurityServiceInterface::class);

        $this->configureMockDefaults();
    }

    private function configureMockDefaults(): void
    {
        // 配置 AuthorizationService Mock 期望
        $this->authService->shouldReceive('canUploadAttachment')->byDefault()->andReturn(true);
        $this->authService->shouldReceive('canDeleteAttachment')->byDefault()->andReturn(true);
        $this->authService->shouldReceive('isSuperAdmin')->byDefault()->andReturn(true);

        // 配置 ActivityLoggingService Mock 期望
        $this->activityLogger->shouldReceive('logSuccess')->byDefault()->andReturn(true);
        $this->activityLogger->shouldReceive('logFailure')->byDefault()->andReturn(true);
        $this->activityLogger->shouldReceive('log')->byDefault()->andReturn(true);
        $this->activityLogger->shouldReceive('logActivity')->byDefault()->andReturn(true);

        // 配置 LoggingSecurityService Mock 期望
        $this->logger->shouldReceive('logSecurityEvent')->byDefault();
        $this->logger->shouldReceive('enrichSecurityContext')->byDefault()->andReturn([]);
    }

    private function setupUploadDirectory(): void
    {
        $this->uploadDir = sys_get_temp_dir() . '/alleynote_test_' . uniqid();
        mkdir($this->uploadDir);
    }

    private function setupRepositories(): void
    {
        $this->attachmentRepo = new AttachmentRepository($this->db, $this->cache);
        $logger = Mockery::mock(LoggingSecurityServiceInterface::class);
        $this->postRepo = new PostRepository($this->db, $this->cache, $logger);
    }

    private function setupAttachmentService(): void
    {
        /** @var AuthorizationService $authService */
        $authService = $this->authService;
        /** @var ActivityLoggingServiceInterface $activityLogger */
        $activityLogger = $this->activityLogger;

        $this->attachmentService = new AttachmentService(
            $this->attachmentRepo,
            $this->postRepo,
            $authService,
            $activityLogger,
            $this->uploadDir,
        );
    }

    private function insertTestPostData(): void
    {
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
            '%s'
        )");
    }

    protected function createUploadedFileMock(string $filename, string $mimeType, int $size): UploadedFileInterface
    {
        $file = Mockery::mock(UploadedFileInterface::class);
        $this->configureFileMockBasicProperties($file, $filename, $mimeType, $size);
        $this->configureFileMockStream($file, $size);
        $this->configureFileMockMoveTo($file, $size);

        return $file;
    }

    private function configureFileMockBasicProperties(MockInterface $file, string $filename, string $mimeType, int $size): void
    {
        $file->shouldReceive('getClientFilename')->andReturn($filename);
        $file->shouldReceive('getClientMediaType')->andReturn($mimeType);
        $file->shouldReceive('getSize')->andReturn($size);
    }

    private function configureFileMockStream(MockInterface $file, int $size): void
    {
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn(str_repeat('x', $size));
        $stream->shouldReceive('rewind')->andReturn(true);
        $file->shouldReceive('getStream')->andReturn($stream);
    }

    private function configureFileMockMoveTo(MockInterface $file, int $size): void
    {
        $file->shouldReceive('moveTo')
            ->andReturnUsing(function ($path) use ($size) {
                $this->ensureDirectoryExists(dirname($path));
                file_put_contents($path, str_repeat('x', $size));

                return true;
            });
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0o755, true);
        }
    }

    #[Test]
    public function should_handle_concurrent_uploads(): void
    {
        $postId = 1;
        $fileCount = 3;

        $successfulUploads = $this->performMultipleUploads($postId, $fileCount);

        $this->assertEquals($fileCount, $successfulUploads, '所有上傳應該成功完成');
    }

    private function performMultipleUploads(int $postId, int $fileCount): int
    {
        $successfulUploads = 0;

        for ($i = 1; $i <= $fileCount; $i++) {
            if ($this->attemptSingleUpload($postId, $i)) {
                $successfulUploads++;
            }
        }

        return $successfulUploads;
    }

    private function attemptSingleUpload(int $postId, int $fileIndex): bool
    {
        $file = $this->createUploadedFileMock(
            sprintf("test%s.jpg", $fileIndex),
            'image/jpeg',
            1024
        );

        try {
            $attachment = $this->attachmentService->upload($postId, $file, 1);

            $this->assertInstanceOf(Attachment::class, $attachment);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    #[Test]
    public function should_handle_large_file_upload(): void
    {
        $postId = 1;
        $fileSize = 10 * 1024 * 1024; // 10MB

        $attachment = $this->uploadLargeFile($postId, $fileSize);

        $this->assertInstanceOf(Attachment::class, $attachment);
        $this->assertEquals($fileSize, $attachment->getFileSize());
    }

    private function uploadLargeFile(int $postId, int $fileSize): Attachment
    {
        $file = $this->createUploadedFileMock(
            'large_file.jpg',
            'image/jpeg',
            $fileSize,
        );

        return $this->attachmentService->upload($postId, $file, 1);
    }

    #[Test]
    public function should_validate_file_types(): void
    {
        $postId = 1;
        $this->testAllInvalidFileTypes($postId);
    }

    private function testAllInvalidFileTypes(int $postId): void
    {
        $invalidTypes = [
            'text/html' => 'test.html',
            'application/x-php' => 'test.php',
            'application/x-javascript' => 'test.js',
            'application/x-msdownload' => 'test.exe',
        ];

        foreach ($invalidTypes as $mimeType => $filename) {
            $this->testSingleInvalidFileType($postId, $filename, $mimeType);
        }

    }
    private function testSingleInvalidFileType(int $postId, string $filename, string $mimeType): void
    {
        $file = $this->createUploadedFileMock($filename, $mimeType, 1024);

        try {
            $this->attachmentService->upload($postId, $file, 1);
            $this->fail(sprintf('應該拒絕 %s 類型的檔案', $mimeType));
        } catch (ValidationException $e) {
            $this->assertStringContainsString('不支援的檔案類型', $e->getMessage());
        }
    }

    #[Test]
    public function should_handle_disk_full_error(): void
    {
        $postId = 1;
        $file = $this->createUploadFileWithError('No space left on device');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('檔案上傳失敗');

        $this->attachmentService->upload($postId, $file, 1);
    }

    #[Test]
    public function should_handle_permission_error(): void
    {
        $postId = 1;
        $file = $this->createUploadFileWithError('Permission denied');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('檔案上傳失敗');

        $this->attachmentService->upload($postId, $file, 1);
    }

    private function createUploadFileWithError(string $errorMessage): UploadedFileInterface
    {
        /** @var UploadedFileInterface&MockInterface $file */
        $file = Mockery::mock(UploadedFileInterface::class);
        $this->configureBasicFileMockForError($file);
        $this->configureFileMockToThrowError($file, $errorMessage);

        return $file;
    }

    private function configureBasicFileMockForError(UploadedFileInterface $file): void
    {
        /** @var UploadedFileInterface&MockInterface $mockFile */
        $mockFile = $file;
        $mockFile->shouldReceive('getClientFilename')->andReturn('test.jpg');
        $mockFile->shouldReceive('getClientMediaType')->andReturn('image/jpeg');
        $mockFile->shouldReceive('getSize')->andReturn(1024);

        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn(str_repeat('x', 1024));
        $stream->shouldReceive('rewind')->andReturn(true);
        $mockFile->shouldReceive('getStream')->andReturn($stream);
    }

    private function configureFileMockToThrowError(UploadedFileInterface $file, string $errorMessage): void
    {
        /** @var UploadedFileInterface&MockInterface $mockFile */
        $mockFile = $file;
        $mockFile->shouldReceive('moveTo')
            ->once()
            ->andThrow(new RuntimeException($errorMessage));
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
        Mockery::close();
    }

    private function cleanupTestFiles(): void
    {
        if (!is_dir($this->uploadDir)) {
            return;
        }

        $this->restoreDirectoryPermissions();
        $this->removeAllFilesInDirectory();
        rmdir($this->uploadDir);
    }

    private function restoreDirectoryPermissions(): void
    {
        chmod($this->uploadDir, 0o755); // 恢復權限以便刪除
    }

    private function removeAllFilesInDirectory(): void
    {
        $files = glob($this->uploadDir . '/*');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            } elseif (is_dir($file)) {
                $this->removeSubdirectory($file);
            }

    }
    }
    private function removeSubdirectory(string $directory): void
    {
        $subFiles = glob($directory . '/*');

        foreach ($subFiles as $subFile) {
            if (is_file($subFile)) {
                unlink($subFile);
            }
        }

        rmdir($directory);
    }

    protected function createTestTables(): void
    {
        $this->dropExistingTables();
        $this->createPostsTable();
        $this->createAttachmentsTable();
    }

    private function dropExistingTables(): void
    {
        $this->db->exec('DROP TABLE IF EXISTS attachments');
        $this->db->exec('DROP TABLE IF EXISTS posts');
    }
}
