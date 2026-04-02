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
use Psr\Http\Message\ServerRequestInterface;
use Tests\Factory\PostFactory;
use Tests\Factory\UserFactory;
use Tests\Support\IntegrationTestCase;

/**
 * 貼文活動記錄整合測試 - 使用新測試框架示範.
 */
class PostActivityLoggingTest extends IntegrationTestCase
{
    private $postService;

    private $validator;

    private $sanitizer;

    private $activityLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->postService = Mockery::mock(PostService::class);
        $this->validator = new Validator();
        $this->sanitizer = Mockery::mock(OutputSanitizerInterface::class)->shouldIgnoreMissing();
        $this->activityLogger = Mockery::mock(ActivityLoggingServiceInterface::class);
        $this->activityLogger->shouldReceive('logSuccess')->zeroOrMoreTimes();
        $this->activityLogger->shouldReceive('logFailure')->zeroOrMoreTimes();
    }

    public function test_it_logs_successful_post_creation(): void
    {
        // 1. 準備資料
        $user = UserFactory::create(['id' => 1]);
        $postData = [
            'title' => 'New Post Title',
            'content' => 'This is a valid content',
            'status' => 'draft',
            'is_pinned' => false,
        ];

        // 2. 建立 Mock 請求
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getMethod')->andReturn('POST')->byDefault();
        $request->shouldReceive('getUri->getPath')->andReturn('/api/posts')->byDefault();
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn($user['id'])->byDefault();
        $request->shouldReceive('getParsedBody')->andReturn($postData)->byDefault();
        $request->shouldReceive('getHeaderLine')->andReturn('')->zeroOrMoreTimes();
        $request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1'])->byDefault();

        // 3. 設定 Mock 期待 (回傳真實 Post 物件)
        $post = new Post(PostFactory::make(['title' => 'New Post Title', 'id' => 999]));
        $this->postService->shouldReceive('createPost')->once()->andReturn($post);

        $this->activityLogger->shouldReceive('logSuccess')->once();

        // 4. 執行
        $authService = $this->mockAuthorizationService();

        $controller = new PostController(
            $this->postService,
            $this->validator,
            $this->sanitizer,
            $this->activityLogger,
            Mockery::mock(PostViewStatisticsService::class),
            $authService,
        );

        $response = $controller->store($request, new Response());

        // 5. 語義化斷言
        $this->assertResponseStatus($response, 201);
        $this->assertJsonResponseMatches($response, [
            'success' => true,
        ]);
    }
}
