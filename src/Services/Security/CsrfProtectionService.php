<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Exceptions\CsrfTokenException;

class CsrfProtectionService
{
    private const TOKEN_LENGTH = 32;
    private const TOKEN_EXPIRY = 3600; // 1 hour

    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        return $token;
    }

    public function validateToken(?string $token): void
    {
        if (empty($token)) {
            throw new CsrfTokenException('缺少 CSRF token');
        }

        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            throw new CsrfTokenException('無效的 CSRF token');
        }

        if ($_SESSION['csrf_token'] !== $token) {
            throw new CsrfTokenException('CSRF token 驗證失敗');
        }

        if (time() - $_SESSION['csrf_token_time'] > self::TOKEN_EXPIRY) {
            throw new CsrfTokenException('CSRF token 已過期');
        }

        // 更新 token 以防止重放攻擊
        $this->generateToken();
    }
}
