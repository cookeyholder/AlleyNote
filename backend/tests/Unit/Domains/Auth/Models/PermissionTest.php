<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Models;

use App\Domains\Auth\Models\Permission;
use Tests\Support\UnitTestCase;

/**
 * 權限模型單元測試.
 */
final class PermissionTest extends UnitTestCase
{
    public function testConstructAndGetters(): void
    {
        $perm = new Permission(
            id: 1,
            name: 'post.create',
            resource: 'post',
            action: 'create',
            description: '新增文章權限',
            createdAt: '2026-01-01 00:00:00',
            updatedAt: '2026-01-02 00:00:00',
        );

        $this->assertSame(1, $perm->getId());
        $this->assertSame('post.create', $perm->getName());
        $this->assertSame('post', $perm->getResource());
        $this->assertSame('create', $perm->getAction());
        $this->assertSame('新增文章權限', $perm->getDescription());
        $this->assertSame('2026-01-01 00:00:00', $perm->getCreatedAt());
        $this->assertSame('2026-01-02 00:00:00', $perm->getUpdatedAt());
    }

    public function testToArrayAndFromArray(): void
    {
        $data = [
            'id'          => 2,
            'name'        => 'user.delete',
            'description' => null,
            'resource'    => 'user',
            'action'      => 'delete',
            'created_at'  => '',
            'updated_at'  => '',
        ];

        $perm = Permission::fromArray($data);
        $this->assertSame(2, $perm->getId());
        $this->assertSame('user.delete', $perm->getName());
        $this->assertSame('user', $perm->getResource());
        $this->assertSame('delete', $perm->getAction());
        $this->assertNull($perm->getDescription());

        $this->assertSame($data, $perm->toArray());
    }
}
