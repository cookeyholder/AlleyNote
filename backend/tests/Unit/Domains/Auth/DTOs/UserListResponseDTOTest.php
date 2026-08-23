<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\DTOs;

use App\Domains\Auth\DTOs\UserListResponseDTO;
use Tests\Support\UnitTestCase;

/**
 * 使用者列表回應 DTO 單元測試.
 */
final class UserListResponseDTOTest extends UnitTestCase
{
    public function testConstructAndToArray(): void
    {
        $dto = new UserListResponseDTO(
            id: 1,
            username: 'alice',
            email: 'alice@example.com',
            roles: [['name' => 'admin', 'display_name' => '管理員']],
            lastLogin: '2026-08-22 12:00:00',
            createdAt: '2026-01-01 00:00:00',
        );

        $this->assertSame(1, $dto->id);
        $this->assertSame('alice', $dto->username);
        $this->assertSame('alice@example.com', $dto->email);
        $this->assertSame([['name' => 'admin', 'display_name' => '管理員']], $dto->roles);
        $this->assertSame('2026-08-22 12:00:00', $dto->lastLogin);
        $this->assertSame('2026-01-01 00:00:00', $dto->createdAt);

        $this->assertSame([
            'id'         => 1,
            'username'   => 'alice',
            'email'      => 'alice@example.com',
            'roles'      => [['name' => 'admin', 'display_name' => '管理員']],
            'last_login' => '2026-08-22 12:00:00',
            'created_at' => '2026-01-01 00:00:00',
        ], $dto->toArray());
    }

    public function testFromArray(): void
    {
        $dto = UserListResponseDTO::fromArray([
            'id'         => '2',
            'username'   => 'bob',
            'email'      => 'bob@example.com',
            'created_at' => '2026-02-01 10:00:00',
        ]);

        $this->assertSame(2, $dto->id);
        $this->assertSame('bob', $dto->username);
        $this->assertSame([], $dto->roles);
        $this->assertNull($dto->lastLogin);
    }
}
