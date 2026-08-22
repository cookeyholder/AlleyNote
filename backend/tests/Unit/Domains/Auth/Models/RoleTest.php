<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Models;

use App\Domains\Auth\Models\Role;
use Tests\Support\UnitTestCase;

/**
 * 角色模型單元測試.
 */
final class RoleTest extends UnitTestCase
{
    public function testConstructAndGetters(): void
    {
        $role = new Role(
            id: 1,
            name: 'admin',
            displayName: '管理員',
            description: '系統最高管理員',
            createdAt: '2026-01-01 00:00:00',
            updatedAt: '2026-01-02 00:00:00',
        );

        $this->assertSame(1, $role->getId());
        $this->assertSame('admin', $role->getName());
        $this->assertSame('管理員', $role->getDisplayName());
        $this->assertSame('系統最高管理員', $role->getDescription());
        $this->assertSame('2026-01-01 00:00:00', $role->getCreatedAt());
        $this->assertSame('2026-01-02 00:00:00', $role->getUpdatedAt());
    }

    public function testDefaultDisplayNameFallsBackToName(): void
    {
        $role = new Role(2, 'guest');
        $this->assertSame('guest', $role->getDisplayName());
        $this->assertNull($role->getDescription());
        $this->assertSame('', $role->getCreatedAt());
        $this->assertSame('', $role->getUpdatedAt());
    }

    public function testToArrayAndFromArray(): void
    {
        $data = [
            'id'           => 3,
            'name'         => 'editor',
            'display_name' => '編輯者',
            'description'  => '負責審核文章',
            'created_at'   => '2026-01-01 10:00:00',
            'updated_at'   => '2026-01-01 10:00:00',
        ];

        $role = Role::fromArray($data);
        $this->assertSame(3, $role->getId());
        $this->assertSame('editor', $role->getName());
        $this->assertSame('編輯者', $role->getDisplayName());
        $this->assertSame('負責審核文章', $role->getDescription());

        $this->assertSame($data, $role->toArray());
    }
}
