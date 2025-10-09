<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Repositories\PermissionRepository;
use App\Domains\Auth\Repositories\RoleRepository;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use RuntimeException;

/**
 * 角色管理服務.
 */
class RoleManagementService
{
    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly PermissionRepository $permissionRepository,
    ) {}

    /**
     * 取得所有角色.
     *
     * @return Role[]
     */
    public function listRoles(): array
    {
        return $this->roleRepository->findAll();
    }

    /**
     * 取得單一角色（包含權限）.
     */
    public function getRole(int $id): array
    {
        $role = $this->roleRepository->findById($id);

        if (!$role) {
            throw new NotFoundException('角色不存在');
        }

        $permissionIds = $this->roleRepository->getRolePermissionIds($id);
        $permissions = $this->permissionRepository->findByIds($permissionIds);

        return [
            'role' => $role->toArray(),
            'permissions' => array_map(fn($p) => $p->toArray(), $permissions),
            'permission_ids' => $permissionIds,
        ];
    }

    /**
     * 建立角色.
     */
    public function createRole(string $name, string $displayName, ?string $description = null, array $permissionIds = []): Role
    {
        // 檢查名稱是否已存在
        if ($this->roleRepository->findByName($name)) {
            throw ValidationException::fromSingleError('name', '角色名稱已存在');
        }

        $role = $this->roleRepository->create($name, $displayName, $description);

        // 分配權限
        if (!empty($permissionIds)) {
            $this->roleRepository->setRolePermissions($role->getId(), $permissionIds);
        }

        return $role;
    }

    /**
     * 更新角色.
     */
    public function updateRole(int $id, ?string $displayName = null, ?string $description = null): Role
    {
        $role = $this->roleRepository->findById($id);

        if (!$role) {
            throw new NotFoundException('角色不存在');
        }

        $this->roleRepository->update($id, $displayName, $description);

        return $this->roleRepository->findById($id) ?? throw new RuntimeException('Failed to get updated role');
    }

    /**
     * 刪除角色.
     */
    public function deleteRole(int $id): bool
    {
        $role = $this->roleRepository->findById($id);

        if (!$role) {
            throw new NotFoundException('角色不存在');
        }

        // 不允許刪除系統預設角色
        if (in_array($role->getName(), ['super_admin', 'admin'], true)) {
            throw ValidationException::fromSingleError('id', '無法刪除系統預設角色');
        }

        return $this->roleRepository->delete($id);
    }

    /**
     * 設定角色的權限.
     *
     * @param int[] $permissionIds
     */
    public function setRolePermissions(int $roleId, array $permissionIds): bool
    {
        $role = $this->roleRepository->findById($roleId);

        if (!$role) {
            throw new NotFoundException('角色不存在');
        }

        return $this->roleRepository->setRolePermissions($roleId, $permissionIds);
    }

    /**
     * 取得所有權限.
     *
     * @return Permission[]
     */
    public function listPermissions(): array
    {
        return $this->permissionRepository->findAll();
    }

    /**
     * 取得所有權限（按資源分組）.
     *
     * @return array<string, Permission[]>
     */
    public function listPermissionsGroupedByResource(): array
    {
        return $this->permissionRepository->findAllGroupedByResource();
    }
}
