<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services\Authorization;

use App\Domains\Auth\ValueObjects\AuthorizationResult;

/**
 * 授權協調服務.
 *
 * 負責協調多個授權策略的執行順序，實現短路評估機制：
 * 依序執行策略鏈，任一策略回傳允許即立即跳過後續策略。
 * 若所有策略均拒絕，則回傳預設的拒絕結果。
 *
 * 注意：此服務與 Domains\Auth\Services\AuthorizationService（角色/權限 CRUD）職責不同，
 * AuthorizationService 負責資料庫存取，而本服務專注於記憶體中的策略協調。
 */
class AuthorizationOrchestratorService
{
    /**
     * @param array<int, AuthorizationStrategyInterface> $strategies 依執行順序排列的策略實例陣列
     * @param string $defaultPolicy 預設策略（allow 或 deny）
     */
    public function __construct(
        private array $strategies = [],
        private string $defaultPolicy = 'deny',
    ) {}

    /**
     * 執行授權檢查.
     *
     * 依序執行所有策略，任一策略允許即回傳允許結果。
     * 若所有策略均拒絕或無策略可執行，則依預設策略回傳結果。
     *
     * @param AuthorizationContext $context 授權上下文
     *
     * @return AuthorizationResult 授權結果
     */
    public function authorize(AuthorizationContext $context): AuthorizationResult
    {
        foreach ($this->strategies as $strategy) {
            $result = $strategy->evaluate($context);
            if ($result->isAllowed()) {
                return $result;
            }
        }

        if ($this->defaultPolicy === 'allow') {
            return AuthorizationResult::allow(
                reason: '預設允許存取',
                code: 'DEFAULT_ALLOW',
                appliedRules: ['default_allow'],
            );
        }

        return new AuthorizationResult(
            allowed: false,
            reason: "使用者無權限執行操作：{$context->action} on {$context->resource}",
            code: 'INSUFFICIENT_PERMISSIONS',
            appliedRules: ['default_deny'],
        );
    }
}
