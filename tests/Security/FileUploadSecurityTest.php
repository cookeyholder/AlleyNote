<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Domains\Attachment\Models\Attachment;
use App\Shared\Validation\ValidationException;
use App\Domains\Attachment\Repositories\AttachmentRepository;
use App\Domains\Attachment\Services\AttachmentService;
use App\Domains\Post\Models\Post;
use App\Domains\Post\Repositories\PostRepository;

use Mockery;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Tests\TestCase;


class FileUploadSecurityTest extends TestCase
{

    protected AttachmentService $service;
    protected \App\Domains\Security\Services\AuthorizationService|\Mockery\MockInterface $authService;
    protected AttachmentRepository $attachmentRepo;
    protected PostRepository $postRepo;
    protected string $uploadDir;

    protected function setUp(): void
    {
        parent::setUp();

        // 初始化mock對象
        $this->authService = \Mockery::mock(\App\Domains\Security\Services\AuthorizationService::class);
        $this->attachmentRepo = \Mockery::mock(\App\Domains\Attachment\Repositories\AttachmentRepository::class);
        $this->postRepo = \Mockery::mock(\App\Domains\Post\Repositories\PostRepository::class);
        $this->service = \Mockery::mock(AttachmentService::class);

        $this->uploadDir = '/tmp/test-uploads';

        // 設定預設的mock行為
        $this->authService->shouldReceive('canUploadAttachment')->byDefault()->andReturn(true);
        $this->authService->shouldReceive('canDeleteAttachment')->byDefault()->andReturn(true);

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
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
            '<?php echo "malicious"; ?>'
        );

        // 模擬文章存在
        $post = new \App\Domains\Post\Models\Post([
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
        $this->expectException(\App\Shared\Validation\ValidationException::class);
        $this->expectExceptionMessage('不支援的檔案類型');

        // 執行測試
        $this->service->upload($postId, $file);
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
            '<?php echo "malicious"; ?>'
        );

        // 模擬文章存在
        $post = new \App\Domains\Post\Models\Post([
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

        // 設定附件儲存庫行為
        $this->attachmentRepo->shouldReceive('create')
            ->never();

        // 預期會拋出例外
        $this->expectException(\App\Shared\Validation\ValidationException::class);
        $this->expectExceptionMessage('不支援的檔案類型');

        // 執行測試
        $this->service->upload($postId, $file);
    }

    /** @test */
    public function shouldRejectOversizedFiles(): void
    {
        // 準備測試資料
        $postId = 1;
        $file = $this->createUploadedFileMock(
            'large.jpg',
            'image/jpeg',
            21 * 1024 * 1024, // 21MB
            UPLOAD_ERR_OK,
            str_repeat('a', 1024) // 模擬檔案內容
        );

        // 模擬文章存在
        $post = new \App\Domains\Post\Models\Post([
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
        $this->expectException(\App\Shared\Validation\ValidationException::class);
        $this->expectExceptionMessage('檔案大小超過限制（10MB）');

        // 執行測試
        $this->service->upload($postId, $file);
    }

    /** @test */
    public function shouldRejectMaliciousMimeTypes(): void
    {
        // 準備測試資料
        $postId = 1;
        $file = $this->createUploadedFileMock(
            'fake.jpg',
            'application/x-httpd-php',
            1024,
            UPLOAD_ERR_OK,
            '<?php echo "malicious"; ?>'
        );

        // 模擬文章存在
        $post = new \App\Domains\Post\Models\Post([
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
        $this->expectException(\App\Shared\Validation\ValidationException::class);
        $this->expectExceptionMessage('不支援的檔案類型');

        // 執行測試
        $this->service->upload($postId, $file);
    }

    /** @test */
    public function shouldPreventPathTraversal(): void
    {
        // 準備測試資料
        $postId = 1;
        $file = $this->createUploadedFileMock(
            '../../../etc/passwd',
            'text/plain',
            1024,
            UPLOAD_ERR_OK,
            'root:x:0:0:root:/root:/bin/bash'
        );

        // 模擬文章存在
        $post = new \App\Domains\Post\Models\Post([
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

        // 設定附件儲存庫行為
        $this->attachmentRepo->shouldReceive('create')
            ->never();

        // 預期會拋出例外
        $this->expectException(\App\Shared\Validation\ValidationException::class);
        $this->expectExceptionMessage('不支援的檔案類型');

        // 執行測試
        $this->service->upload($postId, $file);
    }

    private function createUploadedFileMock(
        string $filename,
        string $mimeType,
        int $size,
        int $error,
        string $content
    ): UploadedFileInterface {
        $file = Mockery::mock(UploadedFileInterface::class);
        $file->shouldReceive('getClientFilename')->andReturn($filename);
        $file->shouldReceive('getClientMediaType')->andReturn($mimeType);
        $file->shouldReceive('getSize')->andReturn($size);
        $file->shouldReceive('getError')->andReturn($error);
        $file->shouldReceive('moveTo')->andReturnUsing(function ($path) {
            return true;
        });

        // 模擬檔案串流
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn($content);
        $stream->shouldReceive('rewind')->andReturnNull();
        $file->shouldReceive('getStream')->andReturn($stream);

        return $file;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();

        // 清理測試上傳目錄
        if (is_dir($this->uploadDir)) {
            array_map('unlink', glob("$this->uploadDir/*.*"));
            rmdir($this->uploadDir);
        }
    }
}
