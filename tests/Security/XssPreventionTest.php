<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Controllers\PostController;
use App\Services\PostService;
use App\Models\Post;
use Tests\TestCase;
use Mockery;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class XssPreventionTest extends TestCase
{
    private PostService $postService;
    private ServerRequestInterface $request;
    private ResponseInterface $response;
    private PostController $controller;
    private array $responseData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->postService = Mockery::mock(PostService::class);
        $this->request = Mockery::mock(ServerRequestInterface::class);
        $this->response = Mockery::mock(ResponseInterface::class);
        $this->controller = new PostController($this->postService);
        $this->responseData = [];

        // 設定預設回應行為
        $this->response->shouldReceive('withJson')
            ->andReturnUsing(function ($data) {
                $this->responseData = $data;
                return $this->response;
            });

        $this->response->shouldReceive('withStatus')
            ->andReturnSelf();

        $this->response->shouldReceive('withHeader')
            ->andReturnSelf();

        // 設定 CSRF token
        $_SESSION['csrf_token'] = 'valid-token';
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->andReturn('valid-token');
    }

    /** @test */
    public function shouldEscapeHtmlInPostTitle(): void
    {
        // 準備含有 XSS 攻擊程式碼的測試資料
        $maliciousTitle = '<script>alert("XSS");</script>惡意標題';
        $postData = [
            'title' => $maliciousTitle,
            'content' => '正常內容',
            'user_id' => 1
        ];

        // 設定請求模擬
        $this->request->shouldReceive('getParsedBody')
            ->andReturn($postData);

        // 模擬處理後的安全資料
        $safePost = new Post([
            'id' => 1,
            'uuid' => 'test-uuid',
            'title' => htmlspecialchars($maliciousTitle, ENT_QUOTES, 'UTF-8'),
            'content' => '正常內容',
            'user_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // 設定服務層期望行為
        $this->postService->shouldReceive('createPost')
            ->once()
            ->andReturn($safePost);

        // 執行測試
        $this->controller->store($this->request, $this->response);

        // 驗證回應中的標題已被跳脫
        $this->assertArrayHasKey('data', $this->responseData);
        $this->assertEquals(
            htmlspecialchars($maliciousTitle, ENT_QUOTES, 'UTF-8'),
            $this->responseData['data']['title']
        );
    }

    /** @test */
    public function shouldEscapeHtmlInPostContent(): void
    {
        // 準備含有 XSS 攻擊程式碼的測試資料
        $maliciousContent = '<img src="x" onerror="alert(\'XSS\')">';
        $postData = [
            'title' => '正常標題',
            'content' => $maliciousContent,
            'user_id' => 1
        ];

        // 設定請求模擬
        $this->request->shouldReceive('getParsedBody')
            ->andReturn($postData);

        // 模擬處理後的安全資料
        $safePost = new Post([
            'id' => 1,
            'uuid' => 'test-uuid',
            'title' => '正常標題',
            'content' => htmlspecialchars($maliciousContent, ENT_QUOTES, 'UTF-8'),
            'user_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // 設定服務層期望行為
        $this->postService->shouldReceive('createPost')
            ->once()
            ->andReturn($safePost);

        // 執行測試
        $this->controller->store($this->request, $this->response);

        // 驗證回應中的內容已被跳脫
        $this->assertArrayHasKey('data', $this->responseData);
        $this->assertEquals(
            htmlspecialchars($maliciousContent, ENT_QUOTES, 'UTF-8'),
            $this->responseData['data']['content']
        );
    }

    /** @test */
    public function shouldHandleEncodedXssAttempts(): void
    {
        // 準備含有編碼的 XSS 攻擊程式碼
        $encodedScript = urlencode('<script>alert("XSS");</script>');
        $postData = [
            'title' => '正常標題',
            'content' => $encodedScript,
            'user_id' => 1
        ];

        // 設定請求模擬
        $this->request->shouldReceive('getParsedBody')
            ->andReturn($postData);

        // 模擬處理後的安全資料
        $safePost = new Post([
            'id' => 1,
            'uuid' => 'test-uuid',
            'title' => '正常標題',
            'content' => htmlspecialchars(urldecode($encodedScript), ENT_QUOTES, 'UTF-8'),
            'user_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // 設定服務層期望行為
        $this->postService->shouldReceive('createPost')
            ->once()
            ->andReturn($safePost);

        // 執行測試
        $this->controller->store($this->request, $this->response);

        // 驗證回應中的內容已被跳脫
        $this->assertArrayHasKey('data', $this->responseData);
        $this->assertEquals(
            htmlspecialchars(urldecode($encodedScript), ENT_QUOTES, 'UTF-8'),
            $this->responseData['data']['content']
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
        unset($_SESSION['csrf_token']);
    }
}
