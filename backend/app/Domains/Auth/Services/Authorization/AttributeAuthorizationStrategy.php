<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services\Authorization;

use App\Domains\Auth\ValueObjects\AuthorizationResult;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 屬性授權策略 (ABAC).
 *
 * 根據使用者與請求的屬性進行存取控制，包含：
 * - 時間基礎的存取控制（時段、星期限制）
 * - 資源擁有者檢查（僅 update/delete 操作）
 *
 * 注意：IP 基礎的存取控制已由 NetworkHelper 處理，不在此策略中。
 */
final class AttributeAuthorizationStrategy implements AuthorizationStrategyInterface
{
    /**
     * @param array<int, array<string, mixed>> $timeRestrictions 時間限制規則陣列
     * @param array<string, mixed> $ownershipRules 資源擁有者規則對應表
     */
    public function __construct(
        private array $timeRestrictions = [],
        private array $ownershipRules = [],
    ) {}

    public function evaluate(AuthorizationContext $context): AuthorizationResult
    {
        $timeResult = $this->checkTimeBasedAccess($context->userRole, $context->action);
        if (!$timeResult->isAllowed() && $timeResult->getCode() !== 'TIME_CHECK_SKIPPED') {
            return $timeResult;
        }

        if ($context->action === 'update' || $context->action === 'delete') {
            $ownershipResult = $this->checkResourceOwnership($context->userId, $context->resource, $context->request);
            if ($ownershipResult->isAllowed()) {
                return $ownershipResult;
            }
        }

        return new AuthorizationResult(false, '屬性檢查未通過', 'ATTRIBUTE_CHECK_FAILED');
    }

    /**
     * 時間基礎的存取控制檢查.
     */
    private function checkTimeBasedAccess(?string $userRole, string $action): AuthorizationResult
    {
        if (empty($this->timeRestrictions)) {
            return new AuthorizationResult(true, '無時間限制', 'TIME_CHECK_SKIPPED');
        }

        $currentHour = (int) date('H');
        $currentDay = (int) date('w');

        foreach ($this->timeRestrictions as $restriction) {
            if (!$this->matchesTimeRestriction($restriction, $userRole, $action, $currentHour, $currentDay)) {
                continue;
            }

            return new AuthorizationResult(
                allowed: false,
                reason: "操作 {$action} 在當前時間不被允許",
                code: 'TIME_RESTRICTION_VIOLATED',
                appliedRules: ['time_restriction'],
            );
        }

        return new AuthorizationResult(true, '時間檢查通過', 'TIME_CHECK_PASSED');
    }

    /**
     * 資源擁有者檢查.
     */
    private function checkResourceOwnership(
        int $userId,
        string $resource,
        ServerRequestInterface $request,
    ): AuthorizationResult {
        $resourceId = $this->extractResourceId($request, $resource);
        if ($resourceId === null) {
            return new AuthorizationResult(false, '無法識別資源 ID', 'RESOURCE_ID_NOT_FOUND');
        }

        if ($this->isResourceOwner($userId, $resource, $resourceId)) {
            return new AuthorizationResult(
                allowed: true,
                reason: "使用者是資源 {$resource}#{$resourceId} 的擁有者",
                code: 'RESOURCE_OWNER_ACCESS',
                appliedRules: ['resource_ownership'],
            );
        }

        return new AuthorizationResult(
            allowed: false,
            reason: "使用者不是資源 {$resource}#{$resourceId} 的擁有者",
            code: 'NOT_RESOURCE_OWNER',
            appliedRules: ['resource_ownership'],
        );
    }

    /**
     * 檢查時間限制是否匹配.
     *
     * @param array<string, mixed> $restriction
     */
    private function matchesTimeRestriction(
        array $restriction,
        ?string $userRole,
        string $action,
        int $currentHour,
        int $currentDay,
    ): bool {
        /** @var list<string>|null $roles */
        $roles = $restriction['roles'] ?? null;
        if ($roles !== null && !in_array($userRole, $roles, true)) {
            return false;
        }

        /** @var list<string>|null $restrictedActions */
        $restrictedActions = $restriction['actions'] ?? null;
        if ($restrictedActions !== null && !in_array($action, $restrictedActions, true)) {
            return false;
        }

        /** @var list<int>|null $allowedHours */
        $allowedHours = $restriction['hours'] ?? null;
        if ($allowedHours !== null && !in_array($currentHour, $allowedHours, true)) {
            return true;
        }

        /** @var list<int>|null $allowedDays */
        $allowedDays = $restriction['days'] ?? null;
        if ($allowedDays !== null && !in_array($currentDay, $allowedDays, true)) {
            return true;
        }

        return false;
    }

    /**
     * 從請求中提取資源 ID.
     */
    private function extractResourceId(
        ServerRequestInterface $request,
        string $resource,
    ): ?int {
        $path = trim($request->getUri()->getPath(), '/');
        $segments = explode('/', $path);

        if (count($segments) >= 4 && $segments[0] === 'api' && is_numeric($segments[3])) {
            return (int) $segments[3];
        }

        foreach ($segments as $segment) {
            if (is_numeric($segment)) {
                return (int) $segment;
            }
        }

        $queryParams = $request->getQueryParams();
        if (isset($queryParams['id']) && is_numeric($queryParams['id'])) {
            return (int) $queryParams['id'];
        }

        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            $body = $request->getParsedBody();
            if (is_array($body) && isset($body['id']) && is_numeric($body['id'])) {
                return (int) $body['id'];
            }
        }

        return null;
    }

    /**
     * 檢查使用者是否為資源擁有者.
     */
    private function isResourceOwner(int $userId, string $resource, int $resourceId): bool
    {
        if (empty($this->ownershipRules)) {
            return true;
        }

        if (isset($this->ownershipRules[$resource])) {
            return $userId === $resourceId;
        }

        return false;
    }
}
