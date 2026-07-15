<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services\Authorization;

use App\Domains\Auth\ValueObjects\AuthorizationResult;

/**
 * 授權策略介面.
 *
 * 定義授權策略的評估契約，所有具體策略均需實作此介面。
 */
interface AuthorizationStrategyInterface
{
    /**
     * 評估授權上下文，回傳授權結果.
     *
     * @param AuthorizationContext $context 授權上下文
     *
     * @return AuthorizationResult 授權結果
     */
    public function evaluate(AuthorizationContext $context): AuthorizationResult;
}
