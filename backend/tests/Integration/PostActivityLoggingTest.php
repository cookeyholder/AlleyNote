<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Application\Controllers\Api\V1\PostController;
use App\Domains\Post\Contracts\PostServiceInterface;
use App\Domains\Post\Models\Post;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Enums\ActivityType;
use App\Shared\Contracts\OutputSanitizerInterface;
use App\Shared\Contracts\ValidatorInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\TestCase;

/**
 * PostController 與 ActivityLoggingService 整合測試.
 */
class PostActivityLoggingTest extends TestCase
{
    private PostServiceInterface|MockInterface $postService;

    private ValidatorInterface|MockInterface $validator;

    private OutputSanitizerInterface|MockInterface $sanitizer;

    private ActivityLoggingServiceInterface|MockInterface $activityLogger;

    private ServerRequestInterface|MockInterface $request;

    private ResponseInterface|MockInterface $response;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postService = Mockery::mock(PostServiceInterface::class);
        $this->activityLogger = Mockery::mock(ActivityLoggingServiceInterface::class);
        $this->activityLogger->shouldReceive('log')->byDefault()->andReturn(true);
        $this->validator = Mockery::mock(ValidatorInterface::class);
        $this->sanitizer = Mockery::mock(OutputSanitizerInterface::class);

        $this->sanitizer->shouldReceive('sanitizeHtml')
            ->andReturnUsing(fn ($input) => $input)
            ->byDefault();

        // 初始化 request 和 response mocks
        $this->request = Mockery::mock(ServerRequestInterface::class);
        $this->response = Mockery::mock(ResponseInterface::class);

        // 設定基本的 request mock 行為
        $this->request->shouldReceive('getHeaderLine')
            ->byDefault()
            ->andReturn('');
        $this->request->shouldReceive('getServerParams')
            ->byDefault()
            ->andReturn(['REMOTE_ADDR' => '127.0.0.1']);
        $this->request->shouldReceive('getAttribute')
            ->with('user_id')
            ->byDefault()
            ->andReturn(1);

