<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\PostController;
use App\Services\Contracts\PostServiceInterface;
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
    private $request;
    private $response;
    private $stream;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postService = Mockery::mock(PostServiceInterface::class);
        $this->stream = $this->createStreamMock();
        $this->request = $this->createRequestMock();
        $this->response = $this->createResponseMock();
    }

    /** @test */
    public function index_should_return_paginated_posts(): void
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
        $controller = new PostController($this->postService);
        $response = $controller->index($this->request, $this->response);

        // 驗證結果
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['data' => $expectedResult], $this->currentResponseData);
    }

    /** @test */
    public function show_should_return_post_details(): void
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
        $this->postService->shouldReceive('getPost')
            ->once()
            ->with($postId)
            ->andReturn($post);
        $this->postService->shouldReceive('recordView')
            ->once()
            ->with($postId, '127.0.0.1', 1)
            ->andReturn(true);

        // 執行測試
        $controller = new PostController($this->postService);
        $response = $controller->show($this->request, $this->response, ['id' => $postId]);

        // 驗證結果
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['data' => $post->toArray()], $this->currentResponseData);
    }

    /** @test */
    public function store_should_create_new_post(): void
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
        $controller = new PostController($this->postService);
        $response = $controller->store($this->request, $this->response);

        // 驗證結果
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(['data' => $createdPost->toArray()], $this->currentResponseData);
    }

    /** @test */
    public function store_should_return_400_when_validation_fails(): void
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
        $controller = new PostController($this->postService);
        $response = $controller->store($this->request, $this->response);

        // 驗證結果
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals([
            'error' => '標題不能為空',
            'details' => ['title' => ['標題不能為空']]
        ], $this->currentResponseData);
    }

    /** @test */
    public function update_should_modify_existing_post(): void
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
        $controller = new PostController($this->postService);
        $response = $controller->update($this->request, $this->response, ['id' => $postId]);

        // 驗證結果
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['data' => $updatedPost->toArray()], $this->currentResponseData);
    }

    /** @test */
    public function update_should_return_404_when_post_not_found(): void
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
        $controller = new PostController($this->postService);
        $response = $controller->update($this->request, $this->response, ['id' => $postId]);

        // 驗證結果
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(
            ['error' => '找不到指定的文章'],
            json_decode($response->getBody()->getContents(), true)
        );
    }

    /** @test */
    public function destroy_should_delete_post(): void
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
        $controller = new PostController($this->postService);
        $response = $controller->destroy($this->request, $this->response, ['id' => $postId]);

        // 驗證結果
        $this->assertEquals(204, $response->getStatusCode());
    }

    /** @test */
    public function updatePinStatus_should_update_pin_status(): void
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
        $controller = new PostController($this->postService);
        $response = $controller->updatePinStatus($this->request, $this->response, ['id' => $postId]);

        // 驗證結果
        $this->assertEquals(204, $response->getStatusCode());
    }

    /** @test */
    public function updatePinStatus_should_return_422_when_invalid_state_transition(): void
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
        $controller = new PostController($this->postService);
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
        return Mockery::mock(ServerRequestInterface::class);
    }

    private function createStreamMock()
    {
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('write')
            ->andReturnSelf();
        $stream->shouldReceive('getContents')
            ->andReturnUsing(function () {
                return json_encode($this->currentResponseData);
            });
        return $stream;
    }

    protected function createResponseMock(): ResponseInterface
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('withHeader')->andReturnSelf();
        $response->shouldReceive('withStatus')->andReturnSelf();
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody')->andReturnSelf();
        $response->shouldReceive('write')->andReturnSelf();
        $response->shouldReceive('getContents')->andReturn('');
        return $response;
    }

    private $currentResponseData;
}
