<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Application\Controllers\Api\V1\PostController;
use App\Domains\Post\Contracts\PostServiceInterface;
use App\Domains\Post\Models\Post;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Contracts\CsrfProtectionServiceInterface;
use App\Domains\Statistics\Services\PostViewStatisticsService;
use App\Infrastructure\Http\Response;
use App\Shared\Contracts\OutputSanitizerInterface;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Validation\Validator;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\SecureDDDTestCase;

/**
 * CSRF 防護整合測試.
 */
class CsrfProtectionTest extends SecureDDDTestCase
{
    private PostController $controller;

    private PostServiceInterface&MockInterface $postService;

    private ValidatorInterface $validator;

    private OutputSanitizerInterface&MockInterface $sanitizer;

    private ActivityLoggingServiceInterface&MockInterface $activityLogger;

    private CsrfProtectionServiceInterface&MockInterface $csrfProtection;

    private ServerRequestInterface&MockInterface $request;

    private Response $response;

    private StreamInterface&MockInterface $stream;

    protected function setUp(): void
    {
        parent::setUp();

        $this->postService = Mockery::mock(PostServiceInterface::class);
        $this->validator = new Validator();
        $this->sanitizer = Mockery::mock(OutputSanitizerInterface::class);
        $this->activityLogger = Mockery::mock(ActivityLoggingServiceInterface::class);
        $this->csrfProtection = Mockery::mock(CsrfProtectionServiceInterface::class);

        $this->sanitizer->shouldReceive('sanitizeHtml')->andReturnUsing(fn($i) => $i)->zeroOrMoreTimes();
        $this->sanitizer->shouldReceive('sanitizeRichText')->andReturnUsing(fn($i) => $i)->zeroOrMoreTimes();
        $this->activityLogger->shouldReceive('logSuccess')->zeroOrMoreTimes();
        $this->activityLogger->shouldReceive('logFailure')->zeroOrMoreTimes();

        $this->stream = Mockery::mock(StreamInterface::class);
        $this->stream->shouldReceive('write')->andReturn(100)->zeroOrMoreTimes();

        $this->request = Mockery::mock(ServerRequestInterface::class);
        $this->request->shouldReceive('getAttribute')->with('user_id')->andReturn(1)->byDefault();
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1'])->byDefault();
        $this->request->shouldReceive('getHeaderLine')->andReturn('')->zeroOrMoreTimes();
        $this->request->shouldReceive('getBody')->andReturn($this->stream)->zeroOrMoreTimes();
        $this->request->shouldReceive('getMethod')->andReturn('POST')->zeroOrMoreTimes();
        $this->request->shouldReceive('getUri->getPath')->andReturn('/api/posts')->zeroOrMoreTimes();

        $this->response = new Response();

        $this->controller = new PostController(
            $this->postService,
            $this->validator,
            $this->sanitizer,
            $this->activityLogger,
            Mockery::mock(PostViewStatisticsService::class),
            Mockery::mock(\App\Domains\Auth\Contracts\AuthorizationServiceInterface::class)->shouldReceive('authorize')->andReturn(new \App\Application\Middleware\AuthorizationResult(true, 'Allowed', 'SUCCESS'))->getMock(),
        );
    }

    #[Test]
    public function shouldAcceptRequestWithValidData(): void
    {
        // Arrange
        $postData = ['title' => 'Valid Title', 'content' => 'Valid Content'];
        $this->request->shouldReceive('getParsedBody')->andReturn($postData);
        $post = new Post(array_merge($postData, ['id' => 1, 'uuid' => 'uuid', 'seq_number' => 1]));
        $this->postService->shouldReceive('createPost')->once()->andReturn($post);

        // Act
        $response = $this->controller->store($this->request, $this->response);

        // Assert
        $this->assertEquals(201, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
