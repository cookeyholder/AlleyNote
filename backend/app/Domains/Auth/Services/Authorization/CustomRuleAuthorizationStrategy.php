<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services\Authorization;

use App\Domains\Auth\ValueObjects\AuthorizationResult;

/**
 * 自訂規則授權策略.
 *
 * 根據設定的自訂規則進行授權判斷，支援 allow、deny、conditional 三種規則類型。
 * 規則可依條件（資源、操作、角色）配對後執行對應的授權邏輯。
 */
final class CustomRuleAuthorizationStrategy implements AuthorizationStrategyInterface
{
    /**
     * @param array<string, array<string, mixed>> $customRules 自訂規則設定陣列
     */
    public function __construct(
        private array $customRules = [],
    ) {}

    public function evaluate(AuthorizationContext $context): AuthorizationResult
    {
        if (empty($this->customRules)) {
            return new AuthorizationResult(false, '沒有適用的自訂規則', 'NO_CUSTOM_RULE');
        }

        foreach ($this->customRules as $ruleName => $ruleConfig) {
            /** @var array<string, mixed> $conditions */
            $conditions = $ruleConfig['conditions'] ?? [];
            if (!$this->matchesRuleConditions($conditions, $context->resource, $context->action, $context->userRole)) {
                continue;
            }

            $ruleResult = $this->executeCustomRule($ruleName, $ruleConfig, $context);
            if ($ruleResult !== null) {
                return $ruleResult;
            }
        }

        return new AuthorizationResult(false, '沒有適用的自訂規則', 'NO_CUSTOM_RULE');
    }

    /**
     * 檢查規則條件是否匹配.
     */
    private function matchesRuleConditions(
        array $conditions,
        string $resource,
        string $action,
        ?string $userRole,
    ): bool {
        foreach ($conditions as $key => $value) {
            $matches = match ($key) {
                'resource' => is_array($value)
                    ? in_array($resource, $value, true)
                    : (is_string($value) ? $resource === $value : true),
                'action' => is_array($value)
                    ? in_array($action, $value, true)
                    : (is_string($value) ? $action === $value : true),
                'role' => is_array($value)
                    ? in_array($userRole, $value, true)
                    : (is_string($value) ? $userRole === $value : true),
                default => true,
            };
            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * 執行自訂規則.
     */
    private function executeCustomRule(
        string $ruleName,
        array $ruleConfig,
        AuthorizationContext $context,
    ): ?AuthorizationResult {
        $ruleType = is_string($ruleConfig['type'] ?? null) ? $ruleConfig['type'] : 'allow';
        $ruleMessage = is_string($ruleConfig['message'] ?? null) ? $ruleConfig['message'] : "自訂規則 {$ruleName} 生效";

        return match ($ruleType) {
            'allow' => new AuthorizationResult(
                allowed: true,
                reason: $ruleMessage,
                code: 'CUSTOM_RULE_ALLOW',
                appliedRules: ["custom:{$ruleName}"],
            ),
            'deny' => new AuthorizationResult(
                allowed: false,
                reason: $ruleMessage,
                code: 'CUSTOM_RULE_DENY',
                appliedRules: ["custom:{$ruleName}"],
            ),
            'conditional' => $this->evaluateConditionalRule($ruleConfig, $context),
            default       => null,
        };
    }

    /**
     * 評估條件式規則.
     */
    private function evaluateConditionalRule(
        array $ruleConfig,
        AuthorizationContext $context,
    ): AuthorizationResult {
        /** @var array<string, mixed> $conditions */
        $conditions = $ruleConfig['conditions'] ?? [];
        /** @var list<string> $requiredParams */
        $requiredParams = $conditions['required_params'] ?? [];

        if (!empty($requiredParams)) {
            $queryParams = $context->request->getQueryParams();
            foreach ($requiredParams as $param) {
                if (!is_string($param)) {
                    continue;
                }
                if (!isset($queryParams[$param])) {
                    return new AuthorizationResult(
                        allowed: false,
                        reason: "缺少必要參數：{$param}",
                        code: 'MISSING_REQUIRED_PARAM',
                        appliedRules: ['conditional_rule'],
                    );
                }
            }
        }

        $result = is_string($ruleConfig['result'] ?? null) ? $ruleConfig['result'] : 'allow';

        return new AuthorizationResult(
            allowed: $result === 'allow',
            reason: is_string($ruleConfig['message'] ?? null) ? $ruleConfig['message'] : '條件式規則評估完成',
            code: $result === 'allow' ? 'CONDITIONAL_ALLOW' : 'CONDITIONAL_DENY',
            appliedRules: ['conditional_rule'],
        );
    }
}
