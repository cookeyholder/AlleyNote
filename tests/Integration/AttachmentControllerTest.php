<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Application\Controllers\Api\V1\AttachmentController;
use App\Domains\Attachment\Models\Attachment;
use App\Domains\Attachment\Services\AttachmentService;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Tests\TestCase;

class AttachmentControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private AttachmentService&MockInterface $attachmentService;

    private ServerRequestInterface&MockInterface $request;

    private ResponseInterface&MockInterface $response;

    private StreamInterface&MockInterface $stream;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attachmentService = Mockery::mock(AttachmentService::class);
        $this->stream = Mockery::mock(StreamInterface::class);
        $this->request = Mockery::mock(ServerRequestInterface::class);
        $this->response = Mockery::mock(ResponseInterface::class);

        // 設定預設回應行為
        $this->response->shouldReceive('withHeader')->andReturnSelf();
        $this->response->shouldReceive('withStatus')->andReturnSelf();
        $this->response->shouldReceive('getBody')->andReturn($this->stream);
        $this->stream->shouldReceive('write')
            ->andReturnUsing(function ($content) {
                return strlen($content);
            });

        // 設定 AttachmentService mock 期望
        $this->attachmentService->shouldReceive('upload')
            ->andReturn(new Attachment([
                'id' => 1,
                'uuid' => 'test-uuid',
                'post_id' => 1,
                'filename' => 'test.jpg',
                'original_name' => 'test.jpg',
                'file_size' => 1024,
                'mime_type' => 'image/jpeg',
                'storage_path' => '/uploads/test.jpg',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]))
            ->byDefault();

        $this->attachmentService->shouldReceive('delete')
            ->andReturn(true)
            ->byDefault();

        // 設定預設的 user_id 屬性
        $this->request->shouldReceive('getAttribute')
            ->with('user_id')
            ->andReturn(1)
            ->byDefault();
    }

    #[Test]
    public function uploadShouldStoreFileSuccessfully(): void
    {
        // Mock user_id attribute

        $this->request->shouldReceive('getAttribute')

            ->with('user_id')

            ->andReturn(1);

        // 準備測試資料
        $postId = 1;
        $file = Mockery::mock(UploadedFileInterface::class);
        $attachment = new Attachment([
            'id' => 1,
            'post_id' => $postId,
            'filename' => '2025/04/test.jpg',
            'original_name' => '測試圖片.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'storage_path' => '2025/04/test.jpg',
        ]);

        // 設定請求
        $this->request->shouldReceive('getAttribute')
            ->with('post_id')
            ->andReturn($postId);
        $this->request->shouldReceive('getUploadedFiles')
            ->once()
            ->andReturn(['file' => $file]);

        // 設定服務層期望行為
        $this->attachmentService->shouldReceive('upload')
            ->once()
            ->with($postId, $file, 1)
            ->andReturn($attachment);

        // 設定回應期望
        $this->response->shouldReceive('getStatusCode')
            ->andReturn(201);

        // 執行測試
        $controller = new AttachmentController($this->attachmentService);
        $response = $controller->upload($this->request, $this->response);

        // 驗證結果
        $this->assertEquals(201, $response->getStatusCode());
    }

    #[Test]
    public function uploadShouldReturn400ForInvalidFile(): void
    {
        // Mock user_id attribute

        $this->request->shouldReceive('getAttribute')

            ->with('user_id')

            ->andReturn(1);

        // Mock user_id attribute

        $this->request->shouldReceive('getAttribute')

            ->with('user_id')

            ->andReturn(1);

        // 準備測試資料
        $postId = 1;
        $file = Mockery::mock(UploadedFileInterface::class);

        // 設定請求
        $this->request->shouldReceive('getAttribute')
            ->with('post_id')
            ->andReturn($postId);
        $this->request->shouldReceive('getUploadedFiles')
            ->once()
            ->andReturn(['file' => $file]);

        // 設定服務層期望行為
        // 設定服務層期望行為 - 拋出驗證例外
        $this->attachmentService->shouldReceive('upload')
            ->once()
            ->with($postId, $file, 1)
            ->andThrow(ValidationException::fromSingleError('file', '不支援的檔案類型'));

        // 設定回應期望
        $this->response->shouldReceive('getStatusCode')
            ->andReturn(400);

        // 執行測試
        $controller = new AttachmentController($this->attachmentService);
        $response = $controller->upload($this->request, $this->response);

        // 驗證結果
        $this->assertEquals(400, $response->getStatusCode());
    }

    #[Test]
    public function listShouldReturnAttachments(): void
    {
        // Mock user_id attribute

        $this->request->shouldReceive('getAttribute')

            ->with('user_id')

            ->andReturn(1);

        // Mock user_id attribute

        $this->request->shouldReceive('getAttribute')

            ->with('user_id')

            ->andReturn(1);

        // 準備測試資料
        $postId = 1;
        $attachments = [
            new Attachment([
                'id' => 1,
                'post_id' => $postId,
                'filename' => '2025/04/test1.jpg',
                'original_name' => '測試圖片1.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 1024,
                'storage_path' => '2025/04/test1.jpg',
            ]),
            new Attachment([
                'id' => 2,
                'post_id' => $postId,
                'filename' => '2025/04/test2.jpg',
                'original_name' => '測試圖片2.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 2048,
                'storage_path' => '2025/04/test2.jpg',
            ]),
        ];

        // 設定請求
        $this->request->shouldReceive('getAttribute')
            ->with('post_id')
            ->andReturn($postId);

        // 設定服務層期望行為
        $this->attachmentService->shouldReceive('getByPostId')
            ->once()
            ->with($postId)
            ->andReturn($attachments);

        // 設定回應期望
        $this->response->shouldReceive('getStatusCode')
            ->andReturn(200);

        // 執行測試
        $controller = new AttachmentController($this->attachmentService);
        $response = $controller->list($this->request, $this->response);

        // 驗證結果
        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function deleteShouldRemoveAttachment(): void
    {
        // Mock user_id attribute

        $this->request->shouldReceive('getAttribute')

            ->with('user_id')

            ->andReturn(1);

        // 準備測試資料
        $uuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

        // 設定請求
        $this->request->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn($uuid);

        // 設定服務層期望行為
        $this->attachmentService->shouldReceive('delete')
            ->once()
            ->with($uuid, 1)
            ->andReturn(true);

        // 設定回應期望
        $this->response->shouldReceive('getStatusCode')
            ->andReturn(204);

        // 執行測試
        $controller = new AttachmentController($this->attachmentService);
        $response = $controller->delete($this->request, $this->response);

        // 驗證結果
        $this->assertEquals(204, $response->getStatusCode());
    }

    #[Test]
    public function deleteShouldReturn404ForNonexistentAttachment(): void
    {
        // Mock user_id attribute

        $this->request->shouldReceive('getAttribute')

            ->with('user_id')

            ->andReturn(1);

        // Mock user_id attribute

        $this->request->shouldReceive('getAttribute')

            ->with('user_id')

            ->andReturn(1);

        // 準備測試資料
        $uuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d480';

        // 設定請求
        $this->request->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn($uuid);

        // 設定服務層期望行為
        $this->attachmentService->shouldReceive('delete')
            ->once()
            ->with($uuid, 1)
            ->andThrow(new NotFoundException('找不到指定的附件'));

        // 設定回應期望
        $this->response->shouldReceive('getStatusCode')
            ->andReturn(404);

        // 執行測試
        $controller = new AttachmentController($this->attachmentService);
        $response = $controller->delete($this->request, $this->response);

        // 驗證結果
        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function deleteShouldReturn400ForInvalidUuid(): void
    {
        // Mock user_id attribute

        $this->request->shouldReceive('getAttribute')

            ->with('user_id')

            ->andReturn(1);

        // Mock user_id attribute

        $this->request->shouldReceive('getAttribute')

            ->with('user_id')

            ->andReturn(1);

        // 準備測試資料
        $invalidUuid = 123;

        // 設定請求
        $this->request->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn($invalidUuid);

        // 設定回應期望
        $this->response->shouldReceive('getStatusCode')
            ->andReturn(400);

        // 執行測試
        $controller = new AttachmentController($this->attachmentService);
        $response = $controller->delete($this->request, $this->response);

        // 驗證結果
        $this->assertEquals(400, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