        // 設定基本的 response mock 行為
        $this->response->shouldReceive('withStatus')
            ->andReturnSelf()
            ->byDefault();
        $this->response->shouldReceive('withHeader')
            ->andReturnSelf()
            ->byDefault();

        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('write')->andReturnSelf()->byDefault();
        $stream->shouldReceive('__toString')->andReturn('{"success": true}')->byDefault();
        $this->response->shouldReceive('getBody')->andReturn($stream)->byDefault();
    }    #[Test]
    public function it_logs_successful_post_creation(): void
    {
        // Arrange
        $postData = [
            'title' => 'Test Post',
            'content' => 'Test Content',
            'user_id' => 1,
        ];

        $post = new Post([
            'id' => 123,
            'title' => 'Test Post',
            'content' => 'Test Content',
            'status' => 'draft',
        ]);

        $requestBody = json_encode($postData);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn($requestBody);
        $this->request->shouldReceive('getBody')->andReturn($stream);

        $responseStream = Mockery::mock(StreamInterface::class);
        $responseStream->shouldReceive('write')->andReturn(1);
        $this->response->shouldReceive('getBody')->andReturn($responseStream);
        $this->response->shouldReceive('withHeader')->andReturnSelf();
        $this->response->shouldReceive('withStatus')->andReturnSelf();

        $this->postService->shouldReceive('createPost')
            ->once()
            ->andReturn($post);

        $this->sanitizer->shouldReceive('sanitize')->andReturn([]);

        // 允許任何對 logFailure 的呼叫（可能來自驗證錯誤等）
        $this->activityLogger->shouldReceive('logFailure')
            ->zeroOrMoreTimes()
            ->andReturn(false);

        // 重點測試：確保記錄活動
        $this->activityLogger->shouldReceive('logSuccess')
            ->once()
            ->with(
                ActivityType::POST_CREATED,
                1, // user_id
                'post', // target_type
                '123', // target_id
                Mockery::on(function ($metadata) {
                    return isset($metadata['title'])
                        && $metadata['title'] === 'Test Post'
                        && isset($metadata['ip_address']);
                }),
            )
            ->andReturn(true);

        // Act
        $controller = new PostController(
            $this->postService,
            $this->validator,
            $this->sanitizer,
            $this->activityLogger,
        );

        $response = $controller->store($this->request, $this->response);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    #[Test]
    public function it_logs_failed_post_creation_on_json_error(): void
    {
        // Arrange
        $invalidJson = '{invalid json}';
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn($invalidJson);
        $this->request->shouldReceive('getBody')->andReturn($stream);

        $responseStream = Mockery::mock(StreamInterface::class);
        $responseStream->shouldReceive('write')->andReturn(1);
        $this->response->shouldReceive('getBody')->andReturn($responseStream);
        $this->response->shouldReceive('withHeader')->andReturnSelf();
        $this->response->shouldReceive('withStatus')->andReturnSelf();

        // 重點測試：確保記錄失敗活動
        $this->activityLogger->shouldReceive('logFailure')
            ->once()
            ->with(
                ActivityType::POST_CREATED,
                1, // user_id
                'Invalid JSON format', // reason
                Mockery::on(function ($metadata) {
                    return isset($metadata['ip_address']);
                }),
            )
            ->andReturn(true);

        // Act
        $controller = new PostController(
            $this->postService,
            $this->validator,
            $this->sanitizer,
            $this->activityLogger,
        );

        $response = $controller->store($this->request, $this->response);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    #[Test]
    public function it_logs_successful_post_view(): void
    {
        // Arrange
        $postId = 123;
        $args = ['id' => (string) $postId];

        $post = new Post([
            'id' => $postId,
            'title' => 'Test Post',
            'content' => 'Test Content',
            'status' => 'published',
        ]);

        $this->postService->shouldReceive('findById')
            ->with($postId)
            ->once()
            ->andReturn($post);

        $this->postService->shouldReceive('recordView')
            ->once();

        $this->sanitizer->shouldReceive('sanitize')->andReturn([]);

        $responseStream = Mockery::mock(StreamInterface::class);
        $responseStream->shouldReceive('write')->andReturn(1);
        $this->response->shouldReceive('getBody')->andReturn($responseStream);
        $this->response->shouldReceive('withHeader')->andReturnSelf();
        $this->response->shouldReceive('withStatus')->andReturnSelf();

        // 允許任何對 logFailure 的呼叫
        $this->activityLogger->shouldReceive('logFailure')
            ->zeroOrMoreTimes()
            ->andReturn(false);

        // 重點測試：確保記錄查看活動
        $this->activityLogger->shouldReceive('logSuccess')
            ->once()
            ->with(
                ActivityType::POST_VIEWED,
                1, // user_id
                'post', // target_type
                '123', // target_id
                Mockery::on(function ($metadata) {
                    return isset($metadata['title'])
                        && $metadata['title'] === 'Test Post'
                        && isset($metadata['ip_address']);
                }),
            )
            ->andReturn(true);

        // Act
        $controller = new PostController(
            $this->postService,
            $this->validator,
            $this->sanitizer,
            $this->activityLogger,
        );

        $response = $controller->show($this->request, $this->response, $args);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    #[Test]
    public function it_logs_pin_toggle_activity(): void
    {
        // Arrange
        $postId = 123;
        $args = ['id' => (string) $postId];
        $requestData = ['pinned' => true];

        $post = new Post([
            'id' => $postId,
            'title' => 'Test Post',
            'content' => 'Test Content',
            'status' => 'published',
        ]);

        $requestBody = json_encode($requestData);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn($requestBody);
        $this->request->shouldReceive('getBody')->andReturn($stream);

        $this->postService->shouldReceive('setPinned')
            ->with($postId, true)
            ->once();

        $this->postService->shouldReceive('findById')
            ->with($postId)
            ->once()
            ->andReturn($post);

        $this->sanitizer->shouldReceive('sanitize')->andReturn([]);

        $responseStream = Mockery::mock(StreamInterface::class);
        $responseStream->shouldReceive('write')->andReturn(1);
        $this->response->shouldReceive('getBody')->andReturn($responseStream);
        $this->response->shouldReceive('withHeader')->andReturnSelf();
        $this->response->shouldReceive('withStatus')->andReturnSelf();

        // 允許任何對 logFailure 的呼叫
        $this->activityLogger->shouldReceive('logFailure')
            ->zeroOrMoreTimes()
            ->andReturn(false);

        // 重點測試：確保記錄置頂活動
        $this->activityLogger->shouldReceive('logSuccess')
            ->once()
            ->with(
                ActivityType::POST_PINNED, // 預期為 POST_PINNED
                1, // user_id
                'post', // target_type
                '123', // target_id
                Mockery::on(function ($metadata) {
                    return isset($metadata['title'])
                        && $metadata['title'] === 'Test Post'
                        && $metadata['pinned'] === true
                        && isset($metadata['ip_address']);
                }),
            )
            ->andReturn(true);

        // Act
        $controller = new PostController(
            $this->postService,
            $this->validator,
            $this->sanitizer,
            $this->activityLogger,
        );

        $response = $controller->togglePin($this->request, $this->response, $args);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
