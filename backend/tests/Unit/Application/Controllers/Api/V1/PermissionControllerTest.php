<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Api\V1;

use App\Application\Controllers\Api\V1\PermissionController;
use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Services\PermissionManagementService;
use App\Shared\Exceptions\NotFoundException;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\Support\UnitTestCase;

/**
 * PermissionController 單元測試.
 */
#[CoversClass(PermissionController::class)]
class PermissionControllerTest extends UnitTestCase
{
    private PermissionController $controller;

    private PermissionManagementService&MockInterface $permissionManagementService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionManagementService = Mockery::mock(PermissionManagementService::class);
        $this->controller = new PermissionController(
            $this->permissionManagementService,
        );
    }

    #[Test]
    public function testIndexSuccess(): void
    {
        $perm = new Permission(1, 'posts.create', '建立文章', 'posts', 'create', '允許建立文章');
        $request = $this->createMockRequest();
        $response = $this->createMockResponse();

        $this->permissionManagementService
            ->shouldReceive('listPermissions')
            ->once()
            ->andReturn([$perm]);

        $result = $this->controller->index($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testShowSuccess(): void
    {
        $perm = new Permission(1, 'posts.create', '建立文章', 'posts', 'create', '允許建立文章');
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('id')->andReturn('1');
        $response = $this->createMockResponse();

        $this->permissionManagementService
            ->shouldReceive('getPermission')
            ->once()
            ->with(1)
            ->andReturn($perm);

        $result = $this->controller->show($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testShowNotFound(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('id')->andReturn('999');
        $response = $this->createMockResponse(404);

        $this->permissionManagementService
            ->shouldReceive('getPermission')
            ->once()
            ->with(999)
            ->andThrow(new NotFoundException('權限不存在'));

        $result = $this->controller->show($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
    }

    #[Test]
    public function testShowInvalidIdThrowsInvalidArgumentException(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('id')->andReturn('invalid_string_id');
        $response = $this->createMockResponse();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid permission ID');

        $this->controller->show($request, $response);
    }

    private function createMockRequest(): ServerRequestInterface&MockInterface
    {
        return Mockery::mock(ServerRequestInterface::class);
    }

    private function createMockResponse(int $statusCode = 200): ResponseInterface&MockInterface
    {
        $response = Mockery::mock(ResponseInterface::class);
        $stream = Mockery::mock(StreamInterface::class);

        $response->shouldReceive('getBody')
            ->andReturn($stream);

        $stream->shouldReceive('write')
            ->andReturnUsing(fn(string $string): int => strlen($string));

        $response->shouldReceive('withHeader')
            ->andReturnSelf();

        $response->shouldReceive('withStatus')
            ->andReturnUsing(function ($code) use ($response, &$statusCode) {
                $statusCode = $code;

                return $response;
            });

        $response->shouldReceive('getStatusCode')
            ->andReturnUsing(fn() => $statusCode);

        return $response;
    }
}
