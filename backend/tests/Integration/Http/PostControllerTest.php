<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Application\Controllers\Api\V1\PostController;
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
use Tests\Factory\PostFactory;
use Tests\Support\IntegrationTestCase;

class PostControllerTest extends IntegrationTestCase
{
    private PostServiceInterface&MockInterface $postService;

    private ValidatorInterface&MockInterface $validator;

    private OutputSanitizerInterface&MockInterface $sanitizer;

    private ActivityLoggingServiceInterface&MockInterface $activityLogger;

    private PostViewStatisticsService&MockInterface $postViewStatsService;

    private ServerRequestInterface&MockInterface $request;

    private ResponseInterface&MockInterface $response;

    private StreamInterface&MockInterface $stream;

    /** @var array<string, mixed>|null */
    private ?array $currentResponseData = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postService = Mockery::mock(PostServiceInterface::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);
        /** @var OutputSanitizerInterface&MockInterface $sanitizer */
        $sanitizer = $this->mockOutputSanitizer();
        $this->sanitizer = $sanitizer;
        $this->activityLogger = Mockery::mock(ActivityLoggingServiceInterface::class);
        $this->postViewStatsService = Mockery::mock(PostViewStatisticsService::class);

        $this->activityLogger->shouldReceive('logSuccess')->zeroOrMoreTimes();
        $this->activityLogger->shouldReceive('logFailure')->zeroOrMoreTimes();
        $this->activityLogger->shouldReceive('log')->zeroOrMoreTimes();

        // 徹底 Mock Validator 的流式接口
        $this->validator->shouldReceive('addRule')->andReturnSelf()->zeroOrMoreTimes();
        $this->validator->shouldReceive('addMessage')->andReturnSelf()->zeroOrMoreTimes();
        $this->validator->shouldReceive('validateOrFail')->andReturnUsing(fn($data) => $data)->byDefault();

