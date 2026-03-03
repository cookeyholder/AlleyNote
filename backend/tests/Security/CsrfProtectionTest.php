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
use Tests\TestCase;

class CsrfProtectionTest extends TestCase
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

    private int $lastStatusCode = 0;

    private array $headers = [];

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
        );

        $this->response->shouldReceive('getBody')->andReturn($this->stream);
        $this->stream->shouldReceive('write')->andReturnUsing(function ($content) {
            $this->lastWrittenContent = (string) $content;

            return strlen((string) $content);
        });

        $this->request->shouldReceive('getAttribute')->with('user_id')->andReturn(1)->byDefault();
        $this->response->shouldReceive('withStatus')->andReturnUsing(function ($status) {
            $this->lastStatusCode = $status;

            return $this->response;
        });
        $this->response->shouldReceive('withHeader')->andReturnUsing(function ($name, $value) {
            $this->headers[$name] = $value;

            return $this->response;
        });
        $this->response->shouldReceive('getStatusCode')->andReturnUsing(function () {
            return $this->lastStatusCode;
        });
    }

    #[Test]
    public function shouldRejectRequestWithoutCsrfToken(): void
    {
        $this->csrfProtection->shouldReceive('validateToken')->andReturn(false);
        $this->request->shouldReceive('getHeaderLine')->with('X-CSRF-TOKEN')->andReturn('');
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1']);

        $response = $this->controller->store($this->request, $this->response);

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function shouldRejectRequestWithInvalidCsrfToken(): void
    {
        $this->csrfProtection->shouldReceive('validateToken')->andReturn(false);
        $this->request->shouldReceive('getHeaderLine')->with('X-CSRF-TOKEN')->andReturn('invalid-token');
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1']);

        $response = $this->controller->store($this->request, $this->response);

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function shouldAcceptRequestWithValidCsrfToken(): void
    {
        $this->csrfProtection->shouldReceive('validateToken')->andReturn(true);
        $this->request->shouldReceive('getHeaderLine')->with('X-CSRF-TOKEN')->andReturn('valid-token');
        $this->request->shouldReceive('getMethod')->andReturn('POST');
        $this->request->shouldReceive('getParsedBody')->andReturn(['title' => 'Test', 'content' => 'Test']);
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1']);

        $this->postService->shouldReceive('createPost')->andReturn(new Post(['id' => 1]));

        $response = $this->controller->store($this->request, $this->response);

        $this->assertEquals(201, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
