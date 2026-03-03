<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Application\Controllers\Api\V1\PostController;
use App\Domains\Post\Contracts\PostServiceInterface;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Statistics\Services\PostViewStatisticsService;
use App\Shared\Contracts\OutputSanitizerInterface;
use App\Shared\Contracts\ValidatorInterface;
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

    private mixed $request;

    private mixed $response;

    private mixed $stream;

    private mixed $currentResponseData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postService = Mockery::mock(PostServiceInterface::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);
        $this->sanitizer = $this->mockOutputSanitizer();
        $this->activityLogger = Mockery::mock(ActivityLoggingServiceInterface::class);
        $this->postViewStatsService = Mockery::mock(PostViewStatisticsService::class);

        // Default mock behaviors
        $this->activityLogger->shouldReceive('logSuccess')->zeroOrMoreTimes();
        $this->activityLogger->shouldReceive('logFailure')->zeroOrMoreTimes();
        $this->activityLogger->shouldReceive('log')->zeroOrMoreTimes();

        $this->validator->shouldReceive('validateOrFail')
            ->andReturnUsing(fn($data) => $data)
            ->byDefault();

        $this->stream = $this->createLocalStreamMock();
        $this->response = $this->createLocalResponseMock();
        $this->request = $this->createLocalRequestMock();
    }

    #[Test]
    public function indexShouldReturnPaginatedPosts(): void
    {
        // Arrange
        $this->request->shouldReceive('getQueryParams')->once()->andReturn(['page' => 1]);
        $this->postService->shouldReceive('listPosts')->once()->andReturn([
            'items' => [], 'total' => 0, 'page' => 1, 'per_page' => 10,
        ]);
        $this->postViewStatsService->shouldReceive('getBatchPostViewStats')->once()->andReturn([]);

        // Act
        $controller = new PostController($this->postService, $this->validator, $this->sanitizer, $this->activityLogger, $this->postViewStatsService);
        $response = $controller->index($this->request, $this->response);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSafeApiResponse($this->currentResponseData);
    }

    protected function createLocalRequestMock(): ServerRequestInterface|MockInterface
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1'])->byDefault();
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn(1)->byDefault();
        $request->shouldReceive('getBody')->andReturn($this->stream)->byDefault();
        $request->shouldReceive('getHeaderLine')->andReturn('')->zeroOrMoreTimes();

        return $request;
    }

    protected function createLocalStreamMock(): StreamInterface|MockInterface
    {
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('write')->andReturnUsing(function ($content) {
            $this->currentResponseData = json_decode((string) $content, true);

            return strlen((string) $content);
        });

        return $stream;
    }

    protected function createLocalResponseMock(): ResponseInterface|MockInterface
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
}
