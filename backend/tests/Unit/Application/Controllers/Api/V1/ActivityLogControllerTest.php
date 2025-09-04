<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Api\V1;

use App\Application\Controllers\Api\V1\ActivityLogController;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Contracts\ActivityLogRepositoryInterface;
use App\Domains\Security\DTOs\CreateActivityLogDTO;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

#[CoversClass(ActivityLogController::class)]
class ActivityLogControllerTest extends TestCase
{
    private ActivityLogController $controller;

    private ActivityLoggingServiceInterface&MockInterface $loggingService;

    private ActivityLogRepositoryInterface&MockInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggingService = Mockery::mock(ActivityLoggingServiceInterface::class);
        $this->repository = Mockery::mock(ActivityLogRepositoryInterface::class);

        $this->controller = new ActivityLogController(
            $this->loggingService,
            $this->repository,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function testStoreSuccess(): void
    {
        $requestData = [
            'action_type' => 'auth.login.success',
            'user_id' => 123,
            'metadata' => ['ip' => '127.0.0.1'],
        ];

        $request = $this->createMockRequest($requestData);
        $response = $this->createMockResponse();

        $this->loggingService
            ->shouldReceive('log')
            ->once()
            ->with(Mockery::type(CreateActivityLogDTO::class))
            ->andReturn(true);

        $result = $this->controller->store($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    #[Test]
    public function testIndexSuccess(): void
    {
        $request = $this->createMockRequest();
        $response = $this->createMockResponse();

        $this->repository
            ->shouldReceive('findAll')
            ->once()
            ->andReturn([]);

        $result = $this->controller->index($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    private function createMockRequest(array $body = []): ServerRequestInterface&MockInterface
    {
        $request = Mockery::mock(ServerRequestInterface::class);

        $request->shouldReceive('getParsedBody')
            ->andReturn($body);

        $request->shouldReceive('getQueryParams')
            ->andReturn([]);

        return $request;
    }

    private function createMockResponse(): ResponseInterface&MockInterface
    {
        $response = Mockery::mock(ResponseInterface::class);
        $stream = Mockery::mock(StreamInterface::class);

        $response->shouldReceive('getBody')
            ->andReturn($stream);

        $stream->shouldReceive('write')
            ->andReturnSelf();

        $response->shouldReceive('withHeader')
            ->andReturnSelf();

        $response->shouldReceive('withStatus')
            ->andReturnSelf();

        return $response;
    }
}
