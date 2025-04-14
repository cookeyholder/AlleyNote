<?php

declare(strict_types=1);

namespace App\Services\Security\Contracts;

use App\Exceptions\CsrfTokenException;

interface CsrfProtectionServiceInterface
{
    /**
     * 產生新的 CSRF token
     * 
     * @return string 新產生的 token
     */
    public function generateToken(): string;

    /**
     * 驗證 CSRF token 是否有效
     * 
     * @param string $token 要驗證的 token
     * @throws CsrfTokenException 當 token 無效時拋出
     */
    public function validateToken(string $token): void;

    /**
     * 從請求中取得 CSRF token
     * 
     * @return string|null 如果找到 token 則返回，否則返回 null
     */
    public function getTokenFromRequest(): ?string;
}
