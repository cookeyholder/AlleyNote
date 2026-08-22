<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services;

use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Repositories\PermissionRepository;
use App\Domains\Auth\Repositories\RoleRepository;
use App\Domains\Auth\Services\RoleManagementService;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * 角色管理服務單元測試.
 */
final class RoleManagementServiceTest extends UnitTestCase
{
    private RoleRepository&MockInterface $roleRepository;

    private PermissionRepository&MockInterface $permissionRepository;

    private RoleManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->roleRepository = Mockery::mock(RoleRepository::class);
        $this->permissionRepository = Mockery::mock(PermissionRepository::class);
        $this->service = new RoleManagementService(
            $this->roleRepository,
            $this->permissionRepository,
        );
    }

    /**
     * 測試取得所有角色.
     */
    public function testListRoles(): void
    {
        $roles = [
            new Role(1, 'admin', '管理員', '系統管理員'),
            new Role(2, 'user', '一般使用者', '一般使用者'),
        ];

        $this->roleRepository
            ->shouldReceive('findAll')
            ->once()
            ->andReturn($roles);

        $result = $this->service->listRoles();
        $this->assertCount(2, $result);
        $this->assertSame('admin', $result[0]->getName());
    }

    /**
     * 測試取得單一角色（含權限）.
     */
    public function testGetRoleSuccess(): void
    {
        $role = new Role(1, 'editor', '編輯者', '內容編輯');
        $permissions = [
            new Permission(10, 'post.create', 'post', 'create', '建立文章'),
        ];

        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($role);

        $this->roleRepository
            ->shouldReceive('getRolePermissionIds')
            ->once()
            ->with(1)
            ->andReturn([10]);

        $this->permissionRepository
            ->shouldReceive('findByIds')
            ->once()
            ->with([10])
            ->andReturn($permissions);

        $result = $this->service->getRole(1);
        $this->assertSame('editor', $result['role']['name']);
        $this->assertSame([10], $result['permission_ids']);
        $this->assertCount(1, $result['permissions']);
    }

    /**
     * 測試取得不存在之角色拋出 NotFoundException.
     */
    public function testGetRoleNotFound(): void
    {
        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('角色不存在');
        $this->service->getRole(999);
    }

    /**
     * 測試建立角色成功（含分配權限）.
     */
    public function testCreateRoleSuccessWithPermissions(): void
    {
        $createdRole = new Role(5, 'manager', '經理', '管理團隊');

        $this->roleRepository
            ->shouldReceive('findByName')
            ->once()
            ->with('manager')
            ->andReturn(null);

        $this->roleRepository
            ->shouldReceive('create')
            ->once()
            ->with('manager', '經理', '管理團隊')
            ->andReturn($createdRole);

        $this->roleRepository
            ->shouldReceive('setRolePermissions')
            ->once()
            ->with(5, [1, 2, 3])
            ->andReturn(true);

        $result = $this->service->createRole('manager', '經理', '管理團隊', ['1', 2, '3']);
        $this->assertSame(5, $result->getId());
        $this->assertSame('manager', $result->getName());
    }

    /**
     * 測試建立角色名稱重複拋出 ValidationException.
     */
    public function testCreateRoleNameExistsThrowsException(): void
    {
        $existing = new Role(1, 'existing_role');

        $this->roleRepository
            ->shouldReceive('findByName')
            ->once()
            ->with('existing_role')
            ->andReturn($existing);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('角色名稱已存在');
        $this->service->createRole('existing_role', '已存在角色');
    }

    /**
     * 測試更新角色成功
     */
    public function testUpdateRoleSuccess(): void
    {
        $role = new Role(1, 'author', '舊名稱', '舊描述');
        $updatedRole = new Role(1, 'author', '新名稱', '新描述');

        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($role);

        $this->roleRepository
            ->shouldReceive('update')
            ->once()
            ->with(1, '新名稱', '新描述')
            ->andReturn(true);

        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($updatedRole);

        $result = $this->service->updateRole(1, '新名稱', '新描述');
        $this->assertSame('新名稱', $result->getDisplayName());
    }

    /**
     * 測試更新不存在的角色拋出 NotFoundException.
     */
    public function testUpdateRoleNotFound(): void
    {
        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('角色不存在');
        $this->service->updateRole(999, '名稱');
    }

    /**
     * 測試更新角色後若查無角色拋出 RuntimeException.
     */
    public function testUpdateRoleFailedToGetUpdatedThrowsRuntimeException(): void
    {
        $role = new Role(1, 'author');

        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($role);

        $this->roleRepository
            ->shouldReceive('update')
            ->once()
            ->with(1, '新名稱', null)
            ->andReturn(true);

        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to get updated role');
        $this->service->updateRole(1, '新名稱');
    }

    /**
     * 測試刪除自訂角色成功
     */
    public function testDeleteRoleSuccess(): void
    {
        $role = new Role(10, 'custom_role');

        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with(10)
            ->andReturn($role);

        $this->roleRepository
            ->shouldReceive('delete')
            ->once()
            ->with(10)
            ->andReturn(true);

        $result = $this->service->deleteRole(10);
        $this->assertTrue($result);
    }

    /**
     * 測試刪除不存在之角色拋出 NotFoundException.
     */
    public function testDeleteRoleNotFound(): void
    {
        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('角色不存在');
        $this->service->deleteRole(999);
    }

    /**
     * 測試刪除系統預設角色 (super_admin, admin) 拋出 ValidationException.
     */
    public function testDeleteSystemDefaultRolesThrowsException(): void
    {
        $superAdmin = new Role(1, 'super_admin');
        $admin = new Role(2, 'admin');

        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($superAdmin);

        try {
            $this->service->deleteRole(1);
            $this->fail('應該拋出 ValidationException');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('無法刪除系統預設角色', $e->getMessage());
        }

        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with(2)
            ->andReturn($admin);

        try {
            $this->service->deleteRole(2);
            $this->fail('應該拋出 ValidationException');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('無法刪除系統預設角色', $e->getMessage());
        }
    }

    /**
     * 測試設定角色權限成功
     */
    public function testSetRolePermissionsSuccess(): void
    {
        $role = new Role(3, 'editor');

        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with(3)
            ->andReturn($role);

        $this->roleRepository
            ->shouldReceive('setRolePermissions')
            ->once()
            ->with(3, [1, 2])
            ->andReturn(true);

        $result = $this->service->setRolePermissions(3, [1, 2]);
        $this->assertTrue($result);
    }

    /**
     * 測試設定不存在角色之權限拋出 NotFoundException.
     */
    public function testSetRolePermissionsRoleNotFound(): void
    {
        $this->roleRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('角色不存在');
        $this->service->setRolePermissions(999, [1]);
    }

    /**
     * 測試列出所有權限.
     */
    public function testListPermissions(): void
    {
        $perms = [
            new Permission(1, 'post.view', 'post', 'view'),
            new Permission(2, 'post.create', 'post', 'create'),
        ];

        $this->permissionRepository
            ->shouldReceive('findAll')
            ->once()
            ->andReturn($perms);

        $result = $this->service->listPermissions();
        $this->assertCount(2, $result);
    }

    /**
     * 測試列出按資源分組之權限.
     */
    public function testListPermissionsGroupedByResource(): void
    {
        $grouped = [
            'post' => [new Permission(1, 'post.view', 'post', 'view')],
            'user' => [new Permission(2, 'user.view', 'user', 'view')],
        ];

        $this->permissionRepository
            ->shouldReceive('findAllGroupedByResource')
            ->once()
            ->andReturn($grouped);

        $result = $this->service->listPermissionsGroupedByResource();
        $this->assertArrayHasKey('post', $result);
        $this->assertArrayHasKey('user', $result);
    }
}
