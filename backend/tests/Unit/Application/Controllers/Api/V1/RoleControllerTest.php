<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Api\V1;

use App\Application\Controllers\Api\V1\RoleController;
use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Services\RoleManagementService;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\Support\UnitTestCase;

/**
 * RoleController 單元測試.
 */
#[CoversClass(RoleController::class)]
class RoleControllerTest extends UnitTestCase
{
    private RoleController $controller;

    private RoleManagementService&MockInterface $roleManagementService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleManagementService = Mockery::mock(RoleManagementService::class);
        $this->controller = new RoleController(
            $this->roleManagementService,
        );
    }

    #[Test]
    public function testIndexSuccess(): void
    {
        $role = new Role(1, 'admin', '管理員', '系統管理員');
        $request = $this->createMockRequest();
        $response = $this->createMockResponse();

        $this->roleManagementService
            ->shouldReceive('listRoles')
            ->once()
            ->andReturn([$role]);

        $result = $this->controller->index($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testShowSuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('id')->andReturn('1');
        $response = $this->createMockResponse();

        $this->roleManagementService
            ->shouldReceive('getRole')
            ->once()
            ->with(1)
            ->andReturn([
                'role'           => ['id' => 1, 'name' => 'admin'],
                'permissions'    => [['id' => 1, 'name' => 'posts.create']],
                'permission_ids' => [1],
            ]);

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

        $this->roleManagementService
            ->shouldReceive('getRole')
            ->once()
            ->with(999)
            ->andThrow(new NotFoundException('角色不存在'));

        $result = $this->controller->show($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
    }

    #[Test]
    public function testStoreSuccess(): void
    {
        $roleData = [
            'name'           => 'editor',
            'display_name'   => '編輯者',
            'description'    => '內容編輯角色',
            'permission_ids' => [1, 2],
        ];
        $role = new Role(2, 'editor', '編輯者', '內容編輯角色');

        $request = $this->createMockRequestWithBody($roleData);
        $response = $this->createMockResponse(201);

        $this->roleManagementService
            ->shouldReceive('createRole')
            ->once()
            ->with('editor', '編輯者', '內容編輯角色', [1, 2])
            ->andReturn($role);

        $result = $this->controller->store($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(201, $result->getStatusCode());
    }

    #[Test]
    public function testStoreValidationException(): void
    {
        $roleData = ['name' => 'existing_role'];
        $request = $this->createMockRequestWithBody($roleData);
        $response = $this->createMockResponse(422);

        $this->roleManagementService
            ->shouldReceive('createRole')
            ->once()
            ->with('existing_role', '', null, [])
            ->andThrow(ValidationException::fromSingleError('name', '角色名稱已存在'));

        $result = $this->controller->store($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(422, $result->getStatusCode());
    }

    #[Test]
    public function testUpdateSuccess(): void
    {
        $roleData = [
            'display_name' => '進階編輯者',
            'description'  => '修改後的描述',
        ];
        $role = new Role(2, 'editor', '進階編輯者', '修改後的描述');

        $request = $this->createMockRequestWithBody($roleData);
        $request->shouldReceive('getAttribute')->with('id')->andReturn('2');
        $response = $this->createMockResponse(200);

        $this->roleManagementService
            ->shouldReceive('updateRole')
            ->once()
            ->with(2, '進階編輯者', '修改後的描述')
            ->andReturn($role);

        $result = $this->controller->update($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testUpdateNotFound(): void
    {
        $request = $this->createMockRequestWithBody(['display_name' => 'New Name']);
        $request->shouldReceive('getAttribute')->with('id')->andReturn('999');
        $response = $this->createMockResponse(404);

        $this->roleManagementService
            ->shouldReceive('updateRole')
            ->once()
            ->with(999, 'New Name', null)
            ->andThrow(new NotFoundException('角色不存在'));

        $result = $this->controller->update($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
    }

    #[Test]
    public function testDestroySuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('id')->andReturn('3');
        $response = $this->createMockResponse(200);

        $this->roleManagementService
            ->shouldReceive('deleteRole')
            ->once()
            ->with(3);

        $result = $this->controller->destroy($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testDestroyNotFound(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('id')->andReturn('999');
        $response = $this->createMockResponse(404);

        $this->roleManagementService
            ->shouldReceive('deleteRole')
            ->once()
            ->with(999)
            ->andThrow(new NotFoundException('角色不存在'));

        $result = $this->controller->destroy($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
    }

    #[Test]
    public function testDestroyValidationException(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('id')->andReturn('1');
        $response = $this->createMockResponse(422);

        $this->roleManagementService
            ->shouldReceive('deleteRole')
            ->once()
            ->with(1)
            ->andThrow(ValidationException::fromSingleError('role', '角色仍有使用者綁定'));

        $result = $this->controller->destroy($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(422, $result->getStatusCode());
    }

    #[Test]
    public function testUpdatePermissionsSuccess(): void
    {
        $request = $this->createMockRequestWithBody(['permission_ids' => [1, 2, 3]]);
        $request->shouldReceive('getAttribute')->with('id')->andReturn('2');
        $response = $this->createMockResponse(200);

        $this->roleManagementService
            ->shouldReceive('setRolePermissions')
            ->once()
            ->with(2, [1, 2, 3]);

        $result = $this->controller->updatePermissions($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testUpdatePermissionsNotFound(): void
    {
        $request = $this->createMockRequestWithBody(['permission_ids' => [1]]);
        $request->shouldReceive('getAttribute')->with('id')->andReturn('999');
        $response = $this->createMockResponse(404);

        $this->roleManagementService
            ->shouldReceive('setRolePermissions')
            ->once()
            ->with(999, [1])
            ->andThrow(new NotFoundException('角色不存在'));

        $result = $this->controller->updatePermissions($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
    }

    #[Test]
    public function testPermissionsSuccess(): void
    {
        $perm = new Permission(1, 'posts.create', '建立文章', 'posts', 'create', '允許建立文章');
        $request = $this->createMockRequest();
        $response = $this->createMockResponse();

        $this->roleManagementService
            ->shouldReceive('listPermissions')
            ->once()
            ->andReturn([$perm]);

        $result = $this->controller->permissions($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testPermissionsGroupedSuccess(): void
    {
        $perm = new Permission(1, 'posts.create', '建立文章', 'posts', 'create', '允許建立文章');
        $request = $this->createMockRequest();
        $response = $this->createMockResponse();

        $this->roleManagementService
            ->shouldReceive('listPermissionsGroupedByResource')
            ->once()
            ->andReturn(['posts' => [$perm]]);

        $result = $this->controller->permissionsGrouped($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    private function createMockRequest(): ServerRequestInterface&MockInterface
    {
        return Mockery::mock(ServerRequestInterface::class);
    }

    private function createMockRequestWithBody(mixed $data): ServerRequestInterface&MockInterface
    {
        $request = $this->createMockRequest();
        $bodyStream = Mockery::mock(StreamInterface::class);
        $bodyString = is_array($data) ? (json_encode($data) ?: '') : (is_string($data) ? $data : '');
        $bodyStream->shouldReceive('__toString')
            ->andReturn($bodyString);
        $request->shouldReceive('getBody')->andReturn($bodyStream);

        return $request;
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
