<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Application\Controllers\Api\V1\PostController;
use App\Application\Middleware\AuthorizationResult;
use App\Domains\Auth\Contracts\AuthorizationServiceInterface;
use App\Domains\Post\Models\Post;
use App\Domains\Post\Services\PostService;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Statistics\Services\PostViewStatisticsService;
use App\Infrastructure\Http\Response;
use App\Shared\Contracts\OutputSanitizerInterface;
use App\Shared\Validation\Validator;
use Mockery;
use Tests\Factory\PostFactory;
use Tests\Factory\UserFactory;
use Tests\Support\IntegrationTestCase;

/**
 * 貼文控制器整合測試 - 使用新測試框架重構.
 */
class PostControllerTest extends IntegrationTestCase
{
    private $postService;

    private $validator;

    private $sanitizer;

    private $activityLogger;

    private $statsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->postService = Mockery::mock(PostService::class);
        $this->validator = new Validator();
        $this->sanitizer = Mockery::mock(OutputSanitizerInterface::class);
        $this->sanitizer->shouldReceive('sanitizeHtml')->andReturnUsing(fn($i) => $i)->zeroOrMoreTimes();
        $this->sanitizer->shouldReceive('sanitizeRichText')->andReturnUsing(fn($i) => $i)->zeroOrMoreTimes();

        $this->activityLogger = Mockery::mock(ActivityLoggingServiceInterface::class)->shouldIgnoreMissing();
        $this->statsService = Mockery::mock(PostViewStatisticsService::class)->shouldIgnoreMissing();
    }

    public function test_index_returns_paginated_posts(): void
    {
        // 1. 準備請求
        $request = $this->createRequest('GET', '/api/posts');

        // 2. Mock 核心業務邏輯 (實作呼叫的是 listPosts)
        $this->postService->shouldReceive('listPosts')
            ->once()
            ->andReturn([
                'items' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => 15,
            ]);

        // 3. 執行
        $authService = $this->mockAuthorizationService();

        $this->controller = new PostController(
            $this->postService,
            $this->validator,
            $this->sanitizer,
            $this->activityLogger,
            $this->statsService,
            $authService,
        );

        $response = $this->controller->index($request, new Response());

        // 4. 語義化斷言
        $this->assertResponseStatus($response, 200);
        $this->assertJsonResponseMatches($response, [
            'success' => true,
            'pagination' => [
                'total' => 0,
            ],
        ]);
    }

    public function test_store_creates_new_post(): void
    {
        $user = UserFactory::create(['id' => 1]);
        $postData = [
            'title' => '重構標題',
            'content' => '重構內容',
            'status' => 'published',
            'is_pinned' => true,
        ];

        $request = $this->createRequest('POST', '/api/posts');
        $request = $request->withAttribute('user_id', $user['id']);
        $request = $this->withJsonBody($request, $postData);

        $post = new Post(PostFactory::make(['id' => 123, 'title' => '重構標題']));
        $this->postService->shouldReceive('createPost')->once()->andReturn($post);

        $authService = Mockery::mock(AuthorizationServiceInterface::class);
        $authService->shouldReceive('authorize')->andReturn(new AuthorizationResult(true, 'Allowed', 'SUCCESS'))->zeroOrMoreTimes();

        $this->controller = new PostController(
            $this->postService,
            $this->validator,
            $this->sanitizer,
            $this->activityLogger,
            $this->statsService,
            $authService,
        );

        $response = $this->controller->store($request, new Response());

        $this->assertResponseStatus($response, 201);
        $this->assertJsonResponseMatches($response, [
            'success' => true,
            'data' => [
                'id' => 123,
            ],
        ]);
    }
}
