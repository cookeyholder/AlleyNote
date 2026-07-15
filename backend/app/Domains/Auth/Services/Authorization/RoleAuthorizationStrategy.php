<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services\Authorization;

use App\Domains\Auth\ValueObjects\AuthorizationResult;

/**
 * 角色授權策略 (RBAC).
 *
 * 根據使用者角色檢查是否擁有資源與操作的存取權限。
 * 支援通配符權限（如 * 或 resource.*）與特定權限（如 resource.action）。
 */
final class RoleAuthorizationStrategy implements AuthorizationStrategyInterface
{
    /**
     * @param array<string, list<string>> $rolePermissions 角色權限對應表，鍵為角色名稱，值為權限字串陣列
     */
    public function __construct(
        private array $rolePermissions = [],
    ) {}

    public function evaluate(AuthorizationContext $context): AuthorizationResult
    {
        $userRole = $context->userRole;

        if (empty($userRole)) {
            return new AuthorizationResult(false, '使用者角色為空', 'NO_ROLE');
        }

        $permissions = $this->rolePermissions[$userRole] ?? [];

        if (in_array('*', $permissions, true) || in_array("{$context->resource}.*", $permissions, true)) {
            return new AuthorizationResult(
                allowed: true,
                reason: "角色 {$userRole} 擁有資源 {$context->resource} 的完整權限",
                code: 'ROLE_WILDCARD_ACCESS',
                appliedRules: ['role_wildcard'],
            );
        }

        $requiredPermission = "{$context->resource}.{$context->action}";
        if (in_array($requiredPermission, $permissions, true)) {
            return new AuthorizationResult(
                allowed: true,
                reason: "角色 {$userRole} 擁有權限 {$requiredPermission}",
                code: 'ROLE_SPECIFIC_ACCESS',
                appliedRules: ['role_specific'],
            );
        }

        return new AuthorizationResult(
            allowed: false,
            reason: "角色 {$userRole} 沒有權限 {$requiredPermission}",
            code: 'ROLE_INSUFFICIENT',
            appliedRules: ['role_check'],
        );
    }
}