        $this->stream = $this->createLocalStreamMock();
        $this->response = $this->createLocalResponseMock();
        $this->request = $this->createLocalRequestMock();
    }

    #[Test]
    public function indexShouldReturnPaginatedPosts(): void
    {
        $this->request->shouldReceive('getQueryParams')->once()->andReturn(['page' => 1]);
        $this->postService->shouldReceive('listPosts')->once()->andReturn([
            'items' => [], 'total' => 0, 'page' => 1, 'per_page' => 10,
        ]);
        $this->postViewStatsService->shouldReceive('getBatchPostViewStats')->once()->andReturn([]);

        $authServiceOrig = $this->mockAuthorizationService();
        /** @var \App\Domains\Auth\Contracts\AuthorizationServiceInterface $authService */
        $authService = $authServiceOrig;
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1'])->zeroOrMoreTimes();
        $this->request->shouldReceive('getAttribute')->with('user_id')->andReturn(1)->zeroOrMoreTimes();

        /** @var \App\Application\Controllers\Api\V1\PostController $controller */
        $controller = new PostController($this->postService, $this->validator, $this->sanitizer, $this->activityLogger, $this->postViewStatsService, $authService);
        $response = $controller->index($this->request, $this->response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSafeApiResponse($this->currentResponseData);
    }

    #[Test]
    public function showShouldReturnPostDetails(): void
    {
        $postId = 1;
        $post = new Post(PostFactory::make(['id' => $postId, 'title' => 'Test Title']));

        $this->postService->shouldReceive('findById')->once()->with($postId)->andReturn($post);
        $this->postService->shouldReceive('recordView')->once();
        $this->postViewStatsService->shouldReceive('getPostViewStats')->once()->andReturn(['views' => 10, 'unique_visitors' => 5]);

        $authServiceOrig = $this->mockAuthorizationService();
        /** @var \App\Domains\Auth\Contracts\AuthorizationServiceInterface $authService */
        $authService = $authServiceOrig;
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1'])->zeroOrMoreTimes();
        $this->request->shouldReceive('getAttribute')->with('user_id')->andReturn(1)->zeroOrMoreTimes();

        /** @var \App\Application\Controllers\Api\V1\PostController $controller */
        $controller = new PostController($this->postService, $this->validator, $this->sanitizer, $this->activityLogger, $this->postViewStatsService, $authService);
        $response = $controller->show($this->request, $this->response, ['id' => (string) $postId]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertResponseTitleEquals('Test Title');
    }

    #[Test]
    public function showShouldReturn404WhenNotFound(): void
    {
        // 修正例外建立方式
        $this->postService->shouldReceive('findById')->once()->andThrow(new PostNotFoundException(999));

        $authServiceOrig = $this->mockAuthorizationService();
        /** @var \App\Domains\Auth\Contracts\AuthorizationServiceInterface $authService */
        $authService = $authServiceOrig;
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1'])->zeroOrMoreTimes();
        $this->request->shouldReceive('getAttribute')->with('user_id')->andReturn(1)->zeroOrMoreTimes();

        /** @var \App\Application\Controllers\Api\V1\PostController $controller */
        $controller = new PostController($this->postService, $this->validator, $this->sanitizer, $this->activityLogger, $this->postViewStatsService, $authService);
        $response = $controller->show($this->request, $this->response, ['id' => '999']);

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function storeShouldCreateNewPost(): void
    {
        $postData = ['title' => 'New Post', 'content' => 'Content'];
        $this->request->shouldReceive('getParsedBody')->andReturn($postData);

        $createdPost = new Post(array_merge($postData, ['id' => 1, 'uuid' => 'uuid', 'seq_number' => 1]));
        $this->postService->shouldReceive('createPost')->once()->andReturn($createdPost);

        $authServiceOrig = $this->mockAuthorizationService();
        /** @var \App\Domains\Auth\Contracts\AuthorizationServiceInterface $authService */
        $authService = $authServiceOrig;
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1'])->zeroOrMoreTimes();
        $this->request->shouldReceive('getAttribute')->with('user_id')->andReturn(1)->zeroOrMoreTimes();

        /** @var \App\Application\Controllers\Api\V1\PostController $controller */
        $controller = new PostController($this->postService, $this->validator, $this->sanitizer, $this->activityLogger, $this->postViewStatsService, $authService);
        $response = $controller->store($this->request, $this->response);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertResponseTitleEquals('New Post');
    }

    #[Test]
    public function storeShouldReturn400OnValidationFailure(): void
    {
        $this->request->shouldReceive('getParsedBody')->andReturn(['title' => '']);
        // 確保 createPost 拋出 ValidationException
        $this->postService->shouldReceive('createPost')->once()->andThrow(new ValidationException(new ValidationResult(false, ['title' => ['Required']])));

        $authServiceOrig = $this->mockAuthorizationService();
        /** @var \App\Domains\Auth\Contracts\AuthorizationServiceInterface $authService */
        $authService = $authServiceOrig;
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1'])->zeroOrMoreTimes();
        $this->request->shouldReceive('getAttribute')->with('user_id')->andReturn(1)->zeroOrMoreTimes();

        /** @var \App\Application\Controllers\Api\V1\PostController $controller */
        $controller = new PostController($this->postService, $this->validator, $this->sanitizer, $this->activityLogger, $this->postViewStatsService, $authService);
        $response = $controller->store($this->request, $this->response);

        $this->assertEquals(400, $response->getStatusCode());
    }

    #[Test]
    public function deleteShouldRemovePost(): void
    {
        $postId = 1;
        $post = new Post(['id' => $postId, 'title' => 'To Delete', 'status' => 'published']);
        $this->postService->shouldReceive('findById')->once()->with($postId)->andReturn($post);
        $this->postService->shouldReceive('deletePost')->once()->with($postId);

        $authServiceOrig = $this->mockAuthorizationService();
        /** @var \App\Domains\Auth\Contracts\AuthorizationServiceInterface $authService */
        $authService = $authServiceOrig;
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1'])->zeroOrMoreTimes();
        $this->request->shouldReceive('getAttribute')->with('user_id')->andReturn(1)->zeroOrMoreTimes();

        /** @var \App\Application\Controllers\Api\V1\PostController $controller */
        $controller = new PostController($this->postService, $this->validator, $this->sanitizer, $this->activityLogger, $this->postViewStatsService, $authService);
        $response = $controller->delete($this->request, $this->response, ['id' => (string) $postId]);

        $this->assertEquals(204, $response->getStatusCode());
    }

    protected function createLocalRequestMock(): ServerRequestInterface&MockInterface
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1'])->byDefault();
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn(1)->byDefault();
        $request->shouldReceive('getBody')->andReturn($this->stream)->byDefault();
        $request->shouldReceive('getHeaderLine')->andReturn('')->zeroOrMoreTimes();

        return $request;
    }

    protected function createLocalStreamMock(): StreamInterface&MockInterface
    {
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('write')->andReturnUsing(function ($content) {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode((string) $content, true, 512, JSON_THROW_ON_ERROR);
            self::assertIsArray($decoded);
            $this->currentResponseData = $decoded;

            return strlen((string) $content);
        });

        return $stream;
    }

    protected function createLocalResponseMock(): ResponseInterface&MockInterface
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('withHeader')->andReturnSelf();
        $response->shouldReceive('withStatus')->andReturnUsing(function ($status) use ($response) {
            $response->shouldReceive('getStatusCode')->andReturn($status);

            return $response;
        });
        $response->shouldReceive('getBody')->andReturn($this->stream);

        return $response;
    }

    private function assertResponseTitleEquals(string $expectedTitle): void
    {
        self::assertIsArray($this->currentResponseData);
        self::assertArrayHasKey('data', $this->currentResponseData);
        self::assertIsArray($this->currentResponseData['data']);
        self::assertArrayHasKey('title', $this->currentResponseData['data']);
        self::assertSame($expectedTitle, $this->currentResponseData['data']['title']);
    }
}
