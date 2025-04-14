<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Controllers\PostController;
use App\Services\PostService;
use App\Exceptions\CsrfTokenException;
use Tests\TestCase;
use Mockery;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class CsrfProtectionTest extends TestCase
{
    private PostService $postService;
    private ServerRequestInterface $request;
    private ResponseInterface $response;
    private PostController $controller;
    private array $responseData;
    private int $responseStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->postService = Mockery::mock(PostService::class);
        $this->request = Mockery::mock(ServerRequestInterface::class);
        $this->response = Mockery::mock(ResponseInterface::class);
        $this->controller = new PostController($this->postService);
        $this->responseData = [];
        $this->responseStatus = 0;

        // 設定預設回應行為
        $this->response->shouldReceive('withJson')
            ->andReturnUsing(function ($data) {
                $this->responseData = $data;
                return $this->response;
            });

        $this->response->shouldReceive('withStatus')
            ->andReturnUsing(function ($status) {
                $this->responseStatus = $status;
                return $this->response;
            });

        $this->response->shouldReceive('withHeader')
            ->andReturnSelf();
    }

    /** @test */
    public function shouldRejectRequestWithoutCsrfToken(): void
    {
        // 設定請求沒有 CSRF token
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->andReturn('');

        // 準備測試資料
        $postData = [
            'title' => '測試文章',
            'content' => '測試內容'
        ];
        $this->request->shouldReceive('getParsedBody')
            ->andReturn($postData);

        // 執行測試
        $response = $this->controller->store($this->request, $this->response);

        // 斷言檢查
        $this->assertEquals(403, $this->responseStatus);
        $this->assertEquals(['error' => 'CSRF token 驗證失敗'], $this->responseData);
    }

    /** @test */
    public function shouldRejectRequestWithInvalidCsrfToken(): void
    {
        // 設定 Session 中的 token
        $_SESSION['csrf_token'] = 'valid-token';

        // 設定請求帶有無效的 token
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->andReturn('invalid-token');

        // 準備測試資料
        $postData = [
            'title' => '測試文章',
            'content' => '測試內容'
        ];
        $this->request->shouldReceive('getParsedBody')
            ->andReturn($postData);

        // 執行測試
        $response = $this->controller->store($this->request, $this->response);

        // 斷言檢查
        $this->assertEquals(403, $this->responseStatus);
        $this->assertEquals(['error' => 'CSRF token 驗證失敗'], $this->responseData);
    }

    /** @test */
    public function shouldAcceptRequestWithValidCsrfToken(): void
    {
        // 設定初始 token
        $_SESSION['csrf_token'] = 'valid-token';

        // 設定請求帶有有效的 token
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->andReturn('valid-token');

        // 準備測試資料
        $postData = [
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1
        ];
        $this->request->shouldReceive('getParsedBody')
            ->andReturn($postData);

        // 設定 Post 模擬物件
        $post = Mockery::mock('App\Models\Post');
        $post->shouldReceive('toArray')
            ->andReturn($postData + ['id' => 1]);

        // 設定服務層期望行為
        $this->postService->shouldReceive('createPost')
            ->once()
            ->with($postData)
            ->andReturn($post);

        // 執行測試
        $response = $this->controller->store($this->request, $this->response);

        // 斷言檢查
        $this->assertEquals(201, $this->responseStatus);
        $this->assertArrayHasKey('data', $this->responseData);

        // 驗證 CSRF token 是否已更新
        $this->assertNotEquals('valid-token', $_SESSION['csrf_token'] ?? null);
        $this->assertNotEmpty($_SESSION['csrf_token'] ?? null);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
        unset($_SESSION['csrf_token']);
    }
}
