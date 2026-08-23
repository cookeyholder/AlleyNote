<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Api\V1;

use App\Application\Controllers\Api\V1\UserController;
use App\Domains\Auth\DTOs\CreateUserDTO;
use App\Domains\Auth\DTOs\UpdateUserDTO;
use App\Domains\Auth\Services\UserManagementService;
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
 * UserController 單元測試.
 */
#[CoversClass(UserController::class)]
class UserControllerTest extends UnitTestCase
{
    private UserController $controller;

    private UserManagementService&MockInterface $userManagementService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userManagementService = Mockery::mock(UserManagementService::class);
        $this->controller = new UserController(
            $this->userManagementService,
        );
    }

    #[Test]
    public function testIndexSuccessDefaultParams(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $this->userManagementService
            ->shouldReceive('listUsers')
            ->once()
            ->with(1, 10, [])
            ->andReturn([
                'items'     => [['id' => 1, 'username' => 'testuser']],
                'total'     => 1,
                'page'      => 1,
                'per_page'  => 10,
                'last_page' => 1,
            ]);

        $result = $this->controller->index($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testIndexSuccessWithCustomParamsAndSearch(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([
            'page'     => '2',
            'per_page' => '25',
            'search'   => 'john',
        ]);
        $response = $this->createMockResponse();

        $this->userManagementService
            ->shouldReceive('listUsers')
            ->once()
            ->with(2, 25, ['search' => 'john'])
            ->andReturn([
                'items'     => [['id' => 2, 'username' => 'john']],
                'total'     => 1,
                'page'      => 2,
                'per_page'  => 25,
                'last_page' => 2,
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

        $this->userManagementService
            ->shouldReceive('getUser')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'username' => 'testuser', 'email' => 'test@example.com']);

        $result = $this->controller->show($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testShowNotFound(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn('999');
        $response = $this->createMockResponse(404);

        $this->userManagementService
            ->shouldReceive('getUser')
            ->once()
            ->with(999)
            ->andThrow(new NotFoundException('使用者不存在'));

        $result = $this->controller->show($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
    }

    #[Test]
    public function testStoreSuccess(): void
    {
        $userData = [
            'username' => 'superadminuser',
            'email'    => 'superadminuser@example.com',
            'password' => 'SecurePass#2026',
        ];

        $request = $this->createMockRequestWithBody($userData);
        $response = $this->createMockResponse(201);

        $this->userManagementService
            ->shouldReceive('createUser')
            ->once()
            ->with(Mockery::type(CreateUserDTO::class))
            ->andReturn(['id' => 1, 'username' => 'superadminuser', 'email' => 'superadminuser@example.com']);

        $result = $this->controller->store($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(201, $result->getStatusCode());
    }

    #[Test]
    public function testStoreValidationException(): void
    {
        $request = $this->createMockRequestWithBody(['username' => 'test']);
        $response = $this->createMockResponse(422);

        $result = $this->controller->store($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(422, $result->getStatusCode());
    }

    #[Test]
    public function testStoreWithInvalidJsonBody(): void
    {
        $request = $this->createMockRequest();
        $bodyStream = Mockery::mock(StreamInterface::class);
        $bodyStream->shouldReceive('__toString')->andReturn('invalid-json');
        $request->shouldReceive('getBody')->andReturn($bodyStream);

        $response = $this->createMockResponse(422);

        $result = $this->controller->store($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(422, $result->getStatusCode());
    }

    #[Test]
    public function testUpdateSuccess(): void
    {
        $updateData = ['username' => 'updated_name'];
        $request = $this->createMockRequestWithBody($updateData);
        $request->shouldReceive('getAttribute')->with('id')->andReturn('5');
        $response = $this->createMockResponse(200);

        $this->userManagementService
            ->shouldReceive('updateUser')
            ->once()
            ->with(5, Mockery::type(UpdateUserDTO::class))
            ->andReturn(['id' => 5, 'username' => 'updated_name']);

        $result = $this->controller->update($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testUpdateNotFound(): void
    {
        $request = $this->createMockRequestWithBody(['username' => 'new_name']);
        $request->shouldReceive('getAttribute')->with('id')->andReturn('999');
        $response = $this->createMockResponse(404);

        $this->userManagementService
            ->shouldReceive('updateUser')
            ->once()
            ->with(999, Mockery::type(UpdateUserDTO::class))
            ->andThrow(new NotFoundException('使用者不存在'));

        $result = $this->controller->update($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
    }

    #[Test]
    public function testUpdateValidationException(): void
    {
        $request = $this->createMockRequestWithBody(['email' => 'invalid-email']);
        $request->shouldReceive('getAttribute')->with('id')->andReturn('1');
        $response = $this->createMockResponse(422);

        $this->userManagementService
            ->shouldReceive('updateUser')
            ->once()
            ->with(1, Mockery::type(UpdateUserDTO::class))
            ->andThrow(ValidationException::fromSingleError('email', '格式錯誤'));

        $result = $this->controller->update($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(422, $result->getStatusCode());
    }

    #[Test]
    public function testDestroySuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('id')->andReturn('10');
        $response = $this->createMockResponse(200);

        $this->userManagementService
            ->shouldReceive('deleteUser')
            ->once()
            ->with(10);

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

        $this->userManagementService
            ->shouldReceive('deleteUser')
            ->once()
            ->with(999)
            ->andThrow(new NotFoundException('使用者不存在'));

        $result = $this->controller->destroy($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
    }

    #[Test]
    public function testAssignRolesSuccess(): void
    {
        $request = $this->createMockRequestWithBody(['role_ids' => [1, 2]]);
        $request->shouldReceive('getAttribute')->with('id')->andReturn('3');
        $response = $this->createMockResponse(200);

        $this->userManagementService
            ->shouldReceive('assignRoles')
            ->once()
            ->with(3, [1, 2]);

        $result = $this->controller->assignRoles($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testAssignRolesNotFound(): void
    {
        $request = $this->createMockRequestWithBody(['role_ids' => [1]]);
        $request->shouldReceive('getAttribute')->with('id')->andReturn('999');
        $response = $this->createMockResponse(404);

        $this->userManagementService
            ->shouldReceive('assignRoles')
            ->once()
            ->with(999, [1])
            ->andThrow(new NotFoundException('使用者不存在'));

        $result = $this->controller->assignRoles($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
    }

    #[Test]
    public function testActivateSuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('id')->andReturn('1');
        $response = $this->createMockResponse(200);

        $this->userManagementService
            ->shouldReceive('activateUser')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'status' => 'active']);

        $result = $this->controller->activate($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testActivateNotFound(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('id')->andReturn('999');
        $response = $this->createMockResponse(404);

        $this->userManagementService
            ->shouldReceive('activateUser')
            ->once()
            ->with(999)
            ->andThrow(new NotFoundException('使用者不存在'));

        $result = $this->controller->activate($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
    }

    #[Test]
    public function testDeactivateSuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('id')->andReturn('1');
        $response = $this->createMockResponse(200);

        $this->userManagementService
            ->shouldReceive('deactivateUser')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'status' => 'inactive']);

        $result = $this->controller->deactivate($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testDeactivateNotFound(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('id')->andReturn('999');
        $response = $this->createMockResponse(404);

        $this->userManagementService
            ->shouldReceive('deactivateUser')
            ->once()
            ->with(999)
            ->andThrow(new NotFoundException('使用者不存在'));

        $result = $this->controller->deactivate($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
    }

    #[Test]
    public function testResetPasswordSuccess(): void
    {
        $request = $this->createMockRequestWithBody(['password' => 'newPassword123']);
        $request->shouldReceive('getAttribute')->with('id')->andReturn('1');
        $response = $this->createMockResponse(200);

        $this->userManagementService
            ->shouldReceive('resetPassword')
            ->once()
            ->with(1, 'newPassword123');

        $result = $this->controller->resetPassword($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testResetPasswordEmptyPasswordValidation(): void
    {
        $request = $this->createMockRequestWithBody(['password' => '']);
        $request->shouldReceive('getAttribute')->with('id')->andReturn('1');
        $response = $this->createMockResponse(422);

        $result = $this->controller->resetPassword($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(422, $result->getStatusCode());
    }

    #[Test]
    public function testResetPasswordNotFound(): void
    {
        $request = $this->createMockRequestWithBody(['password' => 'newPassword123']);
        $request->shouldReceive('getAttribute')->with('id')->andReturn('999');
        $response = $this->createMockResponse(404);

        $this->userManagementService
            ->shouldReceive('resetPassword')
            ->once()
            ->with(999, 'newPassword123')
            ->andThrow(new NotFoundException('使用者不存在'));

        $result = $this->controller->resetPassword($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(404, $result->getStatusCode());
    }

    #[Test]
    public function testResetPasswordServiceValidationException(): void
    {
        $request = $this->createMockRequestWithBody(['password' => 'short']);
        $request->shouldReceive('getAttribute')->with('id')->andReturn('1');
        $response = $this->createMockResponse(422);

        $this->userManagementService
            ->shouldReceive('resetPassword')
            ->once()
            ->with(1, 'short')
            ->andThrow(ValidationException::fromSingleError('password', '密碼太短'));

        $result = $this->controller->resetPassword($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(422, $result->getStatusCode());
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
