<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\DTOs;

use App\Domains\Auth\DTOs\UpdateUserDTO;
use InvalidArgumentException;
use Tests\Support\UnitTestCase;

/**
 * 更新使用者 DTO 單元測試.
 */
final class UpdateUserDTOTest extends UnitTestCase
{
    /**
     * 測試建構與 hasUpdates 以及 toArray.
     */
    public function testConstructAndToArray(): void
    {
        $dto = new UpdateUserDTO(
            username: 'alice_new',
            email: 'alice_new@example.com',
            password: 'N3wP@ssw0rd!#9Km',
            roleIds: [2],
        );

        $this->assertTrue($dto->hasUpdates());
        $this->assertSame([
            'username' => 'alice_new',
            'email'    => 'alice_new@example.com',
            'password' => 'N3wP@ssw0rd!#9Km',
            'role_ids' => [2],
        ], $dto->toArray());

        $emptyDto = new UpdateUserDTO();
        $this->assertFalse($emptyDto->hasUpdates());
        $this->assertSame([], $emptyDto->toArray());
    }

    /**
     * 測試 fromArray 方法.
     */
    public function testFromArray(): void
    {
        $dto = UpdateUserDTO::fromArray([
            'username' => 'bob',
            'email'    => 'bob@example.com',
        ]);

        $this->assertSame('bob', $dto->username);
        $this->assertSame('bob@example.com', $dto->email);
        $this->assertNull($dto->password);
        $this->assertNull($dto->roleIds);
    }

    /**
     * 測試非法的角色 ID.
     */
    public function testInvalidRoleIds(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Role IDs must be a positive integer list');

        new UpdateUserDTO(roleIds: [0]);
    }
}
