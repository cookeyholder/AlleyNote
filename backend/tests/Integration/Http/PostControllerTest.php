<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Application\Controllers\Api\V1\PostController;
use App\Domains\Auth\Contracts\AuthorizationServiceInterface;
use App\Domains\Post\Contracts\PostServiceInterface;
use App\Domains\Post\Exceptions\PostNotFoundException;
use App\Domains\Post\Models\Post;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Statistics\Services\PostViewStatisticsService;
use App\Shared\Contracts\OutputSanitizerInterface;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\ValidationResult;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\Support\IntegrationTestCase;

class PostControllerTest extends IntegrationTestCase
{
    private PostServiceInterface|MockInterface $postService;

    private ValidatorInterface|MockInterface $validator;

    private OutputSanitizerInterface|MockInterface $sanitizer;

    private ActivityLoggingServiceInterface|MockInterface $activityLogger;

    private PostViewStatisticsService|MockInterface $postViewStatsService;

    private ServerRequestInterface|MockInterface $request;

    private ResponseInterface|MockInterface $response;

    private StreamInterface|MockInterface $stream;

    /** @var array<mixed, mixed>|null */
    private ?array $currentResponseData = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postService = Mockery::mock(PostServiceInterface::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);
        $this->sanitizer = Mockery::mock(OutputSanitizerInterface::class);
        $this->activityLogger = Mockery::mock(ActivityLoggingServiceInterface::class)->shouldIgnoreMissing();
        $this->postViewStatsService = Mockery::mock(PostViewStatisticsService::class);

        $this->request = Mockery::mock(ServerRequestInterface::class);
        $this->response = Mockery::mock(ResponseInterface::class);
        $this->stream = Mockery::mock(StreamInterface::class);

        // 預設行為
        $this->request->shouldReceive('getQueryParams')->andReturn([])->byDefault();
        $this->request->shouldReceive('getParsedBody')->andReturn([])->byDefault();
        $this->request->shouldReceive('getHeaderLine')->andReturn('')->byDefault();
        $this->request->shouldReceive('getAttribute')->andReturn(null)->byDefault();

