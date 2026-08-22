<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services;

use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Repositories\PermissionRepository;
use App\Domains\Auth\Services\PermissionManagementService;
use App\Shared\Exceptions\NotFoundException;
use Mockery;
use Mockery\MockInterface;
use Tests\Support\UnitTestCase;

/**
 * 權限管理服務單元測試.
 */
final class PermissionManagementServiceTest extends UnitTestCase
{
    private PermissionRepository&MockInterface $permissionRepository;

    private PermissionManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissionRepository = Mockery::mock(PermissionRepository::class);
        $this->service = new PermissionManagementService($this->permissionRepository);
    }

    /**
     * 測試取得所有權限列表.
     */
    public function testListPermissions(): void
    {
        $perms = [
            1 => new Permission(1, 'post.create', 'post', 'create'),
            2 => new Permission(2, 'post.edit', 'post', 'edit'),
        ];

        $this->permissionRepository
            ->shouldReceive('findAll')
            ->once()
            ->andReturn($perms);

        $result = $this->service->listPermissions();
        $this->assertCount(2, $result);
        $this->assertSame('post.create', $result[0]->getName());
        $this->assertSame('post.edit', $result[1]->getName());
    }

    /**
     * 測試根據 ID 取得單一權限成功
     */
    public function testGetPermissionSuccess(): void
    {
        $perm = new Permission(5, 'user.delete', 'user', 'delete', '刪除使用者');

        $this->permissionRepository
            ->shouldReceive('findById')
            ->once()
            ->with(5)
            ->andReturn($perm);

        $result = $this->service->getPermission(5);
        $this->assertSame(5, $result->getId());
        $this->assertSame('user.delete', $result->getName());
        $this->assertSame('user', $result->getResource());
        $this->assertSame('delete', $result->getAction());
    }

    /**
     * 測試根據 ID 取得不存在權限拋出 NotFoundException.
     */
    public function testGetPermissionNotFound(): void
    {
        $this->permissionRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('權限不存在 (ID: 999)');
        $this->service->getPermission(999);
    }

    /**
     * 測試根據名稱取得單一權限成功
     */
    public function testGetPermissionByNameSuccess(): void
    {
        $perm = new Permission(3, 'post.publish', 'post', 'publish');

        $this->permissionRepository
            ->shouldReceive('findByName')
            ->once()
            ->with('post.publish')
            ->andReturn($perm);

        $result = $this->service->getPermissionByName('post.publish');
        $this->assertSame('post.publish', $result->getName());
    }

    /**
     * 測試根據名稱取得不存在權限拋出 NotFoundException.
     */
    public function testGetPermissionByNameNotFound(): void
    {
        $this->permissionRepository
            ->shouldReceive('findByName')
            ->once()
            ->with('nonexistent.perm')
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('權限不存在 (名稱: nonexistent.perm)');
        $this->service->getPermissionByName('nonexistent.perm');
    }

    /**
     * 測試取得按群組分類的權限清單.
     */
    public function testGetPermissionsByGroup(): void
    {
        $grouped = [
            'post' => [
                1 => new Permission(1, 'post.view', 'post', 'view'),
                2 => new Permission(2, 'post.edit', 'post', 'edit'),
            ],
            'attachment' => [
                3 => new Permission(3, 'attachment.upload', 'attachment', 'upload'),
            ],
        ];

        $this->permissionRepository
            ->shouldReceive('findAllGroupedByResource')
            ->once()
            ->andReturn($grouped);

        $result = $this->service->getPermissionsByGroup();
        $this->assertArrayHasKey('post', $result);
        $this->assertArrayHasKey('attachment', $result);
        $this->assertCount(2, $result['post']);
        $this->assertCount(1, $result['attachment']);
        $this->assertSame('post.view', $result['post'][0]->getName());
        $this->assertSame('attachment.upload', $result['attachment'][0]->getName());
    }
}
