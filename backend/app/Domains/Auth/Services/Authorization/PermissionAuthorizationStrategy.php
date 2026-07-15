<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services\Authorization;

use App\Domains\Auth\ValueObjects\AuthorizationResult;

/**
 * 權限授權策略 (Permission-based).
 *
 * 根據使用者直接擁有的權限檢查存取權限。
 * 支援通配符權限（如 * 或 resource.*）與特定權限（如 resource.action）。
 */
final class PermissionAuthorizationStrategy implements AuthorizationStrategyInterface
{
    public function evaluate(AuthorizationContext $context): AuthorizationResult
    {
        $userPermissions = $context->userPermissions;

        if (empty($userPermissions)) {
            return new AuthorizationResult(false, '使用者權限為空', 'NO_PERMISSIONS');
        }

        $requiredPermission = "{$context->resource}.{$context->action}";

        if (in_array('*', $userPermissions, true) || in_array("{$context->resource}.*", $userPermissions, true)) {
            return new AuthorizationResult(
                allowed: true,
                reason: "使用者擁有資源 {$context->resource} 的完整權限",
                code: 'PERMISSION_WILDCARD_ACCESS',
                appliedRules: ['permission_wildcard'],
            );
        }

        if (in_array($requiredPermission, $userPermissions, true)) {
            return new AuthorizationResult(
                allowed: true,
                reason: "使用者擁有權限 {$requiredPermission}",
                code: 'PERMISSION_SPECIFIC_ACCESS',
                appliedRules: ['permission_specific'],
            );
        }

        return new AuthorizationResult(
            allowed: false,
            reason: "使用者沒有權限 {$requiredPermission}",
            code: 'PERMISSION_INSUFFICIENT',
            appliedRules: ['permission_check'],
        );
    }
}
