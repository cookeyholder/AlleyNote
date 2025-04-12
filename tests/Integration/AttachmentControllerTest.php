<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\AttachmentController;
use App\Services\AttachmentService;
use App\Models\Attachment;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class AttachmentControllerTest extends TestCase
{
    private AttachmentService|MockInterface $attachmentService;
    private ServerRequestInterface|MockInterface $request;
    private ResponseInterface|MockInterface $response;
    private StreamInterface|MockInterface $stream;

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
    }

    /** @test */
    public function upload_should_store_file_successfully(): void
    {
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
            'storage_path' => '2025/04/test.jpg'
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
            ->with($postId, $file)
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

    /** @test */
    public function upload_should_return_400_for_invalid_file(): void
    {
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
        $this->attachmentService->shouldReceive('upload')
            ->once()
            ->with($postId, $file)
            ->andThrow(new ValidationException('不支援的檔案類型'));

        // 設定回應期望
        $this->response->shouldReceive('getStatusCode')
            ->andReturn(400);

        // 執行測試
        $controller = new AttachmentController($this->attachmentService);
        $response = $controller->upload($this->request, $this->response);

        // 驗證結果
        $this->assertEquals(400, $response->getStatusCode());
    }

    /** @test */
    public function list_should_return_attachments(): void
    {
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
                'storage_path' => '2025/04/test1.jpg'
            ]),
            new Attachment([
                'id' => 2,
                'post_id' => $postId,
                'filename' => '2025/04/test2.jpg',
                'original_name' => '測試圖片2.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 2048,
                'storage_path' => '2025/04/test2.jpg'
            ])
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

    /** @test */
    public function delete_should_remove_attachment(): void
    {
        // 準備測試資料
        $attachmentId = 1;

        // 設定請求
        $this->request->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn($attachmentId);

        // 設定服務層期望行為
        $this->attachmentService->shouldReceive('delete')
            ->once()
            ->with($attachmentId)
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

    /** @test */
    public function delete_should_return_404_for_nonexistent_attachment(): void
    {
        // 準備測試資料
        $attachmentId = 999;

        // 設定請求
        $this->request->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn($attachmentId);

        // 設定服務層期望行為
        $this->attachmentService->shouldReceive('delete')
            ->once()
            ->with($attachmentId)
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

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
