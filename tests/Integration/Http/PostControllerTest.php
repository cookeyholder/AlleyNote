<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Application\Controllers\Api\V1\PostController;
use App\Domains\Post\Contracts\PostServiceInterface;
use App\Domains\Post\DTOs\CreatePostDTO;
use App\Domains\Post\DTOs\UpdatePostDTO;
use App\Domains\Post\Models\Post;
use App\Services\Security\Contracts\CsrfProtectionServiceInterface;
use App\Services\Security\Contracts\XssProtectionServiceInterface;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Validation\ValidationException;
use App\Shared\Validation\ValidationResult;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    private PostServiceInterface|MockInterface $postService;

    private XssProtectionServiceInterface|MockInterface $xssProtection;

    private CsrfProtectionServiceInterface|MockInterface $csrfProtection;

    private ValidatorInterface|MockInterface $validator;

    private ServerRequestInterface|MockInterface $request;

    private ResponseInterface|MockInterface $response;

    private StreamInterface|MockInterface $stream;

    private PostController $controller;

    private string $lastWrittenContent = '';

    private int $lastStatusCode = 0;

    private array $headers = [];

    protected function setUp(): void
    {
        parent::setUp();

        // 初始化所有mock對象
        $this->postService = Mockery::mock(PostServiceInterface::class);
        $this->xssProtection = Mockery::mock(XssProtectionServiceInterface::class);
        $this->csrfProtection = Mockery::mock(CsrfProtectionServiceInterface::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);
        $this->request = Mockery::mock(ServerRequestInterface::class);
        $this->response = Mockery::mock(ResponseInterface::class);
        $this->stream = Mockery::mock(StreamInterface::class);

        // 創建控制器實例
        $this->controller = new PostController(
            $this->postService,
            $this->validator,
        );

        // 設定預設的response行為
        $this->setupResponseMocks();

        // 設定預設的XSS防護
        $this->xssProtection->shouldReceive('cleanArray')
            ->andReturnUsing(function ($data) {
                return $data;
            });

        // 設定預設的CSRF防護
        $this->csrfProtection->shouldReceive('validateToken')
            ->andReturnNull();
        $this->csrfProtection->shouldReceive('generateToken')
            ->andReturn('test-token');

        // 設定預設的用戶ID
        $this->request->shouldReceive('getAttribute')
            ->with('user_id')
            ->andReturn(1)
            ->byDefault();
    }

    private function setupResponseMocks(): void
    {
        $this->response->shouldReceive('getBody')
            ->andReturn($this->stream);

        $this->stream->shouldReceive('write')
            ->andReturnUsing(function ($content) {
                $this->lastWrittenContent = $content;

                return strlen($content);
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
    }

    public function testGetPostsReturnsSuccessResponse(): void
    {
        // 設定查詢參數
        $this->request->shouldReceive('getQueryParams')
            ->andReturn([]);

        // 模擬分頁數據
        $paginatedData = [
            'data' => [
                ['id' => 1, 'title' => '測試文章1', 'content' => '內容1'],
                ['id' => 2, 'title' => '測試文章2', 'content' => '內容2'],
            ],
            'pagination' => [
                'page' => 1,
                'per_page' => 10,
                'total' => 2,
                'total_pages' => 1,
            ],
        ];

        $this->postService->shouldReceive('listPosts')
            ->once()
            ->andReturn($paginatedData);

        $response = $this->controller->index($this->request, $this->response);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($this->lastWrittenContent, true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('success', $body);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('pagination', $body);
        $this->assertArrayHasKey('timestamp', $body);
    }

    public function testGetPostsWithPaginationParameters(): void
    {
        // 設定分頁查詢參數
        $this->request->shouldReceive('getQueryParams')
            ->andReturn([
                'page' => '2',
                'limit' => '5',
            ]);

        $paginatedData = [
            'data' => [],
            'pagination' => [
                'page' => 2,
                'per_page' => 5,
                'total' => 10,
                'total_pages' => 2,
            ],
        ];

        $this->postService->shouldReceive('listPosts')
            ->once()
            ->with(2, 5, null, null)
            ->andReturn($paginatedData);

        $response = $this->controller->index($this->request, $this->response);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($this->lastWrittenContent, true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('pagination', $body);
        $this->assertEquals(2, $body['pagination']['page']);
        $this->assertEquals(5, $body['pagination']['per_page']);
    }

    public function testGetPostsWithSearchFilter(): void
    {
        $this->request->shouldReceive('getQueryParams')
            ->andReturn([
                'search' => '測試',
                'page' => '1',
                'limit' => '10',
            ]);

        $paginatedData = [
            'data' => [
                ['id' => 1, 'title' => '測試文章', 'content' => '測試內容'],
            ],
            'pagination' => [
                'page' => 1,
                'per_page' => 10,
                'total' => 1,
                'total_pages' => 1,
            ],
        ];

        $this->postService->shouldReceive('listPosts')
            ->once()
            ->with(1, 10, '測試', null)
            ->andReturn($paginatedData);

        $response = $this->controller->index($this->request, $this->response);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($this->lastWrittenContent, true);
        $this->assertTrue($body['success']);
        $this->assertCount(1, $body['data']);
        $this->assertStringContainsString('測試', $body['data'][0]['title']);
    }

    public function testGetPostsWithStatusFilter(): void
    {
        $this->request->shouldReceive('getQueryParams')
            ->andReturn([
                'status' => 'published',
                'page' => '1',
                'limit' => '10',
            ]);

        $paginatedData = [
            'data' => [
                ['id' => 1, 'title' => '已發布文章', 'status' => 'published'],
            ],
            'pagination' => [
                'page' => 1,
                'per_page' => 10,
                'total' => 1,
                'total_pages' => 1,
            ],
        ];

        $this->postService->shouldReceive('listPosts')
            ->once()
            ->with(1, 10, null, 'published')
            ->andReturn($paginatedData);

        $response = $this->controller->index($this->request, $this->response);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($this->lastWrittenContent, true);
        $this->assertTrue($body['success']);
        $this->assertEquals('published', $body['data'][0]['status']);
    }

    public function testGetPostsWithInvalidLimitReturnsValidationError(): void
    {
        $this->request->shouldReceive('getQueryParams')
            ->andReturn([
                'limit' => 'invalid',
            ]);

        $validationResult = new ValidationResult([], ['limit' => ['limit 必須是數字']], ['limit' => ['required']]);
        $this->validator->shouldReceive('validateOrFail')
            ->andThrow(new ValidationException($validationResult));

        $response = $this->controller->index($this->request, $this->response);

        $this->assertEquals(400, $response->getStatusCode());

        $body = json_decode($this->lastWrittenContent, true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('limit', $body['error']);
    }

    public function testCreatePostWithValidData(): void
    {
        $postData = [
            'title' => '測試文章標題',
            'content' => '這是測試文章的內容，應該足夠長來通過驗證規則。',
            'status' => 'draft',
        ];

        $this->request->shouldReceive('getParsedBody')
            ->andReturn($postData);

        $this->request->shouldReceive('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->andReturn('valid-token');

        $this->validator->shouldReceive('validateOrFail')
            ->once()
            ->andReturn($postData);

        $createdPost = new Post([
            'id' => 1,
            'title' => $postData['title'],
            'content' => $postData['content'],
            'status' => $postData['status'],
            'user_id' => 1,
        ]);

        $this->postService->shouldReceive('createPost')
            ->once()
            ->with(Mockery::type(CreatePostDTO::class))
            ->andReturn($createdPost);

        $response = $this->controller->store($this->request, $this->response);

        $this->assertEquals(201, $response->getStatusCode());

        $body = json_decode($this->lastWrittenContent, true);
        $this->assertTrue($body['success']);
        $this->assertEquals($postData['title'], $body['data']['title']);
        $this->assertEquals($postData['content'], $body['data']['content']);
    }

    public function testCreatePostWithInvalidJsonReturnsError(): void
    {
        $this->request->shouldReceive('getParsedBody')
            ->andReturn(null);

        $response = $this->controller->store($this->request, $this->response);

        $this->assertEquals(400, $response->getStatusCode());

        $body = json_decode($this->lastWrittenContent, true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('JSON', $body['error']);
    }

    public function testCreatePostWithMissingRequiredFields(): void
    {
        $invalidData = [
            'title' => '', // 空標題
            'content' => '', // 空內容
        ];

        $this->request->shouldReceive('getParsedBody')
            ->andReturn($invalidData);

        $this->request->shouldReceive('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->andReturn('valid-token');

        $this->request->shouldReceive('getBody')
            ->andReturn($this->stream);

        $this->stream->shouldReceive('getContents')
            ->andReturn(json_encode($invalidData));

        $this->request->shouldReceive('getBody')
            ->andReturn($this->stream);

        $this->stream->shouldReceive('getContents')
            ->andReturn(json_encode($postData));

        $validationResult = new ValidationResult([], ['title' => ['title 為必填項目']], ['title' => ['required']]);
        $this->validator->shouldReceive('validateOrFail')
            ->andThrow(new ValidationException($validationResult));

        $response = $this->controller->store($this->request, $this->response);

        $this->assertEquals(422, $response->getStatusCode());

        $body = json_decode($this->lastWrittenContent, true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('title', $body['error']);
    }

    public function testGetPostByIdReturnsSuccess(): void
    {
        $postId = 1;
        $post = new Post([
            'id' => $postId,
            'title' => '測試取得文章',
            'content' => '這是測試內容',
            'status' => 'published',
        ]);

        $this->postService->shouldReceive('findById')
            ->once()
            ->with($postId)
            ->andReturn($post);

        $response = $this->controller->show($this->request, $this->response, ['id' => $postId]);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($this->lastWrittenContent, true);
        $this->assertTrue($body['success']);
        $this->assertEquals('測試取得文章', $body['data']['title']);
    }

    public function testGetNonExistentPostReturnsNotFound(): void
    {
        $postId = 99999;

        $this->postService->shouldReceive('findById')
            ->once()
            ->with($postId)
            ->andThrow(new NotFoundException('文章不存在'));

        $response = $this->controller->show($this->request, $this->response, ['id' => $postId]);

        $this->assertEquals(404, $response->getStatusCode());

        $body = json_decode($this->lastWrittenContent, true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('不存在', $body['error']);
    }

    public function testGetPostWithInvalidIdReturnsError(): void
    {
        $invalidId = 'invalid';

        $response = $this->controller->show($this->request, $this->response, ['id' => $invalidId]);

        $this->assertEquals(400, $response->getStatusCode());

        $body = json_decode($this->lastWrittenContent, true);
        $this->assertFalse($body['success']);
    }

    public function testUpdatePostWithValidData(): void
    {
        $postId = 1;
        $updateData = [
            'title' => '更新後的標題',
            'content' => '更新後的內容，這裡有足夠的文字來通過驗證。',
        ];

        $this->request->shouldReceive('getParsedBody')
            ->andReturn($updateData);

        $this->request->shouldReceive('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->andReturn('valid-token');

        $this->validator->shouldReceive('validateOrFail')
            ->once()
            ->andReturn($updateData);

        $updatedPost = new Post([
            'id' => $postId,
            'title' => $updateData['title'],
            'content' => $updateData['content'],
            'status' => 'published',
        ]);

        $this->postService->shouldReceive('updatePost')
            ->once()
            ->with($postId, Mockery::type(UpdatePostDTO::class))
            ->andReturn($updatedPost);

        $response = $this->controller->update($this->request, $this->response, ['id' => $postId]);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($this->lastWrittenContent, true);
        $this->assertTrue($body['success']);
        $this->assertEquals('更新後的標題', $body['data']['title']);
        $this->assertEquals('更新後的內容，這裡有足夠的文字來通過驗證。', $body['data']['content']);
    }

    public function testUpdateNonExistentPostReturnsNotFound(): void
    {
        $postId = 99999;
        $updateData = [
            'title' => '更新標題',
            'content' => '更新內容',
        ];

        $this->request->shouldReceive('getParsedBody')
            ->andReturn($updateData);

        $this->request->shouldReceive('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->andReturn('valid-token');

        $this->validator->shouldReceive('validateOrFail')
            ->once()
            ->andReturn($updateData);

        $this->postService->shouldReceive('updatePost')
            ->once()
            ->with($postId, Mockery::type(UpdatePostDTO::class))
            ->andThrow(new NotFoundException('文章不存在'));

        $response = $this->controller->update($this->request, $this->response, ['id' => $postId]);

        $this->assertEquals(404, $response->getStatusCode());

        $body = json_decode($this->lastWrittenContent, true);
        $this->assertFalse($body['success']);
    }

    public function testDeletePost(): void
    {
        $postId = 1;

        $this->request->shouldReceive('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->andReturn('valid-token');

        $this->postService->shouldReceive('deletePost')
            ->once()
            ->with($postId)
            ->andReturnNull();

        $response = $this->controller->delete($this->request, $this->response, ['id' => $postId]);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty($this->lastWrittenContent);
    }

    public function testDeleteNonExistentPostReturnsNotFound(): void
    {
        $postId = 99999;

        $this->request->shouldReceive('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->andReturn('valid-token');

        $this->postService->shouldReceive('deletePost')
            ->once()
            ->with($postId)
            ->andThrow(new NotFoundException('文章不存在'));

        $response = $this->controller->delete($this->request, $this->response, ['id' => $postId]);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testTogglePostPin(): void
    {
        $postId = 1;
        $pinData = ['pinned' => true];

        $this->request->shouldReceive('getParsedBody')
            ->andReturn($pinData);

        $this->request->shouldReceive('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->andReturn('valid-token');

        $this->validator->shouldReceive('validateOrFail')
            ->once()
            ->andReturn($pinData);

        $updatedPost = new Post([
            'id' => $postId,
            'title' => '測試文章',
            'content' => '內容',
            'pinned' => true,
        ]);

        $this->postService->shouldReceive('updatePost')
            ->once()
            ->with($postId, Mockery::type(UpdatePostDTO::class))
            ->andReturn($updatedPost);

        $response = $this->controller->togglePin($this->request, $this->response, ['id' => $postId]);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($this->lastWrittenContent, true);
        $this->assertTrue($body['success']);
        $this->assertStringContainsString('置頂', $body['message']);
    }

    public function testTogglePostPinWithInvalidData(): void
    {
        $postId = 1;
        $invalidData = ['pinned' => 'invalid'];

        $this->request->shouldReceive('getParsedBody')
            ->andReturn($invalidData);

        $this->request->shouldReceive('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->andReturn('valid-token');

        $validationResult = new ValidationResult([], ['pinned' => ['pinned 必須是布林值']], ['pinned' => ['boolean']]);
        $this->validator->shouldReceive('validateOrFail')
            ->andThrow(new ValidationException($validationResult));

        $response = $this->controller->togglePin($this->request, $this->response, ['id' => $postId]);

        $this->assertEquals(422, $response->getStatusCode());

        $body = json_decode($this->lastWrittenContent, true);
        $this->assertFalse($body['success']);
    }

    public function testApiResponseStructureConsistency(): void
    {
        $this->request->shouldReceive('getQueryParams')
            ->andReturn([]);

        $paginatedData = [
            'data' => [],
            'pagination' => [
                'page' => 1,
                'per_page' => 10,
                'total' => 0,
                'total_pages' => 0,
            ],
        ];

        $this->postService->shouldReceive('listPosts')
            ->once()
            ->andReturn($paginatedData);

        $response = $this->controller->index($this->request, $this->response);

        $body = json_decode($this->lastWrittenContent, true);

        // 檢查所有必要的結構
        $this->assertArrayHasKey('success', $body);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('pagination', $body);
        $this->assertArrayHasKey('timestamp', $body);

        // 檢查分頁結構
        $pagination = $body['pagination'];
        $this->assertArrayHasKey('page', $pagination);
        $this->assertArrayHasKey('per_page', $pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('total_pages', $pagination);
    }

    public function testHealthEndpoint(): void
    {
        // 如果有健康檢查端點的話
        $this->assertTrue(true); // 佔位符測試
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
