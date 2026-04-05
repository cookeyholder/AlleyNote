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
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ApiTestCase;

class PostControllerTest extends ApiTestCase
{
    private PostServiceInterface|MockInterface $postService;

    private ValidatorInterface|MockInterface $validator;

    private OutputSanitizerInterface|MockInterface $sanitizer;

    /** @var mixed */
    private $activityLogger;

    private PostViewStatisticsService|MockInterface $postViewStatsService;

    private AuthorizationServiceInterface|MockInterface $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postService = Mockery::mock(PostServiceInterface::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);
        $this->sanitizer = $this->mockOutputSanitizer();
        $this->activityLogger = Mockery::mock(ActivityLoggingServiceInterface::class)->shouldIgnoreMissing();
        $this->postViewStatsService = Mockery::mock(PostViewStatisticsService::class);
        $this->authService = $this->mockAuthorizationService();

        $this->validator->shouldReceive('addRule')->zeroOrMoreTimes()->andReturnSelf();
        $this->validator->shouldReceive('addMessage')->zeroOrMoreTimes()->andReturnSelf();
        $this->validator->shouldReceive('validateOrFail')->zeroOrMoreTimes()->andReturnUsing(
            static fn(array $input): array => $input,
        );
    }

    private function controller(): PostController
    {
        /** @var PostServiceInterface $postService */
        $postService = $this->postService;
        /** @var ValidatorInterface $validator */
        $validator = $this->validator;
        /** @var OutputSanitizerInterface $sanitizer */
        $sanitizer = $this->sanitizer;
        /** @var ActivityLoggingServiceInterface $activityLogger */
        $activityLogger = $this->activityLogger;
        /** @var PostViewStatisticsService $postViewStatsService */
        $postViewStatsService = $this->postViewStatsService;
        /** @var AuthorizationServiceInterface $authService */
        $authService = $this->authService;

        return new PostController(
            $postService,
            $validator,
            $sanitizer,
            $activityLogger,
            $postViewStatsService,
            $authService,
        );
    }

    #[Test]
    public function indexShouldReturnPaginatedPosts(): void
    {
        $request = $this
            ->actingAs(['id' => 1, 'email' => 'post-index@example.com'])
            ->json('GET', '/api/posts', ['page' => 1, 'limit' => 10])
            ->withAttribute('user_id', 1);

        $this->postService->shouldReceive('listPosts')->once()->andReturn([
            'items' => [],
            'total' => 0,
            'page' => 1,
            'per_page' => 10,
        ]);
        $this->postViewStatsService->shouldReceive('getBatchPostViewStats')->once()->andReturn([]);

        $response = $this->controller()->index($request, $this->createApiResponse());
        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function showShouldReturn404WhenNotFound(): void
    {
        $request = $this
            ->actingAs(['id' => 1, 'email' => 'post-show-not-found@example.com'])
            ->json('GET', '/api/posts/999')
            ->withAttribute('user_id', 1);

        $this->postService
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andThrow(new PostNotFoundException(999, 'Post not found'));

        $this->postService->shouldReceive('recordView')->never();
        $this->postViewStatsService->shouldReceive('getPostViewStats')->never();

        $response = $this->controller()->show($request, $this->createApiResponse(), ['id' => '999']);
        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function showShouldReturnPostDetails(): void
    {
        $request = $this
            ->actingAs(['id' => 1, 'email' => 'post-show@example.com'])
            ->json('GET', '/api/posts/1')
            ->withAttribute('user_id', 1);

        $post = new Post([
            'id' => 1,
            'title' => '測試文章',
            'content' => '內容',
            'user_id' => 1,
            'status' => 'published',
        ]);

        $this->postService->shouldReceive('findById')->once()->with(1)->andReturn($post);
        $this->postService->shouldReceive('recordView')->once()->with(1, Mockery::any());
        $this->postViewStatsService->shouldReceive('getPostViewStats')->once()->andReturn([
            'views' => 10,
            'unique_visitors' => 5,
        ]);

        $response = $this->controller()->show($request, $this->createApiResponse(), ['id' => '1']);
        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function storeShouldCreatePost(): void
    {
        $request = $this
            ->actingAs(['id' => 1, 'email' => 'post-store@example.com'])
            ->json('POST', '/api/posts', [
                'title' => '新文章',
                'content' => '內容',
                'status' => 'published',
            ])
            ->withAttribute('user_id', 1);

        $createdPost = new Post([
            'id' => 1,
            'title' => '新文章',
            'content' => '內容',
            'user_id' => 1,
            'status' => 'published',
        ]);

        $this->postService->shouldReceive('createPost')->once()->andReturn($createdPost);
        $this->postService->shouldReceive('setTags')->zeroOrMoreTimes();

        $response = $this->controller()->store($request, $this->createApiResponse());
        $this->assertSame(201, $response->getStatusCode());
    }
}
