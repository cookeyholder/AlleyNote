<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Application\Controllers\Api\V1\PostController;
use App\Domains\Post\Contracts\PostServiceInterface;
use App\Domains\Post\Models\Post;
use App\Domains\Security\Contracts\CsrfProtectionServiceInterface;
use App\Domains\Security\Contracts\XssProtectionServiceInterface;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\TestCase;

class XssPreventionTest extends TestCase
{
    private PostServiceInterface $postService;

    private XssProtectionServiceInterface $xssProtection;

    private CsrfProtectionServiceInterface $csrfProtection;

    private ServerRequestInterface $request;

    private ResponseInterface $response;

    private PostController $controller;

    private StreamInterface $stream;

    private string $lastWrittenContent = '';

    private int $lastStatusCode = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->postService = Mockery::mock(PostServiceInterface::class);
        $this->xssProtection = Mockery::mock(XssProtectionServiceInterface::class);
        $this->csrfProtection = Mockery::mock(CsrfProtectionServiceInterface::class);
        $this->request = Mockery::mock(ServerRequestInterface::class);
        $this->response = Mockery::mock(ResponseInterface::class);
        $this->stream = Mockery::mock(StreamInterface::class);

        $this->controller = new PostController(
            $this->postService,
            $this->xssProtection,
            $this->csrfProtection,
        );

        // 設定預設回應行為
        $this->response->shouldReceive('getBody')
            ->andReturn($this->stream);
        $this->stream->shouldReceive('write')
            ->andReturnUsing(function ($content) {
                $this->lastWrittenContent = $content;

                return strlen($content);
                // 設定預設的 user_id 屬性
                $this->request->shouldReceive('getAttribute')
                    ->with('user_id')
                    ->andReturn(1)
                    ->byDefault();
            });
        $this->response->shouldReceive('withStatus')
            ->andReturnUsing(function ($status) {
                $this->lastStatusCode = $status;

                return $this->response;
            });
        $this->response->shouldReceive('withHeader')
            ->andReturnSelf();
        $this->response->shouldReceive('getStatusCode')
            ->andReturnUsing(function () {
                return $this->lastStatusCode;
            });

        // 設定 CSRF token 驗證
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->andReturn('valid-token');
        $this->csrfProtection->shouldReceive('validateToken')
            ->with('valid-token')
            ->andReturnNull();
        $this->csrfProtection->shouldReceive('generateToken')
            ->andReturn('new-token');
    }

    /** @test */
    public function shouldEscapeHtmlInPostTitle(): void
    {
        // 準備含有 XSS 攻擊程式碼的測試資料
        $maliciousTitle = '<script>alert("XSS");</script>惡意標題';
        $postData = [
            'title' => $maliciousTitle,
            'content' => '正常內容',
            'user_id' => 1,
        ];

        // 設定請求模擬
        $this->request->shouldReceive('getParsedBody')
            ->andReturn($postData);

        // 設定 XSS 清理行為
        $this->xssProtection->shouldReceive('cleanArray')
            ->with($postData, ['title', 'content'])
            ->andReturn([
                'title' => htmlspecialchars($maliciousTitle, ENT_QUOTES, 'UTF-8'),
                'content' => '正常內容',
                'user_id' => 1,
            ]);

        // 模擬處理後的安全資料
        $safePost = Mockery::mock(Post::class);
        $safePost->shouldReceive('toArray')
            ->andReturn([
                'id' => 1,
                'uuid' => 'test-uuid',
                'title' => htmlspecialchars($maliciousTitle, ENT_QUOTES, 'UTF-8'),
                'content' => '正常內容',
                'user_id' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        // 設定服務層期望行為
        $this->postService->shouldReceive('createPost')
            ->once()
            ->andReturn($safePost);

        // 執行測試
        $response = $this->controller->store($this->request, $this->response);

        // 驗證回應內容
        $responseData = json_decode($this->lastWrittenContent, true);
        $this->assertEquals(
            htmlspecialchars($maliciousTitle, ENT_QUOTES, 'UTF-8'),
            $responseData['data']['title'],
        );
        $this->assertEquals(201, $response->getStatusCode());
    }

    /** @test */
    public function shouldEscapeHtmlInPostContent(): void
    {
        // 準備含有 XSS 攻擊程式碼的測試資料
        $maliciousContent = '<img src="x" onerror="alert(\'XSS\')">';
        $postData = [
            'title' => '正常標題',
            'content' => $maliciousContent,
            'user_id' => 1,
        ];

        // 設定請求模擬
        $this->request->shouldReceive('getParsedBody')
            ->andReturn($postData);

        // 設定 XSS 清理行為
        $this->xssProtection->shouldReceive('cleanArray')
            ->with($postData, ['title', 'content'])
            ->andReturn([
                'title' => '正常標題',
                'content' => htmlspecialchars($maliciousContent, ENT_QUOTES, 'UTF-8'),
                'user_id' => 1,
            ]);

        // 模擬處理後的安全資料
        $safePost = Mockery::mock(Post::class);
        $safePost->shouldReceive('toArray')
            ->andReturn([
                'id' => 1,
                'uuid' => 'test-uuid',
                'title' => '正常標題',
                'content' => htmlspecialchars($maliciousContent, ENT_QUOTES, 'UTF-8'),
                'user_id' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        // 設定服務層期望行為
        $this->postService->shouldReceive('createPost')
            ->once()
            ->andReturn($safePost);

        // 執行測試
        $response = $this->controller->store($this->request, $this->response);

        // 驗證回應內容
        $responseData = json_decode($this->lastWrittenContent, true);
        $this->assertEquals(
            htmlspecialchars($maliciousContent, ENT_QUOTES, 'UTF-8'),
            $responseData['data']['content'],
        );
        $this->assertEquals(201, $response->getStatusCode());
    }

    /** @test */
    public function shouldHandleEncodedXssAttempts(): void
    {
        // 準備含有編碼的 XSS 攻擊程式碼
        $encodedScript = urlencode('<script>alert("XSS");</script>');
        $postData = [
            'title' => '正常標題',
            'content' => $encodedScript,
            'user_id' => 1,
        ];

        // 設定請求模擬
        $this->request->shouldReceive('getParsedBody')
            ->andReturn($postData);

        // 設定 XSS 清理行為
        $this->xssProtection->shouldReceive('cleanArray')
            ->with($postData, ['title', 'content'])
            ->andReturn([
                'title' => '正常標題',
                'content' => htmlspecialchars(urldecode($encodedScript), ENT_QUOTES, 'UTF-8'),
                'user_id' => 1,
            ]);

        // 模擬處理後的安全資料
        $safePost = Mockery::mock(Post::class);
        $safePost->shouldReceive('toArray')
            ->andReturn([
                'id' => 1,
                'uuid' => 'test-uuid',
                'title' => '正常標題',
                'content' => htmlspecialchars(urldecode($encodedScript), ENT_QUOTES, 'UTF-8'),
                'user_id' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        // 設定服務層期望行為
        $this->postService->shouldReceive('createPost')
            ->once()
            ->andReturn($safePost);

        // 執行測試
        $response = $this->controller->store($this->request, $this->response);

        // 驗證回應內容
        $responseData = json_decode($this->lastWrittenContent, true);
        $this->assertEquals(
            htmlspecialchars(urldecode($encodedScript), ENT_QUOTES, 'UTF-8'),
            $responseData['data']['content'],
        );
        $this->assertEquals(201, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
