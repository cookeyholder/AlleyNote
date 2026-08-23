<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Api\V1;

use App\Application\Controllers\Api\V1\PostController;
use App\Domains\Auth\Contracts\AuthorizationServiceInterface;
use App\Domains\Post\Contracts\PostServiceInterface;
use App\Domains\Post\DTOs\CreatePostDTO;
use App\Domains\Post\Exceptions\PostNotFoundException;
use App\Domains\Post\Exceptions\PostStatusException;
use App\Domains\Post\Models\Post;
use App\Domains\Post\Validation\PostValidator;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Statistics\Services\PostViewStatisticsService;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Stream;
use App\Infrastructure\Http\Uri;
use App\Shared\Contracts\OutputSanitizerInterface;
use App\Shared\Exceptions\StateTransitionException;
use App\Shared\Exceptions\Validation\RequestValidationException;
use GuzzleHttp\Psr7\ServerRequest as GuzzleServerRequest;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * PostController 單元測試.
 *
 * 以真實 PSR-7 物件（App\Infrastructure\Http）與 Mockery 模擬相依服務，
 * 驗證每個動作的成功與錯誤路徑。
 */
#[CoversClass(PostController::class)]
final class PostControllerTest extends UnitTestCase
{
    private PostController $controller;

    private PostServiceInterface&MockInterface $postService;

    private OutputSanitizerInterface&MockInterface $sanitizer;

    private ActivityLoggingServiceInterface&MockInterface $activityLogger;

    private PostViewStatisticsService&MockInterface $postViewStatsService;

    private AuthorizationServiceInterface&MockInterface $authService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->postService = Mockery::mock(PostServiceInterface::class);
        $this->sanitizer = Mockery::mock(OutputSanitizerInterface::class);
        $this->activityLogger = Mockery::mock(ActivityLoggingServiceInterface::class);
        $this->postViewStatsService = Mockery::mock(PostViewStatisticsService::class);
        $this->authService = Mockery::mock(AuthorizationServiceInterface::class);

        // 輸出清理器預設原樣回傳
        $this->sanitizer->shouldReceive('sanitizeHtml')
            ->byDefault()
            ->andReturnUsing(static fn(string $content): string => $content);
        $this->sanitizer->shouldReceive('sanitizeRichText')
            ->byDefault()
            ->andReturnUsing(static fn(string $content): string => $content);

        // 活動記錄預設允許任意次數
        $this->activityLogger->shouldReceive('logSuccess')->byDefault()->andReturn(true);
        $this->activityLogger->shouldReceive('logFailure')->byDefault()->andReturn(true);

        // 權限檢查預設拒絕
        $this->authService->shouldReceive('can')->byDefault()->andReturn(false);

        // 使用真實驗證器，讓 DTO 驗證流程完整運作
        $validator = new PostValidator();

