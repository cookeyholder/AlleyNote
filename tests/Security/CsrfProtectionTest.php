<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Application\Controllers\Api\V1\PostController;
use App\Domains\Post\Models\Post;
use App\Services\Contracts\PostServiceInterface;
use App\Services\Security\Contracts\CsrfProtectionServiceInterface;
use App\Services\Security\Contracts\XssProtectionServiceInterface;
use App\Shared\Exceptions\CsrfTokenException;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\TestCase;

class CsrfProtectionTest extends TestCase
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

    private array $headers = [];

    protected function setUp(): void
    {
        parent::setUp();

        // 使用介面來建立 mock 物件
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
            ->andReturnUsing(function ($name, $value) {
                $this->headers[$name] = $value;

                return $this->response;
            });
        $this->response->shouldReceive('getStatusCode')
            ->andReturnUsing(function () {
                return $this->lastStatusCode;
            });
        $this->response->shouldReceive('getHeaderLine')
            ->andReturnUsing(function ($name) {
                return $this->headers[$name] ?? '';
            });

        // 設定 XSS 防護預設行為
        $this->xssProtection->shouldReceive('cleanArray')
            ->andReturnUsing(function ($data) {
                return $data;
            });
    }

    /** @test */
    public function shouldRejectRequestWithoutCsrfToken(): void
    {
        // 設定請求沒有 CSRF token
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->andReturn('');

        // 設定 CSRF 驗證失敗
        $this->csrfProtection->shouldReceive('validateToken')
            ->with('')
            ->andThrow(new CsrfTokenException('CSRF token 驗證失敗'));

        // 準備測試資料
        $postData = [
            'title' => '測試文章',
            'content' => '測試內容',
        ];
        $this->request->shouldReceive('getParsedBody')
            ->andReturn($postData);

        // 執行測試
        $response = $this->controller->store($this->request, $this->response);

        // 驗證回應內容
        $responseData = json_decode($this->lastWrittenContent, true);
        $this->assertEquals('CSRF token 驗證失敗', $responseData['error']);
        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function shouldRejectRequestWithInvalidCsrfToken(): void
    {
        // 設定請求帶有無效的 token
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->andReturn('invalid-token');

        // 設定 CSRF 驗證失敗
        $this->csrfProtection->shouldReceive('validateToken')
            ->with('invalid-token')
            ->andThrow(new CsrfTokenException('CSRF token 驗證失敗'));

        // 準備測試資料
        $postData = [
            'title' => '測試文章',
            'content' => '測試內容',
        ];
        $this->request->shouldReceive('getParsedBody')
            ->andReturn($postData);

        // 執行測試
        $response = $this->controller->store($this->request, $this->response);

        // 驗證回應內容
        $responseData = json_decode($this->lastWrittenContent, true);
        $this->assertEquals('CSRF token 驗證失敗', $responseData['error']);
        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function shouldAcceptRequestWithValidCsrfToken(): void
    {
        // 設定請求帶有有效的 token
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->andReturn('valid-token');

        // 設定 CSRF 驗證成功
        $this->csrfProtection->shouldReceive('validateToken')
            ->with('valid-token')
            ->andReturnNull();

        // 設定產生新的 CSRF token
        $this->csrfProtection->shouldReceive('generateToken')
            ->andReturn('new-token');

        // 準備測試資料
        $postData = [
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1,
        ];
        $this->request->shouldReceive('getParsedBody')
            ->andReturn($postData);

        // 設定 Post 模擬物件
        $post = Mockery::mock('App\Domains\Post\Models\Post');
        $post->shouldReceive('toArray')
            ->andReturn($postData + ['id' => 1]);

        // 設定服務層期望行為
        $this->postService->shouldReceive('createPost')
            ->once()
            ->with($postData)
            ->andReturn($post);

        // 執行測試
        $response = $this->controller->store($this->request, $this->response);

        // 驗證回應內容
        $responseData = json_decode($this->lastWrittenContent, true);
        $this->assertEquals($postData + ['id' => 1], $responseData['data']);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('new-token', $this->headers['X-CSRF-TOKEN']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
