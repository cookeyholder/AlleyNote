<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\DTOs;

use App\Domains\Auth\DTOs\CreateUserDTO;
use App\Domains\Auth\DTOs\LoginRequestDTO;
use App\Domains\Auth\DTOs\RefreshRequestDTO;
use App\Domains\Auth\DTOs\UpdateUserDTO;
use InvalidArgumentException;
use Tests\Support\UnitTestCase;

final class AuthDtoTypeSafetyTest extends UnitTestCase
{
    public function testLoginRequestFromArrayNormalizesScopes(): void
    {
        $dto = LoginRequestDTO::fromArray([
            'email' => 'user@example.com',
            'password' => 'secret',
            'remember_me' => 1,
            'scopes' => ['read', '', 123, 'write'],
        ]);

        $this->assertSame('user@example.com', $dto->email);
        $this->assertTrue($dto->rememberMe);
        $this->assertSame(['read', 'write'], $dto->scopes);
    }

    public function testLoginRequestRejectsEmptyScopeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Scopes must be a non-empty string list');

        new LoginRequestDTO('user@example.com', 'secret', false, ['read', '']);
    }

    public function testRefreshRequestFromArrayNormalizesScopes(): void
    {
        $dto = RefreshRequestDTO::fromArray([
            'refresh_token' => 'refresh-token',
            'scopes' => ['profile', null, 'admin'],
        ]);

        $this->assertSame('refresh-token', $dto->refreshToken);
        $this->assertSame(['profile', 'admin'], $dto->scopes);
    }

    public function testCreateUserFromArrayNormalizesRoleIds(): void
    {
        $dto = CreateUserDTO::fromArray([
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'Q7$z!9Lm#2',
            'role_ids' => [1, '2', 3, -1, 3],
        ]);

        $this->assertSame([1, 3], $dto->roleIds);
    }

    public function testUpdateUserRejectsInvalidRoleIdList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Role IDs must be a positive integer list');

        new UpdateUserDTO(roleIds: [1, 0]);
    }

    public function testUpdateUserFromArrayNormalizesRoleIds(): void
    {
        $dto = UpdateUserDTO::fromArray([
            'role_ids' => [2, 2, '3', 4],
        ]);

        $this->assertSame([2, 4], $dto->roleIds);
        $this->assertTrue($dto->hasUpdates());
    }
}