        $this->controller = new PostController(
            $this->postService,
            $validator,
            $this->sanitizer,
            $this->activityLogger,
            $this->postViewStatsService,
            $this->authService,
        );
    }

    /**
     * 建構測試用請求.
     *
     * @param array<string, mixed> $options 可用鍵值：method、user_id、query、parsed、body、attributes、ip
     */
    private function makeRequest(array $options = []): ServerRequest
    {
        $methodOption = $options['method'] ?? 'GET';
        $method = is_string($methodOption) ? $methodOption : 'GET';
        $ipOption = $options['ip'] ?? '10.0.0.1';
        $serverParams = ['REMOTE_ADDR' => is_string($ipOption) ? $ipOption : '10.0.0.1'];

        $body = isset($options['body']) && is_string($options['body']) ? new Stream($options['body']) : null;
        $request = new ServerRequest($method, new Uri('/api/posts'), [], $body, '1.1', $serverParams);

        if (array_key_exists('user_id', $options)) {
            $request = $request->withAttribute('user_id', $options['user_id']);
        }
        if (isset($options['query']) && is_array($options['query'])) {
            $request = $request->withQueryParams($options['query']);
        }
        if (array_key_exists('parsed', $options) && is_array($options['parsed'])) {
            $request = $request->withParsedBody($options['parsed']);
        }
        if (isset($options['attributes']) && is_array($options['attributes'])) {
            foreach ($options['attributes'] as $name => $value) {
                if (is_string($name)) {
                    $request = $request->withAttribute($name, $value);
                }
            }
        }

        return $request;
    }

    /**
     * 建構測試用文章模型.
     *
     * @param array<string, mixed> $overrides
     */
    private function makePost(int $id, int $userId = 10, array $overrides = []): Post
    {
        return new Post(array_merge([
            'id'           => $id,
            'uuid'         => 'uuid-' . $id,
            'seq_number'   => (string) (100 + $id),
            'title'        => '測試標題' . $id,
            'content'      => '<p>測試內容</p>',
            'user_id'      => $userId,
            'user_ip'      => '127.0.0.1',
            'is_pinned'    => false,
            'status'       => 'draft',
            'views'        => 3,
            'publish_date' => '2026-01-01T00:00:00+00:00',
            'created_at'   => '2026-01-01T00:00:00+00:00',
            'updated_at'   => '2026-01-02T00:00:00+00:00',
        ], $overrides));
    }

    /**
     * 解碼回應 JSON 主體.
     *
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }

    /**
     * 型別窄化：確保值為陣列.
     *
     * @return array<mixed>
     */
    private static function asArray(mixed $value): array
    {
        assert(is_array($value));

        return $value;
    }

    /**
     * 允許使用者管理文章（admin 權限）.
     */
    private function allowManage(int $userId): void
    {
        $this->authService->shouldReceive('can')->with($userId, 'post', 'manage')->andReturn(true);
    }

    #[Test]
    public function test_index成功回傳分頁列表(): void
    {
        $request = $this->makeRequest([
            'user_id' => 1,
            'query'   => ['page' => '2', 'limit' => '5', 'search' => '公告', 'status' => 'published'],
        ]);
        $post = $this->makePost(5);

        $this->postService
            ->shouldReceive('listPosts')
            ->once()
            ->with(2, 5, ['search' => '公告', 'status' => 'published'])
            ->andReturn(['items' => [$post], 'total' => 11, 'page' => 2, 'perPage' => 5]);
        $this->postViewStatsService
            ->shouldReceive('getBatchPostViewStats')
            ->once()
            ->with([5])
            ->andReturn([5 => ['views' => 9, 'unique_visitors' => 4]]);

        $result = $this->controller->index($request, new Response());

        $this->assertSame(200, $result->getStatusCode());
        $body = $this->decode($result);
        $this->assertTrue($body['success']);
        $pagination = self::asArray($body['pagination']);
        $this->assertSame(11, $pagination['total']);
        $this->assertSame(2, $pagination['page']);
        $items = self::asArray($body['data']);
        $firstItem = self::asArray($items[0]);
        $this->assertSame(5, $firstItem['id']);
        $this->assertSame(9, $firstItem['views']);
        $this->assertSame(4, $firstItem['unique_visitors']);
    }

    #[Test]
    public function test_index請求驗證失敗回傳422(): void
    {
        $request = $this->makeRequest(['user_id' => 1]);

        $this->postService
            ->shouldReceive('listPosts')
            ->once()
            ->andThrow(new RequestValidationException('請求參數無效', ['limit' => ['必須為整數']]));

        $result = $this->controller->index($request, new Response());

        $this->assertSame(422, $result->getStatusCode());
        $body = $this->decode($result);
        $this->assertFalse($body['success']);
        $this->assertArrayHasKey('errors', $body);
    }

    #[Test]
    public function test_index一般錯誤回傳500(): void
    {
        $request = $this->makeRequest(['user_id' => 1]);

        $this->postService
            ->shouldReceive('listPosts')
            ->once()
            ->andThrow(new RuntimeException('資料庫錯誤'));

        $result = $this->controller->index($request, new Response());

        $this->assertSame(500, $result->getStatusCode());
        $this->assertFalse($this->decode($result)['success']);
    }

    #[Test]
    public function test_store非陣列內容回傳400(): void
    {
        // 專案自製 ServerRequest 的 parsedBody 僅接受陣列，
        // 因此以 Guzzle 實作模擬非陣列主體的請求
        $request = new GuzzleServerRequest('POST', new Uri('/api/posts'))
            ->withAttribute('user_id', 1)
            ->withParsedBody((object) ['unexpected' => true]);

        $result = $this->controller->store($request, new Response());

        $this->assertSame(400, $result->getStatusCode());
        $this->assertFalse($this->decode($result)['success']);
    }

    #[Test]
    public function test_store缺少身分驗證回傳401(): void
    {
        $request = $this->makeRequest(['method' => 'POST', 'parsed' => ['title' => 'x']]);

        $result = $this->controller->store($request, new Response());

        $this->assertSame(401, $result->getStatusCode());
    }

    #[Test]
    public function test_store成功建立文章並設定標籤(): void
    {
        $request = $this->makeRequest([
            'method'  => 'POST',
            'user_id' => 7,
            'parsed'  => [
                'title'   => '新文章標題',
                'content' => '這是文章內容',
                'tag_ids' => ['1', 'x', 3],
            ],
        ]);
        $post = $this->makePost(55, 7, ['title' => '新文章標題']);

        $this->postService
            ->shouldReceive('createPost')
            ->once()
            ->with(Mockery::on(static fn(mixed $dto): bool => $dto instanceof CreatePostDTO
                && $dto->title === '新文章標題'
                && $dto->userId === 7))
            ->andReturn($post);
        $this->postService->shouldReceive('setTags')->once()->with(55, [1, 3]);

        $result = $this->controller->store($request, new Response());

        $this->assertSame(201, $result->getStatusCode());
        $body = $this->decode($result);
        $this->assertTrue($body['success']);
        $data = self::asArray($body['data']);
        $this->assertSame(55, $data['id']);
        $this->assertSame('新文章標題', $data['title']);
    }

    #[Test]
    public function test_store驗證失敗回傳400(): void
    {
        $request = $this->makeRequest([
            'method'  => 'POST',
            'user_id' => 7,
            'parsed'  => ['content' => '只有內容'],
        ]);

        $this->postService->shouldNotReceive('createPost');

        $result = $this->controller->store($request, new Response());

        $this->assertSame(400, $result->getStatusCode());
        $errors = self::asArray($this->decode($result)['errors'] ?? []);
        $this->assertArrayHasKey('title', $errors);
    }

    #[Test]
    public function test_store服務錯誤回傳500(): void
    {
        $request = $this->makeRequest([
            'method'  => 'POST',
            'user_id' => 7,
            'parsed'  => ['title' => '合法標題', 'content' => '合法內容'],
        ]);

        $this->postService
            ->shouldReceive('createPost')
            ->once()
            ->andThrow(new RuntimeException('儲存失敗'));

        $result = $this->controller->store($request, new Response());

        $this->assertSame(500, $result->getStatusCode());
    }

    #[Test]
    public function test_show成功取得單篇文章(): void
    {
        $request = $this->makeRequest(['user_id' => 2, 'ip' => '10.0.0.5']);
        $post = $this->makePost(5);

        $this->postService->shouldReceive('findById')->once()->with(5)->andReturn($post);
        $this->postService->shouldReceive('recordView')->once()->with(5, '10.0.0.5');
        $this->postViewStatsService
            ->shouldReceive('getPostViewStats')
            ->once()
            ->with(5)
            ->andReturn(['views' => 9, 'unique_visitors' => 4]);

        $result = $this->controller->show($request, new Response(), ['id' => '5']);

        $this->assertSame(200, $result->getStatusCode());
        $data = self::asArray($this->decode($result)['data']);
        $this->assertSame(5, $data['id']);
        $this->assertSame(9, $data['views']);
        $this->assertSame(4, $data['unique_visitors']);
    }

    #[Test]
    public function test_show無效ID回傳400(): void
    {
        $request = $this->makeRequest(['user_id' => 2]);

        $result = $this->controller->show($request, new Response(), ['id' => 'abc']);

        $this->assertSame(400, $result->getStatusCode());
    }

    #[Test]
    public function test_show找不到文章回傳404(): void
    {
        $request = $this->makeRequest(['user_id' => 2]);

        $this->postService
            ->shouldReceive('findById')
            ->once()
            ->andThrow(new PostNotFoundException(5));

        $result = $this->controller->show($request, new Response(), ['id' => '5']);

        $this->assertSame(404, $result->getStatusCode());
    }

    #[Test]
    public function test_show一般錯誤回傳500(): void
    {
        $request = $this->makeRequest(['user_id' => 2]);

        $this->postService->shouldReceive('findById')->once()->andReturn($this->makePost(5));
        $this->postService
            ->shouldReceive('recordView')
            ->once()
            ->andThrow(new RuntimeException('瀏覽記錄失敗'));

        $result = $this->controller->show($request, new Response(), ['id' => '5']);

        $this->assertSame(500, $result->getStatusCode());
    }

    #[Test]
    public function test_update作者成功更新內容(): void
    {
        $request = $this->makeRequest([
            'method'  => 'PUT',
            'user_id' => 10,
            'parsed'  => ['title' => '改後標題'],
        ]);

        $this->postService->shouldReceive('findById')->twice()->with(5)->andReturn(
            $this->makePost(5, 10),
            $this->makePost(5, 10, ['title' => '改後標題']),
        );
        $this->postService
            ->shouldReceive('updatePost')
            ->once()
            ->andReturn($this->makePost(5, 10, ['title' => '改後標題']));

        $result = $this->controller->update($request, new Response(), ['id' => '5']);

        $this->assertSame(200, $result->getStatusCode());
        $data = self::asArray($this->decode($result)['data']);
        $this->assertSame('改後標題', $data['title']);
    }

    #[Test]
    public function test_update僅更新標籤仍回傳成功(): void
    {
        $request = $this->makeRequest([
            'method'  => 'PUT',
            'user_id' => 10,
            'parsed'  => ['tag_ids' => [3]],
        ]);
        $post = $this->makePost(5, 10);

        $this->postService->shouldReceive('findById')->twice()->with(5)->andReturn($post);
        $this->postService->shouldReceive('setTags')->once()->with(5, [3]);

        $result = $this->controller->update($request, new Response(), ['id' => '5']);

        $this->assertSame(200, $result->getStatusCode());
    }

    #[Test]
    public function test_update沒有變更時回傳400(): void
    {
        $request = $this->makeRequest([
            'method'  => 'PUT',
            'user_id' => 10,
            'parsed'  => [],
        ]);

        $this->postService->shouldReceive('findById')->once()->andReturn($this->makePost(5, 10));

        $result = $this->controller->update($request, new Response(), ['id' => '5']);

        $this->assertSame(400, $result->getStatusCode());
    }

    #[Test]
    public function test_update權限不足回傳403(): void
    {
        $request = $this->makeRequest([
            'method'  => 'PUT',
            'user_id' => 20,
            'parsed'  => ['title' => '任何標題'],
        ]);

        $this->postService->shouldReceive('findById')->once()->andReturn($this->makePost(5, 99));

        $result = $this->controller->update($request, new Response(), ['id' => '5']);

        $this->assertSame(403, $result->getStatusCode());
    }

    #[Test]
    public function test_update管理員可更新他人文章(): void
    {
        $this->allowManage(1);

        $request = $this->makeRequest([
            'method'  => 'PUT',
            'user_id' => 1,
            'parsed'  => ['title' => '管理員改標題'],
        ]);

        $this->postService->shouldReceive('findById')->once()->andReturn($this->makePost(5, 99));
        $this->postService
            ->shouldReceive('updatePost')
            ->once()
            ->andReturn($this->makePost(5, 99, ['title' => '管理員改標題']));

        $result = $this->controller->update($request, new Response(), ['id' => '5']);

        $this->assertSame(200, $result->getStatusCode());
    }

    #[Test]
    public function test_update無效ID回傳400(): void
    {
        $request = $this->makeRequest(['method' => 'PUT', 'user_id' => 1, 'parsed' => ['title' => 'x']]);

        $result = $this->controller->update($request, new Response(), ['id' => '-3']);

        $this->assertSame(400, $result->getStatusCode());
    }

    #[Test]
    public function test_update非陣列內容回傳400(): void
    {
        // 同前：以 Guzzle 實作模擬 null 主體（非陣列）
        $request = new GuzzleServerRequest('PUT', new Uri('/api/posts'))
            ->withAttribute('user_id', 1);

        $this->allowManage(1);

        $result = $this->controller->update($request, new Response(), ['id' => '5']);

        $this->assertSame(400, $result->getStatusCode());
    }

    #[Test]
    public function test_update找不到文章回傳404(): void
    {
        $request = $this->makeRequest([
            'method'  => 'PUT',
            'user_id' => 10,
            'parsed'  => ['title' => '任何'],
        ]);

        $this->postService
            ->shouldReceive('findById')
            ->once()
            ->andThrow(new PostNotFoundException(5));

        $result = $this->controller->update($request, new Response(), ['id' => '5']);

        $this->assertSame(404, $result->getStatusCode());
    }

    #[Test]
    public function test_update驗證失敗回傳400(): void
    {
        $request = $this->makeRequest([
            'method'  => 'PUT',
            'user_id' => 10,
            'parsed'  => ['title' => str_repeat('長', 300)],
        ]);

        $this->postService->shouldReceive('findById')->once()->andReturn($this->makePost(5, 10));

        $result = $this->controller->update($request, new Response(), ['id' => '5']);

        $this->assertSame(400, $result->getStatusCode());
    }

    #[Test]
    public function test_delete成功回傳204(): void
    {
        $request = $this->makeRequest(['method' => 'DELETE', 'user_id' => 10]);
        $post = $this->makePost(5, 10);

        $this->postService->shouldReceive('findById')->once()->andReturn($post);
        $this->postService->shouldReceive('deletePost')->once()->with(5)->andReturn(true);

        $result = $this->controller->delete($request, new Response(), ['id' => '5']);

        $this->assertSame(204, $result->getStatusCode());
        $this->assertSame('', (string) $result->getBody());
    }

    #[Test]
    public function test_destroy委派至delete行為一致(): void
    {
        $request = $this->makeRequest(['method' => 'DELETE', 'user_id' => 10]);

        $this->postService->shouldReceive('findById')->once()->andReturn($this->makePost(6, 10));
        $this->postService->shouldReceive('deletePost')->once()->with(6)->andReturn(true);

        $result = $this->controller->destroy($request, new Response(), ['id' => '6']);

        $this->assertSame(204, $result->getStatusCode());
    }

    #[Test]
    public function test_delete權限不足回傳403(): void
    {
        $request = $this->makeRequest(['method' => 'DELETE', 'user_id' => 20]);

        $this->postService->shouldReceive('findById')->once()->andReturn($this->makePost(5, 10));

        $result = $this->controller->delete($request, new Response(), ['id' => '5']);

        $this->assertSame(403, $result->getStatusCode());
    }

    #[Test]
    public function test_delete無效ID回傳400(): void
    {
        $request = $this->makeRequest(['method' => 'DELETE', 'user_id' => 1]);

        $result = $this->controller->delete($request, new Response(), ['id' => '0']);

        $this->assertSame(400, $result->getStatusCode());
    }

    #[Test]
    public function test_delete狀態限制回傳422(): void
    {
        $request = $this->makeRequest(['method' => 'DELETE', 'user_id' => 1]);
        $this->allowManage(1);

        $this->postService->shouldReceive('findById')->once()->andReturn($this->makePost(5));
        $this->postService
            ->shouldReceive('deletePost')
            ->once()
            ->andThrow(new PostStatusException('已發布的文章不能刪除，請改為封存'));

        $result = $this->controller->delete($request, new Response(), ['id' => '5']);

        $this->assertSame(422, $result->getStatusCode());
    }

    #[Test]
    public function test_delete找不到文章回傳404(): void
    {
        $request = $this->makeRequest(['method' => 'DELETE', 'user_id' => 1]);

        $this->postService
            ->shouldReceive('findById')
            ->once()
            ->andThrow(new PostNotFoundException(5));

        $result = $this->controller->delete($request, new Response(), ['id' => '5']);

        $this->assertSame(404, $result->getStatusCode());
    }

    #[Test]
    public function test_togglePin置頂成功(): void
    {
        $request = $this->makeRequest([
            'method'  => 'POST',
            'user_id' => 1,
            'body'    => '{"pinned":true}',
        ]);
        $post = $this->makePost(5);

        $this->postService->shouldReceive('setPinned')->once()->with(5, true);
        $this->postService->shouldReceive('findById')->once()->with(5)->andReturn($post);

        $result = $this->controller->togglePin($request, new Response(), ['id' => '5']);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('貼文已設為置頂', $this->decode($result)['message']);
    }

    #[Test]
    public function test_togglePin取消置頂成功(): void
    {
        $request = $this->makeRequest([
            'method'  => 'POST',
            'user_id' => 1,
            'body'    => '{"pinned":false}',
        ]);
        $post = $this->makePost(5);

        $this->postService->shouldReceive('setPinned')->once()->with(5, false);
        $this->postService->shouldReceive('findById')->once()->with(5)->andReturn($post);

        $result = $this->controller->togglePin($request, new Response(), ['id' => '5']);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('貼文已取消置頂', $this->decode($result)['message']);
    }

    #[Test]
    public function test_togglePin無效JSON回傳400(): void
    {
        $request = $this->makeRequest([
            'method'  => 'POST',
            'user_id' => 1,
            'body'    => '{invalid-json',
        ]);

        $result = $this->controller->togglePin($request, new Response(), ['id' => '5']);

        $this->assertSame(400, $result->getStatusCode());
    }

    #[Test]
    public function test_togglePin缺少pinned參數回傳400(): void
    {
        $request = $this->makeRequest([
            'method'  => 'POST',
            'user_id' => 1,
            'body'    => '{"other":true}',
        ]);

        $result = $this->controller->togglePin($request, new Response(), ['id' => '5']);

        $this->assertSame(400, $result->getStatusCode());
    }

    #[Test]
    public function test_togglePin無效ID回傳400(): void
    {
        $request = $this->makeRequest(['method' => 'POST', 'user_id' => 1, 'body' => '{"pinned":true}']);

        $result = $this->controller->togglePin($request, new Response(), ['id' => 'oops']);

        $this->assertSame(400, $result->getStatusCode());
    }

    #[Test]
    public function test_togglePin找不到文章回傳404(): void
    {
        $request = $this->makeRequest([
            'method'  => 'POST',
            'user_id' => 1,
            'body'    => '{"pinned":true}',
        ]);

        $this->postService
            ->shouldReceive('setPinned')
            ->once()
            ->andThrow(new PostNotFoundException(5));

        $result = $this->controller->togglePin($request, new Response(), ['id' => '5']);

        $this->assertSame(404, $result->getStatusCode());
    }

    #[Test]
    public function test_togglePin狀態轉換錯誤回傳422(): void
    {
        $request = $this->makeRequest([
            'method'  => 'POST',
            'user_id' => 1,
            'body'    => '{"pinned":true}',
        ]);

        $this->postService
            ->shouldReceive('setPinned')
            ->once()
            ->andThrow(new StateTransitionException('狀態不允許置頂'));

        $result = $this->controller->togglePin($request, new Response(), ['id' => '5']);

        $this->assertSame(422, $result->getStatusCode());
    }

    #[Test]
    public function test_publish成功發布文章(): void
    {
        $request = $this->makeRequest(['user_id' => 1]);

        $this->postService->shouldReceive('findById')->once()->with(5)->andReturn($this->makePost(5, 1));
        $this->postService
            ->shouldReceive('updatePostStatus')
            ->once()
            ->with(5, 'published')
            ->andReturn($this->makePost(5, 1, ['status' => 'published']));

        $result = $this->controller->publish($request, new Response(), ['id' => '5']);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('published', self::asArray($this->decode($result)['data'])['status']);
    }

    #[Test]
    public function test_publish權限不足回傳403(): void
    {
        $request = $this->makeRequest(['user_id' => 2]);

        $this->postService->shouldReceive('findById')->once()->andReturn($this->makePost(5, 99));

        $result = $this->controller->publish($request, new Response(), ['id' => '5']);

        $this->assertSame(403, $result->getStatusCode());
    }

    #[Test]
    public function test_publish無效參數回傳400(): void
    {
        $request = $this->makeRequest([]);

        $result = $this->controller->publish($request, new Response(), ['id' => '5']);

        $this->assertSame(400, $result->getStatusCode());
    }

    #[Test]
    public function test_publish找不到文章回傳404(): void
    {
        $request = $this->makeRequest(['user_id' => 1]);

        $this->postService
            ->shouldReceive('findById')
            ->once()
            ->andThrow(new PostNotFoundException(5));

        $result = $this->controller->publish($request, new Response(), ['id' => '5']);

        $this->assertSame(404, $result->getStatusCode());
        $this->assertSame('貼文不存在', $this->decode($result)['message']);
    }

    #[Test]
    public function test_unpublish成功取消發布(): void
    {
        $request = $this->makeRequest(['user_id' => 1]);

        $this->postService->shouldReceive('findById')->once()->with(5)->andReturn($this->makePost(5, 1));
        $this->postService
            ->shouldReceive('updatePostStatus')
            ->once()
            ->with(5, 'draft')
            ->andReturn($this->makePost(5, 1, ['status' => 'draft']));

        $result = $this->controller->unpublish($request, new Response(), ['id' => '5']);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('貼文已取消發布', $this->decode($result)['message']);
    }

    #[Test]
    public function test_unpin成功取消置頂(): void
    {
        $request = $this->makeRequest(['user_id' => 1]);

        $this->postService->shouldReceive('findById')->twice()->with(5)->andReturn($this->makePost(5, 1));
        $this->postService->shouldReceive('setPinned')->once()->with(5, false);

        $result = $this->controller->unpin($request, new Response(), ['id' => '5']);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('已取消置頂', $this->decode($result)['message']);
    }

    #[Test]
    public function test_unpin無效參數回傳400(): void
    {
        $request = $this->makeRequest();

        $result = $this->controller->unpin($request, new Response(), ['id' => 'abc']);

        $this->assertSame(400, $result->getStatusCode());
    }

    #[Test]
    public function test_unpin權限不足回傳403(): void
    {
        $request = $this->makeRequest(['user_id' => 2]);

        $this->postService->shouldReceive('findById')->once()->andReturn($this->makePost(5, 99));

        $result = $this->controller->unpin($request, new Response(), ['id' => '5']);

        $this->assertSame(403, $result->getStatusCode());
    }

    #[Test]
    public function test_batchDelete未授權回傳401(): void
    {
        $request = $this->makeRequest(['method' => 'DELETE', 'parsed' => ['ids' => [1]]]);

        $result = $this->controller->batchDelete($request, new Response());

        $this->assertSame(401, $result->getStatusCode());
    }

    #[Test]
    public function test_batchDelete權限不足回傳403(): void
    {
        $request = $this->makeRequest(['method' => 'DELETE', 'user_id' => 5, 'parsed' => ['ids' => [1]]]);

        $result = $this->controller->batchDelete($request, new Response());

        $this->assertSame(403, $result->getStatusCode());
    }

    #[Test]
    public function test_batchDelete空ID列表回傳400(): void
    {
        $this->allowManage(1);

        $request = $this->makeRequest(['method' => 'DELETE', 'user_id' => 1, 'parsed' => ['ids' => []]]);

        $result = $this->controller->batchDelete($request, new Response());

        $this->assertSame(400, $result->getStatusCode());
    }

    #[Test]
    public function test_batchDelete部分成功回報失敗清單(): void
    {
        $this->allowManage(1);

        $request = $this->makeRequest([
            'method'  => 'DELETE',
            'user_id' => 1,
            'parsed'  => ['ids' => [1, 'x', 2]],
        ]);

        $this->postService->shouldReceive('deletePost')->once()->with(1)->andReturn(true);
        $this->postService
            ->shouldReceive('deletePost')
            ->once()
            ->with(2)
            ->andThrow(new PostNotFoundException(2));

        $result = $this->controller->batchDelete($request, new Response());

        $this->assertSame(200, $result->getStatusCode());
        $data = self::asArray($this->decode($result)['data']);
        $this->assertSame(1, $data['deleted']);
        $this->assertSame(3, $data['total']);
        $failed = self::asArray($data['failed']);
        $this->assertCount(1, $failed);
        $this->assertSame(2, self::asArray($failed[0])['id']);
    }

    #[Test]
    public function test_batchDelete支援從原始主體解析(): void
    {
        $this->allowManage(1);

        $request = $this->makeRequest([
            'method'  => 'DELETE',
            'user_id' => 1,
            'body'    => '{"ids":[3]}',
        ]);

        $this->postService->shouldReceive('deletePost')->once()->with(3)->andReturn(true);

        $result = $this->controller->batchDelete($request, new Response());

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame(1, self::asArray($this->decode($result)['data'])['deleted']);
    }

    #[Test]
    public function test_exportMarkdown匯出多篇文章(): void
    {
        $request = $this->makeRequest([
            'query' => ['ids' => '1,2,x,0', 'status' => 'published'],
        ]);

        $this->postService
            ->shouldReceive('listPosts')
            ->once()
            ->with(1, 1000, ['ids' => [1, 2], 'status' => 'published'])
            ->andReturn([
                'items'   => [$this->makePost(1), $this->makePost(2, 11, ['author' => null])],
                'total'   => 2,
                'page'    => 1,
                'perPage' => 1000,
            ]);

        $result = $this->controller->exportMarkdown($request, new Response());

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('text/markdown; charset=UTF-8', $result->getHeaderLine('Content-Type'));
        $this->assertSame('attachment; filename="posts_export.md"', $result->getHeaderLine('Content-Disposition'));
        $bodyText = (string) $result->getBody();
        $this->assertStringContainsString('# 測試標題1', $bodyText);
        $this->assertStringContainsString('# 測試標題2', $bodyText);
        $this->assertStringContainsString('---', $bodyText);
    }

    #[Test]
    public function test_exportSingleMarkdown匯出單篇文章(): void
    {
        $request = $this->makeRequest(['attributes' => ['id' => '7']]);

        $this->postService->shouldReceive('findById')->once()->with(7)->andReturn($this->makePost(7));

        $result = $this->controller->exportSingleMarkdown($request, new Response());

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('attachment; filename="post_7.md"', $result->getHeaderLine('Content-Disposition'));
        $this->assertStringContainsString('# 測試標題7', (string) $result->getBody());
    }

    #[Test]
    public function test_exportSingleMarkdown找不到文章回傳404(): void
    {
        $request = $this->makeRequest(['attributes' => ['id' => '7']]);

        $this->postService
            ->shouldReceive('findById')
            ->once()
            ->andThrow(new PostNotFoundException(7));

        $result = $this->controller->exportSingleMarkdown($request, new Response());

        $this->assertSame(404, $result->getStatusCode());
    }

    #[Test]
    public function test_exportSingleMarkdown一般錯誤回傳500(): void
    {
        $request = $this->makeRequest(['attributes' => ['id' => '7']]);

        $this->postService
            ->shouldReceive('findById')
            ->once()
            ->andThrow(new RuntimeException('匯出失敗'));

        $result = $this->controller->exportSingleMarkdown($request, new Response());

        $this->assertSame(500, $result->getStatusCode());
    }
}
