<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\AttachmentService;
use App\Services\CacheService;
use App\Repositories\AttachmentRepository;
use App\Repositories\PostRepository;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Models\Post;
use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\UploadedFileInterface;

class AttachmentServiceTest extends TestCase
{
    protected AttachmentService $service;
    protected string $uploadDir;
    protected AttachmentRepository|MockInterface $attachmentRepo;
    protected PostRepository|MockInterface $postRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploadDir = sys_get_temp_dir() . '/alleynote_test_' . uniqid();
        mkdir($this->uploadDir);

        $this->attachmentRepo = Mockery::mock(AttachmentRepository::class);
        $this->postRepo = Mockery::mock(PostRepository::class);

        $this->service = new AttachmentService(
            $this->attachmentRepo,
            $this->postRepo,
            $this->cache,
            $this->uploadDir
        );
    }

    /** @test */
    public function shouldUploadFileSuccessfully(): void
    {
        // 準備測試資料
        $postId = 1;
        $file = $this->createUploadedFileMock(
            'test.jpg',
            'image/jpeg',
            1024,
            UPLOAD_ERR_OK
        );

        // 模擬文章存在
        $post = Mockery::mock(Post::class);
        $post->shouldReceive('getId')->andReturn($postId);

        $this->postRepo->shouldReceive('find')
            ->once()
            ->with($postId)
            ->andReturn($post);

        // 模擬附件建立
        $this->attachmentRepo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) use ($postId) {
                return $data['post_id'] === $postId
                    && $data['original_name'] === 'test.jpg'
                    && $data['mime_type'] === 'image/jpeg'
                    && $data['file_size'] === 1024
                    && str_starts_with($data['filename'], date('Y/m/'))
                    && str_ends_with($data['storage_path'], '.jpg');
            }))
            ->andReturn(Mockery::mock('App\Models\Attachment'));

        // 執行測試
        $result = $this->service->upload($postId, $file);

        // 驗證結果
        $this->assertNotNull($result);
    }

    /** @test */
    public function shouldRejectInvalidFileType(): void
    {
        // 準備測試資料
        $postId = 1;
        $file = $this->createUploadedFileMock(
            'test.exe',
            'application/x-msdownload',
            1024,
            UPLOAD_ERR_OK
        );

        // 模擬文章存在
        $post = Mockery::mock(Post::class);
        $post->shouldReceive('getId')->andReturn($postId);

        $this->postRepo->shouldReceive('find')
            ->once()
            ->with($postId)
            ->andReturn($post);

        // 預期會拋出例外
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('不支援的檔案類型');

        // 執行測試
        $this->service->upload($postId, $file);
    }

    /** @test */
    public function shouldRejectOversizedFile(): void
    {
        // 準備測試資料
        $postId = 1;
        $file = $this->createUploadedFileMock(
            'test.jpg',
            'image/jpeg',
            21 * 1024 * 1024, // 21MB
            UPLOAD_ERR_OK
        );

        // 模擬文章存在
        $post = Mockery::mock(Post::class);
        $post->shouldReceive('getId')->andReturn($postId);

        $this->postRepo->shouldReceive('find')
            ->once()
            ->with($postId)
            ->andReturn($post);

        // 預期會拋出例外
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('檔案大小不可超過 20MB');

        // 執行測試
        $this->service->upload($postId, $file);
    }

    /** @test */
    public function shouldRejectUploadToNonExistentPost(): void
    {
        // 準備測試資料
        $postId = 999;
        $file = $this->createUploadedFileMock(
            'test.jpg',
            'image/jpeg',
            1024,
            UPLOAD_ERR_OK
        );

        // 模擬文章不存在
        $this->postRepo->shouldReceive('find')
            ->once()
            ->with($postId)
            ->andReturn(null);

        // 預期會拋出例外
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('找不到指定的文章');

        // 執行測試
        $this->service->upload($postId, $file);
    }

    private function createUploadedFileMock(
        string $filename,
        string $mimeType,
        int $size,
        int $error
    ): UploadedFileInterface {
        $file = Mockery::mock(UploadedFileInterface::class);
        $file->shouldReceive('getClientFilename')->andReturn($filename);
        $file->shouldReceive('getClientMediaType')->andReturn($mimeType);
        $file->shouldReceive('getSize')->andReturn($size);
        $file->shouldReceive('getError')->andReturn($error);
        $file->shouldReceive('moveTo')->andReturnNull();
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
