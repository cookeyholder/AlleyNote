<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\DTOs;

use App\Domains\Auth\DTOs\CreateUserDTO;
use InvalidArgumentException;
use Tests\Support\UnitTestCase;

/**
 * 建立使用者 DTO 單元測試.
 */
final class CreateUserDTOTest extends UnitTestCase
{
    /**
     * 測試合法資料建立與 toArray.
     */
    public function testConstructAndToArray(): void
    {
        $dto = new CreateUserDTO(
            username: 'alice',
            email: 'alice@example.com',
            password: 'P@ssw0rd#9Km$2',
            roleIds: [1, 2],
        );

        $this->assertSame('alice', $dto->username);
        $this->assertSame('alice@example.com', $dto->email);
        $this->assertSame('P@ssw0rd#9Km$2', $dto->password);
        $this->assertSame([1, 2], $dto->roleIds);

        $array = $dto->toArray();
        $this->assertSame([
            'username' => 'alice',
            'email'    => 'alice@example.com',
            'password' => 'P@ssw0rd#9Km$2',
            'role_ids' => [1, 2],
        ], $array);
    }

    /**
     * 測試 fromArray 工廠方法.
     */
    public function testFromArray(): void
    {
        $dto = CreateUserDTO::fromArray([
            'username' => 'bob',
            'email'    => 'bob@example.com',
            'password' => 'P@ssw0rd#9Km$2',
            'role_ids' => [3, 4],
        ]);

        $this->assertSame('bob', $dto->username);
        $this->assertSame('bob@example.com', $dto->email);
        $this->assertSame([3, 4], $dto->roleIds);
    }

    /**
     * 測試角色 ID 包含負數或非整數時拋出 InvalidArgumentException.
     */
    public function testInvalidRoleIdsThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Role IDs must be a positive integer list');

        new CreateUserDTO(
            username: 'test',
            email: 'test@example.com',
            password: 'P@ssw0rd#9Km$2',
            roleIds: [-5],
        );
    }
}
