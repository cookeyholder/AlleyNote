<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services\Authorization;

use App\Domains\Auth\ValueObjects\AuthorizationResult;

/**
 * 超級管理員授權策略.
 *
 * 檢查使用者是否擁有超級管理員角色，若是則直接允許所有操作。
 * 此策略應為第一個執行的策略，以實現短路最佳化。
 */
final class SuperAdminAuthorizationStrategy implements AuthorizationStrategyInterface
{
    /**
     * 預設的管理員角色清單.
     *
     * @var array<string>
     */
    private const ADMIN_ROLES = ['admin', 'super_admin', 'system_admin'];

    /**
     * @param array<string> $adminRoles 自訂的管理員角色清單（可擴充預設清單）
     */
    public function __construct(
        private array $adminRoles = [],
    ) {}

    public function evaluate(AuthorizationContext $context): AuthorizationResult
    {
        if (!$this->isSuperAdmin($context->userRole)) {
            return new AuthorizationResult(
                allowed: false,
                reason: '使用者非超級管理員',
                code: 'NOT_SUPER_ADMIN',
                appliedRules: ['super_admin_check'],
            );
        }

        return AuthorizationResult::allowSuperAdmin();
    }

    /**
     * 檢查是否為超級管理員角色.
     */
    private function isSuperAdmin(?string $userRole): bool
    {
        if (empty($userRole)) {
            return false;
        }

        $allAdminRoles = array_unique(array_merge(self::ADMIN_ROLES, $this->adminRoles));

        return in_array($userRole, $allAdminRoles, true);
    }
}
