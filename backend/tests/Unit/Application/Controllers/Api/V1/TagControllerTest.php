<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Api\V1;

use App\Application\Controllers\Api\V1\TagController;
use App\Domains\Post\Services\TagManagementService;
use App\Shared\Exceptions\NotFoundException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

#[CoversClass(TagController::class)]
class TagControllerTest extends TestCase
{
    private TagController $controller;

    private TagManagementService&MockInterface $tagManagementService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tagManagementService = Mockery::mock(TagManagementService::class);

        $this->controller = new TagController(
            $this->tagManagementService,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function testIndexSuccess(): void
    {
        $request = $this->createMockRequest();
        $response = $this->createMockResponse();

        $this->tagManagementService
            ->shouldReceive('listTags')
            ->once()
            ->andReturn([
                'items' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => 20,
                'last_page' => 1,
            ]);

        $result = $this->controller->index($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    #[Test]
    public function testShowSuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn('1');

        $response = $this->createMockResponse();

        $this->tagManagementService
            ->shouldReceive('getTag')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'name' => 'Test Tag']);

        $result = $this->controller->show($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    #[Test]
    public function testShowNotFound(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn('999');

        $response = $this->createMockResponse(404);

        $this->tagManagementService
            ->shouldReceive('getTag')
            ->once()
            ->with(999)
            ->andThrow(new NotFoundException('Tag not found'));

        $result = $this->controller->show($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    #[Test]
    public function testStoreSuccess(): void
    {
        $requestData = [
            'name' => 'New Tag',
            'slug' => 'new-tag',
            'description' => 'Test description',
            'color' => '#FF0000',
        ];

        $request = $this->createMockRequest();
        $bodyStream = Mockery::mock(StreamInterface::class);
        $bodyStream->shouldReceive('__toString')
            ->andReturn(json_encode($requestData) ?: '');
        $request->shouldReceive('getBody')
            ->andReturn($bodyStream);

        $response = $this->createMockResponse(201);

        $this->tagManagementService
            ->shouldReceive('createTag')
            ->once()
            ->andReturn(['id' => 1, 'name' => 'New Tag']);

        $result = $this->controller->store($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    #[Test]
    public function testUpdateSuccess(): void
    {
        $requestData = [
            'name' => 'Updated Tag',
        ];

        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn('1');
        $bodyStream = Mockery::mock(StreamInterface::class);
        $bodyStream->shouldReceive('__toString')
            ->andReturn(json_encode($requestData) ?: '');
        $request->shouldReceive('getBody')
            ->andReturn($bodyStream);

        $response = $this->createMockResponse();

        $this->tagManagementService
            ->shouldReceive('updateTag')
            ->once()
            ->andReturn(['id' => 1, 'name' => 'Updated Tag']);

        $result = $this->controller->update($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    #[Test]
    public function testDestroySuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn('1');

        $response = $this->createMockResponse();

        $this->tagManagementService
            ->shouldReceive('deleteTag')
            ->once()
            ->with(1);

        $result = $this->controller->destroy($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    private function createMockRequest(): ServerRequestInterface&MockInterface
    {
        $request = Mockery::mock(ServerRequestInterface::class);

        $request->shouldReceive('getQueryParams')
            ->andReturn([]);

        return $request;
    }

    private function createMockResponse(int $statusCode = 200): ResponseInterface&MockInterface
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
            ->with($statusCode)
            ->andReturnSelf();

        return $response;
    }
}
