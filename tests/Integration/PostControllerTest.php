<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\PostController;
use App\Services\Contracts\PostServiceInterface;
use App\Services\Security\Contracts\XssProtectionServiceInterface;
use App\Services\Security\Contracts\CsrfProtectionServiceInterface;
use App\Models\Post;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\StateTransitionException;
use Tests\TestCase;
use Mockery;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PostControllerTest extends TestCase
{
    private PostServiceInterface $postService;
    private XssProtectionServiceInterface $xssProtection;
    private CsrfProtectionServiceInterface $csrfProtection;
    private $request;
    private $response;
    private $stream;
    private $responseStatus;
    private $currentResponseData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postService = Mockery::mock(PostServiceInterface::class);
        $this->xssProtection = Mockery::mock(XssProtectionServiceInterface::class);
        $this->csrfProtection = Mockery::mock(CsrfProtectionServiceInterface::class);

        // 設定預設行為
        $this->xssProtection->shouldReceive('cleanArray')
            ->byDefault()
            ->andReturnUsing(function ($data, $fields) {
                return $data;
            });
        $this->csrfProtection->shouldReceive('validateToken')
            ->byDefault()
            ->andReturn(true);
        $this->csrfProtection->shouldReceive('generateToken')
            ->byDefault()
            ->andReturn('new-token');

        // 先建立 stream
        $this->stream = $this->createStreamMock();
        // 再建立 response
        $this->response = $this->createResponseMock();
        // 最後建立 request
        $this->request = $this->createRequestMock();
    }

    /** @test */
    public function indexShouldReturnPaginatedPosts(): void
    {
        // 準備測試資料
        $filters = ['status' => 'published'];
        $expectedResult = [
            'items' => [],
            'total' => 0,
            'page' => 1,
            'per_page' => 10,
            'last_page' => 1
        ];

        // 設定請求參數
        $this->request->shouldReceive('getQueryParams')
            ->once()
            ->andReturn(['page' => 1, 'per_page' => 10, 'status' => 'published']);

        // 設定服務層期望行為
        $this->postService->shouldReceive('listPosts')
            ->once()
            ->with(1, 10, Mockery::subset(['status' => 'published']))
            ->andReturn($expectedResult);

        // 執行測試
        $controller = new PostController(
            $this->postService,
            $this->xssProtection,
            $this->csrfProtection
        );
        $response = $controller->index($this->request, $this->response);

        // 驗證結果
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['data' => $expectedResult], $this->currentResponseData);
    }

    /** @test */
    public function showShouldReturnPostDetails(): void
    {
        // 準備測試資料
        $postId = 1;
        $postData = [
            'id' => $postId,
            'title' => '測試文章',
            'content' => '測試內容',
            'is_pinned' => false
        ];
        $post = new Post($postData);

        // 設定請求參數和屬性
        $this->request->shouldReceive('getAttribute')
            ->with('ip_address')
            ->andReturn('127.0.0.1');
        $this->request->shouldReceive('getAttribute')
            ->with('user_id')
            ->andReturn(1);

        // 設定服務層期望行為
        $this->postService->shouldReceive('findById')
            ->once()
            ->with($postId)
            ->andReturn($post);
        $this->postService->shouldReceive('recordView')
            ->once()
            ->with($postId, '127.0.0.1', 1)
            ->andReturn(true);

        // 執行測試
        $controller = new PostController(
            $this->postService,
            $this->xssProtection,
            $this->csrfProtection
        );
        $response = $controller->show($this->request, $this->response, ['id' => $postId]);

        // 驗證結果
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['data' => $post->toArray()], $this->currentResponseData);
    }

    /** @test */
    public function storeShouldCreateNewPost(): void
    {
        // 準備測試資料
        $postData = [
            'title' => '新文章',
            'content' => '文章內容',
            'is_pinned' => false
        ];
        $createdPost = new Post($postData + ['id' => 1]);

        // 設定請求資料
        $this->request->shouldReceive('getParsedBody')
            ->once()
            ->andReturn($postData);

        // 設定服務層期望行為
        $this->postService->shouldReceive('createPost')
            ->once()
            ->with($postData)
            ->andReturn($createdPost);

        // 執行測試
        $controller = new PostController(
            $this->postService,
            $this->xssProtection,
            $this->csrfProtection
        );
        $response = $controller->store($this->request, $this->response);

        // 驗證結果
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(['data' => $createdPost->toArray()], $this->currentResponseData);
    }

    /** @test */
    public function storeShouldReturn400WhenValidationFails(): void
    {
        // 準備測試資料
        $invalidData = ['title' => ''];

        // 設定請求資料
        $this->request->shouldReceive('getParsedBody')
            ->once()
            ->andReturn($invalidData);

        // 設定服務層期望行為
        $validationException = new ValidationException('標題不能為空', ['title' => ['標題不能為空']]);
        $this->postService->shouldReceive('createPost')
            ->once()
            ->with($invalidData)
            ->andThrow($validationException);

        // 執行測試
        $controller = new PostController(
            $this->postService,
            $this->xssProtection,
            $this->csrfProtection
        );
        $response = $controller->store($this->request, $this->response);

        // 驗證結果
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals([
            'error' => '標題不能為空',
            'details' => ['title' => ['標題不能為空']]
        ], $this->currentResponseData);
    }

    /** @test */
    public function updateShouldModifyExistingPost(): void
    {
        // 準備測試資料
        $postId = 1;
        $updateData = [
            'title' => '更新的標題',
            'content' => '更新的內容',
            'is_pinned' => false
        ];
        $updatedPost = new Post($updateData + ['id' => $postId]);

        // 設定請求資料
        $this->request->shouldReceive('getParsedBody')
            ->once()
            ->andReturn($updateData);

        // 設定服務層期望行為
        $this->postService->shouldReceive('updatePost')
            ->once()
            ->with($postId, $updateData)
            ->andReturn($updatedPost);

        // 執行測試
        $controller = new PostController(
            $this->postService,
            $this->xssProtection,
            $this->csrfProtection
        );
        $response = $controller->update($this->request, $this->response, ['id' => $postId]);

        // 驗證結果
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['data' => $updatedPost->toArray()], $this->currentResponseData);
    }

    /** @test */
    public function updateShouldReturn404WhenPostNotFound(): void
    {
        // 準備測試資料
        $postId = 999;
        $updateData = ['title' => '更新的標題'];

        // 設定請求資料
        $this->request->shouldReceive('getParsedBody')
            ->once()
            ->andReturn($updateData);

        // 設定服務層期望行為
        $this->postService->shouldReceive('updatePost')
            ->once()
            ->with($postId, $updateData)
            ->andThrow(new NotFoundException('找不到指定的文章'));

        // 預期的回應設定
        $this->response->shouldReceive('getStatusCode')
            ->andReturn(404);

        // 執行測試
        $controller = new PostController(
            $this->postService,
            $this->xssProtection,
            $this->csrfProtection
        );
        $response = $controller->update($this->request, $this->response, ['id' => $postId]);

        // 驗證結果
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(
            ['error' => '找不到指定的文章'],
            json_decode($response->getBody()->getContents(), true)
        );
    }

    /** @test */
    public function destroyShouldDeletePost(): void
    {
        // 準備測試資料
        $postId = 1;

        // 設定服務層期望行為
        $this->postService->shouldReceive('deletePost')
            ->once()
            ->with($postId)
            ->andReturn(true);

        // 預期的回應設定
        $this->response->shouldReceive('getStatusCode')
            ->andReturn(204);

        // 執行測試
        $controller = new PostController(
            $this->postService,
            $this->xssProtection,
            $this->csrfProtection
        );
        $response = $controller->destroy($this->request, $this->response, ['id' => $postId]);

        // 驗證結果
        $this->assertEquals(204, $response->getStatusCode());
    }

    /** @test */
    public function updatePinStatusShouldUpdatePinStatus(): void
    {
        // 準備測試資料
        $postId = 1;
        $pinData = ['is_pinned' => true];

        // 設定請求資料
        $this->request->shouldReceive('getParsedBody')
            ->once()
            ->andReturn($pinData);

        // 設定服務層期望行為
        $this->postService->shouldReceive('setPinned')
            ->once()
            ->with($postId, true)
            ->andReturn(true);

        // 預期的回應設定
        $this->response->shouldReceive('getStatusCode')
            ->andReturn(204);

        // 執行測試
        $controller = new PostController(
            $this->postService,
            $this->xssProtection,
            $this->csrfProtection
        );
        $response = $controller->updatePinStatus($this->request, $this->response, ['id' => $postId]);

        // 驗證結果
        $this->assertEquals(204, $response->getStatusCode());
    }

    /** @test */
    public function updatePinStatusShouldReturn422WhenInvalidStateTransition(): void
    {
        // 準備測試資料
        $postId = 1;
        $pinData = ['is_pinned' => true];

        // 設定請求資料
        $this->request->shouldReceive('getParsedBody')
            ->once()
            ->andReturn($pinData);

        // 設定服務層期望行為
        $this->postService->shouldReceive('setPinned')
            ->once()
            ->with($postId, true)
            ->andThrow(new StateTransitionException('只有已發布的文章可以置頂'));

        // 預期的回應設定
        $this->response->shouldReceive('getStatusCode')
            ->andReturn(422);

        // 執行測試
        $controller = new PostController(
            $this->postService,
            $this->xssProtection,
            $this->csrfProtection
        );
        $response = $controller->updatePinStatus($this->request, $this->response, ['id' => $postId]);

        // 驗證結果
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(
            ['error' => '只有已發布的文章可以置頂'],
            json_decode($response->getBody()->getContents(), true)
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    private function createRequestMock()
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->andReturn('valid-token')
            ->byDefault();
        return $request;
    }

    private function createStreamMock()
    {
        $stream = Mockery::mock(StreamInterface::class);
        $this->currentResponseData = null;
        $stream->shouldReceive('write')
            ->andReturnUsing(function ($content) use ($stream) {
                $this->currentResponseData = json_decode($content, true);
                return $stream;
            });
        $stream->shouldReceive('getContents')
            ->andReturnUsing(function () {
                return json_encode($this->currentResponseData);
            });
        return $stream;
    }

    protected function createResponseMock(): ResponseInterface
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('withHeader')
            ->andReturnSelf();
        $response->shouldReceive('withStatus')
            ->andReturnUsing(function ($status) use ($response) {
                $this->responseStatus = $status;
                return $response;
            });
        $response->shouldReceive('getStatusCode')
            ->andReturnUsing(function () {
                return $this->responseStatus ?? 200;
            });
        $response->shouldReceive('getBody')
            ->andReturn($this->stream);
        return $response;
    }
}
