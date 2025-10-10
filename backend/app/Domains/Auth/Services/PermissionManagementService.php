<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Repositories\PermissionRepository;
use App\Shared\Exceptions\NotFoundException;

/**
 * 權限管理服務.
 */
class PermissionManagementService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
    ) {}

    /**
     * 取得所有權限列表.
     *
     * @return array<int, Permission>
     */
    public function listPermissions(): array
    {
        return array_values($this->permissionRepository->findAll());
    }

    /**
     * 取得單一權限.
     *
     * @throws NotFoundException
     */
    public function getPermission(int $id): Permission
    {
        $permission = $this->permissionRepository->findById($id);

        if (!$permission) {
            throw new NotFoundException("權限不存在 (ID: {$id})");
        }

        return $permission;
    }

    /**
     * 根據名稱取得權限.
     *
     * @throws NotFoundException
     */
    public function getPermissionByName(string $name): Permission
    {
        $permission = $this->permissionRepository->findByName($name);

        if (!$permission) {
            throw new NotFoundException("權限不存在 (名稱: {$name})");
        }

        return $permission;
    }

    /**
     * 取得權限群組.
     *
     * @return array<string, array<int, Permission>>
     */
    public function getPermissionsByGroup(): array
    {
        $grouped = $this->permissionRepository->findAllGroupedByResource();
        $result = [];
        foreach ($grouped as $key => $permissions) {
            $result[$key] = array_values($permissions);
        }

        return $result;
    }
}
