<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Application\Controllers\Api\V1\PostController;
use App\Domains\Post\Contracts\PostServiceInterface;
use App\Domains\Post\Models\Post;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Contracts\CsrfProtectionServiceInterface;
use App\Domains\Security\Contracts\XssProtectionServiceInterface;
use App\Domains\Statistics\Services\PostViewStatisticsService;
use App\Shared\Contracts\OutputSanitizerInterface;
use App\Shared\Contracts\ValidatorInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\SecureDDDTestCase;

class XssPreventionTest extends SecureDDDTestCase
{
    private PostServiceInterface&MockInterface $postService;

    private ValidatorInterface&MockInterface $validator;

    private OutputSanitizerInterface&MockInterface $sanitizer;

    private XssProtectionServiceInterface&MockInterface $xssProtection;

    private CsrfProtectionServiceInterface&MockInterface $csrfProtection;

    private ActivityLoggingServiceInterface&MockInterface $activityLogger;

    private ServerRequestInterface&MockInterface $request;

    private ResponseInterface&MockInterface $response;

    private PostController $controller;

    private StreamInterface&MockInterface $stream;

    private string $lastWrittenContent = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->postService = Mockery::mock(PostServiceInterface::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);
        $this->sanitizer = Mockery::mock(OutputSanitizerInterface::class);
        $this->xssProtection = Mockery::mock(XssProtectionServiceInterface::class);
        $this->csrfProtection = Mockery::mock(CsrfProtectionServiceInterface::class);
        $this->activityLogger = Mockery::mock(ActivityLoggingServiceInterface::class)->shouldIgnoreMissing();
        $this->request = Mockery::mock(ServerRequestInterface::class)->shouldIgnoreMissing();
        $this->response = Mockery::mock(ResponseInterface::class)->shouldIgnoreMissing();
        $this->stream = Mockery::mock(StreamInterface::class)->shouldIgnoreMissing();

        $this->activityLogger->shouldReceive('log')->byDefault()->andReturn(true);
        $this->activityLogger->shouldReceive('logFailure')->byDefault()->andReturn(true);
        $this->activityLogger->shouldReceive('logSuccess')->byDefault()->andReturn(true);

        $this->controller = new PostController(
            $this->postService,
            $this->validator,
            $this->sanitizer,
            $this->activityLogger,
            Mockery::mock(PostViewStatisticsService::class),
            Mockery::mock(\App\Domains\Auth\Contracts\AuthorizationServiceInterface::class)->shouldReceive('authorize')->andReturn(new \App\Application\Middleware\AuthorizationResult(true, 'Allowed', 'SUCCESS'))->getMock(),
        );

        $this->response->shouldReceive('getBody')->andReturn($this->stream);
        $this->stream->shouldReceive('write')->andReturnUsing(function ($content) {
            $this->lastWrittenContent = (string) $content;

            return strlen((string) $content);
        });

        $this->request->shouldReceive('getAttribute')->with('user_id')->andReturn(1)->byDefault();
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1'])->byDefault();
        $this->response->shouldReceive('withHeader')->andReturn($this->response)->byDefault();
        $this->response->shouldReceive('withStatus')->andReturn($this->response)->byDefault();
    }

    #[Test]
    public function shouldEscapeHtmlInPostTitle(): void
    {
        $dirtyTitle = '<script>alert("xss")</script>Title';
        $this->request->shouldReceive('getMethod')->andReturn('POST');
        $this->request->shouldReceive('getParsedBody')->andReturn(['title' => $dirtyTitle, 'content' => 'Content']);
        $this->csrfProtection->shouldReceive('validateToken')->andReturn(true);
        $this->request->shouldReceive('getHeaderLine')->with('X-CSRF-TOKEN')->andReturn('token');

        $this->sanitizer->shouldReceive('sanitize')->andReturn('Title');
        $this->postService->shouldReceive('createPost')->andReturn(new Post(['id' => 1]));

        $this->controller->store($this->request, $this->response);

        $this->assertStringNotContainsString('<script>', $this->lastWrittenContent);
    }

    #[Test]
    public function shouldEscapeHtmlInPostContent(): void
    {
        $dirtyContent = '<img src=x onerror=alert(1)>Content';
        $this->request->shouldReceive('getMethod')->andReturn('POST');
        $this->request->shouldReceive('getParsedBody')->andReturn(['title' => 'Title', 'content' => $dirtyContent]);
        $this->csrfProtection->shouldReceive('validateToken')->andReturn(true);
        $this->request->shouldReceive('getHeaderLine')->with('X-CSRF-TOKEN')->andReturn('token');

        $this->sanitizer->shouldReceive('sanitize')->andReturn('Content');
        $this->postService->shouldReceive('createPost')->andReturn(new Post(['id' => 1]));

        $this->controller->store($this->request, $this->response);

        $this->assertStringNotContainsString('onerror', $this->lastWrittenContent);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
