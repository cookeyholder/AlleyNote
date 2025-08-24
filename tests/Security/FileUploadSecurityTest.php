<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Domains\Attachment\Repositories\AttachmentRepository;
use App\Domains\Attachment\Services\AttachmentService;
use App\Domains\Auth\Services\AuthorizationService;
use App\Domains\Post\Models\Post;
use App\Domains\Post\Repositories\PostRepository;
use App\Shared\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Tests\TestCase;

class FileUploadSecurityTest extends TestCase
{
    protected AttachmentService $service;

    protected AuthorizationService|MockInterface $authService;

    protected AttachmentRepository|MockInterface $attachmentRepo;

    protected PostRepository|MockInterface $postRepo;

    protected string $uploadDir;

    protected function setUp(): void
    {
        parent::setUp();

        // 初始化mock對象
        $this->authService = Mockery::mock(AuthorizationService::class);
        $this->attachmentRepo = Mockery::mock(AttachmentRepository::class);
        $this->postRepo = Mockery::mock(PostRepository::class);

        // 建立真實的 AttachmentService 實例
        $this->service = new AttachmentService(
            $this->attachmentRepo,
            $this->postRepo,
            $this->authService,
        );

        $this->uploadDir = '/tmp/test-uploads';

        // 設定預設的mock行為
        $this->authService->shouldReceive('canUploadAttachment')->byDefault()->andReturn(true);
        $this->authService->shouldReceive('canDeleteAttachment')->byDefault()->andReturn(true);

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0o777, true);
        }
    }

    /** @test */
    public function shouldRejectExecutableFiles(): void
    {
        // 準備測試資料
        $postId = 1;
        $file = $this->createUploadedFileMock(
            'malicious.exe',
            'application/x-msdownload',
            1024,
            UPLOAD_ERR_OK,
            '<?php echo "malicious"; ?>',
        );

        // 模擬文章存在
        $post = new Post([
            'id' => $postId,
            'uuid' => 'test-uuid',
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->postRepo->shouldReceive('find')
            ->once()
            ->with($postId)
            ->andReturn($post);

        // 預期會拋出例外
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('不支援的檔案類型');

        // 執行測試
        $this->service->upload($postId, $file, 1);
    }

    /** @test */
    public function shouldRejectDoubleExtensionFiles(): void
    {
        // 準備測試資料
        $postId = 1;
        $file = $this->createUploadedFileMock(
            'image.jpg.php',
            'image/jpeg',
            1024,
            UPLOAD_ERR_OK,
            '<?php echo "malicious"; ?>',
        );

        // 模擬文章存在
        $post = new Post([
            'id' => $postId,
            'uuid' => 'test-uuid',
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->postRepo->shouldReceive('find')
            ->once()
            ->with($postId)
            ->andReturn($post);

        // 預期會拋出例外
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('檔案名稱包含危險字元');

        // 執行測試
        $this->service->upload($postId, $file, 1);
    }

    /** @test */
    public function shouldRejectOversizedFiles(): void
    {
        // 準備測試資料 - 檔案大小超過限制
        $postId = 1;
        $file = $this->createUploadedFileMock(
            'large-image.jpg',
            'image/jpeg',
            15728640, // 15MB，超過 10MB 限制
            UPLOAD_ERR_OK,
            str_repeat('x', 15728640),
        );

        // 模擬文章存在
        $post = new Post([
            'id' => $postId,
            'uuid' => 'test-uuid',
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->postRepo->shouldReceive('find')
            ->once()
            ->with($postId)
            ->andReturn($post);

        // 預期會拋出例外
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('檔案大小超過限制');

        // 執行測試
        $this->service->upload($postId, $file, 1);
    }

    /** @test */
    public function shouldRejectMaliciousMimeTypes(): void
    {
        // 準備測試資料
        $postId = 1;
        $file = $this->createUploadedFileMock(
            'script.txt',
            'application/x-php', // 惡意 MIME 類型
            1024,
            UPLOAD_ERR_OK,
            '<?php echo "malicious"; ?>',
        );

        // 模擬文章存在
        $post = new Post([
            'id' => $postId,
            'uuid' => 'test-uuid',
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->postRepo->shouldReceive('find')
            ->once()
            ->with($postId)
            ->andReturn($post);

        // 預期會拋出例外
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('不支援的檔案類型');

        // 執行測試
        $this->service->upload($postId, $file, 1);
    }

    /** @test */
    public function shouldPreventPathTraversal(): void
    {
        // 準備測試資料 - 包含路徑遍歷攻擊的檔案名
        $postId = 1;
        $file = $this->createUploadedFileMock(
            '../../../etc/passwd',
            'text/plain',
            1024,
            UPLOAD_ERR_OK,
            'root:x:0:0:root:/root:/bin/bash',
        );

        // 模擬文章存在
        $post = new Post([
            'id' => $postId,
            'uuid' => 'test-uuid',
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->postRepo->shouldReceive('find')
            ->once()
            ->with($postId)
            ->andReturn($post);

        // 預期會拋出例外
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('檔案名稱包含危險字元');

        // 執行測試
        $this->service->upload($postId, $file, 1);
    }

    /** @test */
    public function shouldAcceptValidFiles(): void
    {
        // 準備測試資料 - 有效的檔案
        $postId = 1;
        $file = $this->createUploadedFileMock(
            'valid-image.jpg',
            'image/jpeg',
            1024,
            UPLOAD_ERR_OK,
            'fake-image-content',
        );

        // 模擬文章存在
        $post = new Post([
            'id' => $postId,
            'uuid' => 'test-uuid',
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->postRepo->shouldReceive('find')
            ->once()
            ->with($postId)
            ->andReturn($post);

        // 模擬成功保存附件
        $this->attachmentRepo->shouldReceive('create')
            ->once()
            ->andReturn([
                'id' => 1,
                'uuid' => 'attachment-uuid',
                'post_id' => $postId,
                'filename' => 'valid-image.jpg',
                'original_filename' => 'valid-image.jpg',
                'mime_type' => 'image/jpeg',
                'size' => 1024,
                'path' => '/uploads/valid-image.jpg',
                'user_id' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        // 執行測試 - 應該成功
        $result = $this->service->upload($postId, $file, 1);

        // 驗證結果
        $this->assertIsArray($result);
        $this->assertEquals('valid-image.jpg', $result['original_filename']);
        $this->assertEquals('image/jpeg', $result['mime_type']);
        $this->assertEquals(1024, $result['size']);
    }

    /**
     * 建立模擬的上傳檔案.
     */
    protected function createUploadedFileMock(
        string $filename,
        string $mimeType,
        int $size,
        int $error,
        string $content,
    ): UploadedFileInterface {
        $file = Mockery::mock(UploadedFileInterface::class);
        $stream = Mockery::mock(StreamInterface::class);

        $file->shouldReceive('getClientFilename')->andReturn($filename);
        $file->shouldReceive('getClientMediaType')->andReturn($mimeType);
        $file->shouldReceive('getSize')->andReturn($size);
        $file->shouldReceive('getError')->andReturn($error);
        $file->shouldReceive('getStream')->andReturn($stream);

        $stream->shouldReceive('getContents')->andReturn($content);
        $stream->shouldReceive('getSize')->andReturn($size);

        // 為有效檔案設定 moveTo 方法
        if ($error === UPLOAD_ERR_OK && $size <= 10485760
            && !preg_match('/\.(php|exe|bat|cmd|sh)$/i', $filename)
            && !str_contains($filename, '..')
            && in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'])) {
            $file->shouldReceive('moveTo')->andReturnNull();
        }

        return $file;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // 清理測試檔案
        if (is_dir($this->uploadDir)) {
            $this->removeDirectory($this->uploadDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
