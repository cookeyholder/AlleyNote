<?php

declare(strict_types=1);

namespace App\Services\Security\Contracts;

interface AuthorizationServiceInterface
{
    /**
     * 檢查使用者是否擁有特定權限
     */
    public function hasPermission(int $userId, string $permission): bool;

    /**
     * 檢查使用者是否擁有特定角色
     */
    public function hasRole(int $userId, string $roleName): bool;

    /**
     * 檢查使用者是否能夠對資源執行特定動作
     */
    public function can(int $userId, string $resource, string $action): bool;

    /**
     * 為使用者分配角色
     */
    public function assignRole(int $userId, string $roleName): bool;

    /**
     * 移除使用者的角色
     */
    public function removeRole(int $userId, string $roleName): bool;

    /**
     * 為使用者直接分配權限
     */
    public function givePermission(int $userId, string $permission): bool;

    /**
     * 移除使用者的直接權限
     */
    public function revokePermission(int $userId, string $permission): bool;

    /**
     * 取得使用者的所有角色
     */
    public function getUserRoles(int $userId): array;

    /**
     * 取得使用者的所有權限（包含角色權限和直接權限）
     */
    public function getUserPermissions(int $userId): array;

    /**
     * 檢查使用者是否為超級管理員
     */
    public function isSuperAdmin(int $userId): bool;
}