        // 模擬 Response 的鏈式調用與內容捕捉
        $this->response->shouldReceive('withHeader')->andReturnSelf()->byDefault();
        $this->response->shouldReceive('withStatus')->andReturnSelf()->byDefault();
        $this->response->shouldReceive('getStatusCode')->andReturn(200)->byDefault();
        $this->response->shouldReceive('getBody')->andReturn($this->stream)->byDefault();
        $this->stream->shouldReceive('write')->andReturnUsing(function ($content) {
            $decoded = json_decode((string) $content, true);
            $this->currentResponseData = is_array($decoded) ? $decoded : null;

            return strlen((string) $content);
        });
    }

    #[Test]
    public function indexShouldReturnPaginatedPosts(): void
    {
        $this->postService->shouldReceive('listPosts')->once()->andReturn([
            'items' => [], 'total' => 0, 'page' => 1, 'per_page' => 15,
        ]);
        $this->postViewStatsService->shouldReceive('getBatchPostViewStats')->once()->andReturn([]);

        $authServiceOrig = $this->mockAuthorizationService();
        /** @var AuthorizationServiceInterface $authService */
        $authService = $authServiceOrig;
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1'])->zeroOrMoreTimes();
        $this->request->shouldReceive('getAttribute')->with('user_id')->andReturn(1)->zeroOrMoreTimes();

        $controller = new PostController($this->postService, $this->validator, $this->sanitizer, $this->activityLogger, $this->postViewStatsService, $authService);
        $response = $controller->index($this->request, $this->response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSafeApiResponse($this->currentResponseData);
    }

    #[Test]
    public function showShouldReturnPostDetails(): void
    {
        $postId = 1;
        $post = new Post([
            'id' => $postId,
            'title' => '測試文章',
            'content' => '內容',
            'user_id' => 1,
            'status' => 'published',
        ]);

        $this->postService->shouldReceive('findById')->once()->with($postId)->andReturn($post);
        $this->postService->shouldReceive('recordView')->once();
        $this->postViewStatsService->shouldReceive('getPostViewStats')->once()->andReturn(['views' => 10, 'unique_visitors' => 5]);

        $authServiceOrig = $this->mockAuthorizationService();
        /** @var AuthorizationServiceInterface $authService */
        $authService = $authServiceOrig;
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1'])->zeroOrMoreTimes();
        $this->request->shouldReceive('getAttribute')->with('user_id')->andReturn(1)->zeroOrMoreTimes();

        $controller = new PostController($this->postService, $this->validator, $this->sanitizer, $this->activityLogger, $this->postViewStatsService, $authService);
        $response = $controller->show($this->request, $this->response, ['id' => (string) $postId]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSafeApiResponse($this->currentResponseData);
    }

    #[Test]
    public function showShouldReturn404WhenNotFound(): void
    {
        $this->postService->shouldReceive('findById')->once()->andThrow(new PostNotFoundException(999));
        $this->response->shouldReceive('withStatus')->with(404)->andReturnSelf();
        $this->response->shouldReceive('getStatusCode')->andReturn(404);

        $authServiceOrig = $this->mockAuthorizationService();
        /** @var AuthorizationServiceInterface $authService */
        $authService = $authServiceOrig;
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1'])->zeroOrMoreTimes();
        $this->request->shouldReceive('getAttribute')->with('user_id')->andReturn(1)->zeroOrMoreTimes();

        $controller = new PostController($this->postService, $this->validator, $this->sanitizer, $this->activityLogger, $this->postViewStatsService, $authService);
        $response = $controller->show($this->request, $this->response, ['id' => '999']);

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function storeShouldCreateNewPost(): void
    {
        $createdPost = new Post(['id' => 1, 'title' => '新文章', 'user_id' => 1]);
        $this->validator->shouldReceive('validateOrFail')->andReturn(['title' => '新文章']);
        $this->postService->shouldReceive('createPost')->once()->andReturn($createdPost);
        $this->response->shouldReceive('withStatus')->with(201)->andReturnSelf();
        $this->response->shouldReceive('getStatusCode')->andReturn(201);

        $authServiceOrig = $this->mockAuthorizationService();
        /** @var AuthorizationServiceInterface $authService */
        $authService = $authServiceOrig;
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1'])->zeroOrMoreTimes();
        $this->request->shouldReceive('getAttribute')->with('user_id')->andReturn(1)->zeroOrMoreTimes();

        $controller = new PostController($this->postService, $this->validator, $this->sanitizer, $this->activityLogger, $this->postViewStatsService, $authService);
        $response = $controller->store($this->request, $this->response);

        $this->assertEquals(201, $response->getStatusCode());
    }

    #[Test]
    public function storeShouldReturn400OnValidationFailure(): void
    {
        $this->postService->shouldReceive('createPost')->once()->andThrow(new ValidationException(new ValidationResult(false, ['title' => ['Required']])));
        $this->response->shouldReceive('withStatus')->with(400)->andReturnSelf();
        $this->response->shouldReceive('getStatusCode')->andReturn(400);

        $authServiceOrig = $this->mockAuthorizationService();
        /** @var AuthorizationServiceInterface $authService */
        $authService = $authServiceOrig;
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1'])->zeroOrMoreTimes();
        $this->request->shouldReceive('getAttribute')->with('user_id')->andReturn(1)->zeroOrMoreTimes();

        $controller = new PostController($this->postService, $this->validator, $this->sanitizer, $this->activityLogger, $this->postViewStatsService, $authService);
        $response = $controller->store($this->request, $this->response);

        $this->assertEquals(400, $response->getStatusCode());
    }

    #[Test]
    public function deleteShouldRemovePost(): void
    {
        $postId = 1;
        $post = new Post(['id' => $postId, 'user_id' => 1]);
        $this->postService->shouldReceive('findById')->once()->with($postId)->andReturn($post);
        $this->postService->shouldReceive('deletePost')->once()->with($postId);
        $this->response->shouldReceive('withStatus')->with(204)->andReturnSelf();
        $this->response->shouldReceive('getStatusCode')->andReturn(204);

        $authServiceOrig = $this->mockAuthorizationService();
        /** @var AuthorizationServiceInterface $authService */
        $authService = $authServiceOrig;
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1'])->zeroOrMoreTimes();
        $this->request->shouldReceive('getAttribute')->with('user_id')->andReturn(1)->zeroOrMoreTimes();

        $controller = new PostController($this->postService, $this->validator, $this->sanitizer, $this->activityLogger, $this->postViewStatsService, $authService);
        $response = $controller->delete($this->request, $this->response, ['id' => (string) $postId]);

        $this->assertEquals(204, $response->getStatusCode());
    }
}
