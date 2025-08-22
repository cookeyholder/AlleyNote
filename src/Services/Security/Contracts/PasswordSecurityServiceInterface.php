<?php

declare(strict_types=1);

namespace App\Services\Security\Contracts;

interface PasswordSecurityServiceInterface
{
    /**
     * 雜湊密碼
     */
    public function hashPassword(string $password): string;

    /**
     * 驗證密碼
     */
    public function verifyPassword(string $password, string $hash): bool;

    /**
     * 檢查雜湊是否需要重新產生
     */
    public function needsRehash(string $hash): bool;

    /**
     * 驗證密碼強度
     * 
     * @throws \App\Exceptions\ValidationException
     */
    public function validatePassword(string $password): void;

    /**
     * 產生安全的隨機密碼
     */
    public function generateSecurePassword(int $length = 16): string;

    /**
     * 計算密碼強度評分
     */
    public function calculatePasswordStrength(string $password): array;
}
