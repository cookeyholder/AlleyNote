<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Api\V1;

use App\Application\Controllers\Api\V1\PostViewController;
use App\Domains\Post\Contracts\PostServiceInterface;
use App\Domains\Post\Exceptions\PostNotFoundException;
use App\Domains\Post\Models\Post;
use App\Domains\Statistics\Events\PostViewed;
use App\Shared\Events\Contracts\EventDispatcherInterface;
use Exception;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * PostViewController 單元測試.
 */
class PostViewControllerTest extends TestCase
{
    private PostViewController $controller;

    /** @var PostServiceInterface&MockInterface */
    private $postService;

    /** @var EventDispatcherInterface&MockInterface */
    private $eventDispatcher;

    /** @var ServerRequestInterface&MockInterface */
    private $request;

    /** @var ResponseInterface&MockInterface */
    private $response;

    /** @var StreamInterface&MockInterface */
    private $responseBody;

    protected function setUp(): void
    {
        parent::setUp();

        $this->postService = Mockery::mock(PostServiceInterface::class);
        $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->controller = new PostViewController(
            $this->postService,
            $this->eventDispatcher,
        );

        // Mock PSR-7 objects
        $this->request = Mockery::mock(ServerRequestInterface::class);
        $this->response = Mockery::mock(ResponseInterface::class);
        $this->responseBody = Mockery::mock(StreamInterface::class);

        // 設定 response 的基本行為
        $this->response->shouldReceive('getBody')->andReturn($this->responseBody);
        $this->response->shouldReceive('withHeader')->andReturnSelf();
        $this->response->shouldReceive('withStatus')->andReturnSelf();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testRecordViewWithValidAuthenticatedUser(): void
    {
        // Arrange
        $postId = 123;
        $userId = 456;
        $userIp = '192.168.1.100';
        $userAgent = 'Mozilla/5.0 (Test Browser)';

        $this->setupValidRequest($postId, $userId, $userIp, $userAgent);
        $this->setupValidPostService($postId);
        $this->setupEventDispatcher();
        $this->setupResponse();

        // Act
        $result = $this->controller->recordView($this->request, $this->response, (string) $postId);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testRecordViewWithAnonymousUser(): void
    {
        // Arrange
        $postId = 789;
        $userIp = '10.0.0.1';
        $userAgent = 'Safari/14.0';

        $this->setupValidRequest($postId, null, $userIp, $userAgent);
        $this->setupValidPostService($postId);
        $this->setupEventDispatcher();
        $this->setupResponse();

        // Act
        $result = $this->controller->recordView($this->request, $this->response, (string) $postId);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testRecordViewWithInvalidPostId(): void
    {
        // Arrange
        $this->setupResponse();

        // Act
        $result = $this->controller->recordView($this->request, $this->response, 'invalid');

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testRecordViewWithMissingPostId(): void
    {
        // Arrange
        $this->setupResponse();

        // Act
        $result = $this->controller->recordView($this->request, $this->response, '');

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testRecordViewWithZeroPostId(): void
    {
        // Arrange
        $this->setupResponse();

        // Act
        $result = $this->controller->recordView($this->request, $this->response, '0');

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testRecordViewWithNegativePostId(): void
    {
        // Arrange
        $this->setupResponse();

        // Act
        $result = $this->controller->recordView($this->request, $this->response, '-1');

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testRecordViewWithPostNotFound(): void
    {
        // Arrange
        $postId = 999;
        $this->setupValidRequest($postId, null, '127.0.0.1');
        $this->setupResponse();

        $this->postService
            ->shouldReceive('findById')
            ->once()
            ->with($postId)
            ->andThrow(new PostNotFoundException($postId));

        // Act
        $result = $this->controller->recordView($this->request, $this->response, (string) $postId);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testRecordViewWithGenericException(): void
    {
        // Arrange
        $postId = 111;
        $this->setupValidRequest($postId, null, '127.0.0.1');
        $this->setupResponse();

        $this->postService
            ->shouldReceive('findById')
            ->once()
            ->with($postId)
            ->andThrow(new Exception('Database error'));

        // Act
        $result = $this->controller->recordView($this->request, $this->response, (string) $postId);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testRecordViewWithRequestBody(): void
    {
        // Arrange
        $postId = 222;
        $userId = 333;
        $referrer = 'https://example.com/source';

        $this->setupValidRequestWithBody($postId, $userId, '192.168.1.1', 'Chrome/91.0', $referrer);
        $this->setupValidPostService($postId);
        $this->setupEventDispatcher();
        $this->setupResponse();

        // Act
        $result = $this->controller->recordView($this->request, $this->response, (string) $postId);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testRecordViewWithProxyHeaders(): void
    {
        // Arrange
        $postId = 444;
        $realIp = '203.0.113.1';

        $this->setupRequestWithProxyHeaders($postId, null, $realIp);
        $this->setupValidPostService($postId);
        $this->setupEventDispatcher();
        $this->setupResponse();

        // Act
        $result = $this->controller->recordView($this->request, $this->response, (string) $postId);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testRecordViewEventDispatcherError(): void
    {
        // Arrange
        $postId = 555;
        $this->setupValidRequest($postId, null, '127.0.0.1');
        $this->setupValidPostService($postId);
        $this->setupResponse();

        $this->eventDispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->andThrow(new Exception('Event dispatcher error'));

        // Act
        $result = $this->controller->recordView($this->request, $this->response, (string) $postId);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    /**
     * 設定有效的請求 mock.
     */
    private function setupValidRequest(int $postId, ?int $userId, string $userIp, ?string $userAgent = null): void
    {
        $this->request->shouldReceive('getAttribute')
            ->with('user_id')
            ->andReturn($userId);

        $this->request->shouldReceive('getHeaderLine')
            ->with('User-Agent')
            ->andReturn($userAgent ?? '');

        $this->request->shouldReceive('getHeaderLine')
            ->with('Referer')
            ->andReturn('');

        $this->request->shouldReceive('getBody')
            ->andReturn('{}');

        $serverParams = ['REMOTE_ADDR' => $userIp];
        $this->request->shouldReceive('getServerParams')
            ->andReturn($serverParams);
    }

    /**
     * 設定包含 body 的有效請求 mock.
     */
    private function setupValidRequestWithBody(int $postId, ?int $userId, string $userIp, string $userAgent, string $referrer): void
    {
        $this->request->shouldReceive('getAttribute')
            ->with('user_id')
            ->andReturn($userId);

        $this->request->shouldReceive('getHeaderLine')
            ->with('User-Agent')
            ->andReturn($userAgent);

        $this->request->shouldReceive('getHeaderLine')
            ->with('Referer')
            ->andReturn('');

        $bodyData = json_encode(['referrer' => $referrer]);
        $this->request->shouldReceive('getBody')
            ->andReturn($bodyData);

        $serverParams = ['REMOTE_ADDR' => $userIp];
        $this->request->shouldReceive('getServerParams')
            ->andReturn($serverParams);
    }

    /**
     * 設定包含代理標頭的請求 mock.
     */
    private function setupRequestWithProxyHeaders(int $postId, ?int $userId, string $realIp): void
    {
        $this->request->shouldReceive('getAttribute')
            ->with('user_id')
            ->andReturn($userId);

        $this->request->shouldReceive('getHeaderLine')
            ->with('User-Agent')
            ->andReturn('Test/1.0');

        $this->request->shouldReceive('getHeaderLine')
            ->with('Referer')
            ->andReturn('');

        $this->request->shouldReceive('getBody')
            ->andReturn('{}');

        $serverParams = [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => $realIp,
        ];
        $this->request->shouldReceive('getServerParams')
            ->andReturn($serverParams);
    }

    /**
     * 設定有效的 PostService mock.
     */
    private function setupValidPostService(int $postId): void
    {
        // 建立一個假的 Post 物件
        $postMock = Mockery::mock(Post::class);
        $postMock->shouldReceive('getId')->andReturn($postId);
        $postMock->shouldReceive('getTitle')->andReturn('Test Post');

        $this->postService
            ->shouldReceive('findById')
            ->once()
            ->with($postId)
            ->andReturn($postMock);
    }

    /**
     * 設定事件分派器 mock.
     */
    private function setupEventDispatcher(): void
    {
        $this->eventDispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(PostViewed::class));
    }

    /**
     * 設定回應 mock.
     */
    private function setupResponse(): void
    {
        $this->responseBody
            ->shouldReceive('write')
            ->once()
            ->with(Mockery::type('string'));
    }
}
