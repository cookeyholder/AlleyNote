<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\PasswordSecurityServiceInterface;
use App\Domains\Auth\Services\Advanced\PwnedPasswordService;
use App\Shared\Exceptions\ValidationException;

class PasswordSecurityService implements PasswordSecurityServiceInterface
{
    // 密碼最小長度
    private const MIN_PASSWORD_LENGTH = 12;

    // 密碼最大長度 (防止 DoS 攻擊)
    private const MAX_PASSWORD_LENGTH = 128;

    // 密碼複雜度要求
    private const REQUIRE_UPPERCASE = true;

    private const REQUIRE_LOWERCASE = true;

    private const REQUIRE_NUMBERS = true;

    private const REQUIRE_SYMBOLS = true;

    private const MIN_UNIQUE_CHARS = 8;

    // 基本弱密碼清單（作為備選方案）
    private const FALLBACK_COMMON_PASSWORDS = [
        'password',
        '123456',
        '123456789',
        'qwerty',
        'abc123',
        'password123',
        'admin',
        'letmein',
        'welcome',
        'monkey',
        '111111',
        '222222',
        '333333',
        '000000',
        '654321',
        'password1',
        'qwerty123',
        '123123',
        'admin123',
    ];

    private PwnedPasswordService $pwnedPasswordService;

    public function __construct(?PwnedPasswordService $pwnedPasswordService = null)
    {
        $this->pwnedPasswordService = $pwnedPasswordService ?? new PwnedPasswordService();
    }

    public function hashPassword(string $password): string
    {
        $this->validatePassword($password);

        // 使用 Argon2ID 演算法（PHP 7.2+ 支援）
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536, // 64 MB
                'time_cost' => 4,       // 4 iterations
                'threads' => 3,         // 3 threads
            ]);
        }

        // 降級到 Argon2i
        if (defined('PASSWORD_ARGON2I')) {
            return password_hash($password, PASSWORD_ARGON2I, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3,
            ]);
        }

        // 最後降級到 bcrypt
        return password_hash($password, PASSWORD_BCRYPT, [
            'cost' => 12,
        ]);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        // 檢查是否需要重新雜湊（演算法或參數已更新）
        if (defined('PASSWORD_ARGON2ID')) {
            return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3,
            ]);
        }

        if (defined('PASSWORD_ARGON2I')) {
            return password_needs_rehash($hash, PASSWORD_ARGON2I, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3,
            ]);
        }

        return password_needs_rehash($hash, PASSWORD_BCRYPT, [
            'cost' => 12,
        ]);
    }

    public function validatePassword(string $password): void
    {
        // 檢查長度
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw ValidationException::fromSingleError(
                'password',
                sprintf('密碼長度必須至少為 %d 個字元', self::MIN_PASSWORD_LENGTH),
            );
        }

        if (strlen($password) > self::MAX_PASSWORD_LENGTH) {
            throw ValidationException::fromSingleError(
                'password',
                sprintf('密碼長度不能超過 %d 個字元', self::MAX_PASSWORD_LENGTH),
            );
        }

        // 檢查字元複雜度
        $this->validatePasswordComplexity($password);

        // 檢查是否為常見弱密碼
        $commonPasswordResult = $this->isCommonPassword($password);
        if ($commonPasswordResult['is_common']) {
            throw ValidationException::fromSingleError('password', $commonPasswordResult['message']);
        }

        // 檢查重複字元
        if ($this->hasExcessiveRepetition($password)) {
            throw ValidationException::fromSingleError('password', '密碼包含過多重複字元');
        }

        // 檢查順序字元
        if ($this->hasSequentialChars($password)) {
            throw ValidationException::fromSingleError('password', '密碼不能包含連續的字元序列');
        }
    }

    public function generateSecurePassword(int $length = 16): string
    {
        if ($length < self::MIN_PASSWORD_LENGTH) {
            $length = self::MIN_PASSWORD_LENGTH;
        }

        if ($length > self::MAX_PASSWORD_LENGTH) {
            $length = self::MAX_PASSWORD_LENGTH;
        }

        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        // 確保每種字元類型至少有一個
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        // 填充剩餘字元
        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // 打亂字元順序
        return str_shuffle($password);
    }

    public function calculatePasswordStrength(string $password): array
    {
        $score = 0;
        $feedback = [];

        // 長度評分
        $length = strlen($password);
        if ($length >= 12) {
            $score += 25;
        } elseif ($length >= 8) {
            $score += 15;
            $feedback[] = '建議密碼長度至少 12 個字元';
        } else {
            $feedback[] = '密碼長度過短';
        }

        // 字元類型評分
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasLower = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        $hasSymbol = preg_match('/[^A-Za-z0-9]/', $password);

        $typeCount = $hasUpper + $hasLower + $hasNumber + $hasSymbol;
        $score += $typeCount * 15;

        if (!$hasUpper) {
            $feedback[] = '建議包含大寫字母';
        }
        if (!$hasLower) {
            $feedback[] = '建議包含小寫字母';
        }
        if (!$hasNumber) {
            $feedback[] = '建議包含數字';
        }
        if (!$hasSymbol) {
            $feedback[] = '建議包含特殊符號';
        }

        // 唯一字元評分
        $uniqueChars = count(array_unique(str_split($password)));
        if ($uniqueChars >= self::MIN_UNIQUE_CHARS) {
            $score += 20;
        } else {
            $feedback[] = '建議使用更多不同的字元';
        }

        // 常見密碼檢查
        $commonPasswordResult = $this->isCommonPassword($password);
        if ($commonPasswordResult['is_common']) {
            $score -= 30;
            $feedback[] = $commonPasswordResult['source'] === 'hibp_api'
                ? sprintf('此密碼已在 %d 次資料外洩中被發現', $commonPasswordResult['breach_count'])
                : '這是常見的弱密碼';
        }

        // 重複字元檢查
        if ($this->hasExcessiveRepetition($password)) {
            $score -= 20;
            $feedback[] = '避免重複字元';
        }

        // 順序字元檢查
        if ($this->hasSequentialChars($password)) {
            $score -= 15;
            $feedback[] = '避免使用連續字元';
        }

        $score = max(0, min(100, $score));

        $strength = match (true) {
            $score >= 80 => 'very_strong',
            $score >= 60 => 'strong',
            $score >= 40 => 'medium',
            $score >= 20 => 'weak',
            default => 'very_weak'
        };

        return [
            'score' => $score,
            'strength' => $strength,
            'feedback' => $feedback,
        ];
    }

    private function validatePasswordComplexity(string $password): void
    {
        $errors = [];

        if (self::REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = '至少包含一個大寫字母';
        }

        if (self::REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors[] = '至少包含一個小寫字母';
        }

        if (self::REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            $errors[] = '至少包含一個數字';
        }

        if (self::REQUIRE_SYMBOLS && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = '至少包含一個特殊符號';
        }

        // 檢查唯一字元數量
        $uniqueChars = count(array_unique(str_split($password)));
        if ($uniqueChars < self::MIN_UNIQUE_CHARS) {
            $errors[] = sprintf('至少包含 %d 個不同的字元', self::MIN_UNIQUE_CHARS);
        }

        if (!empty($errors)) {
            throw ValidationException::fromErrors(['password' => $errors], '密碼必須' . implode('、', $errors));
        }
    }

    private function isCommonPassword(string $password): array
    {
        // 首先使用 HIBP API 檢查
        $pwnedResult = $this->pwnedPasswordService->isPasswordPwned($password);

        if ($pwnedResult['api_available']) {
            if ($pwnedResult['is_leaked']) {
                return [
                    'is_common' => true,
                    'message' => sprintf(
                        '此密碼已在 %d 次資料外洩中被發現，請選擇一個更安全的密碼',
                        $pwnedResult['count'],
                    ),
                    'source' => 'hibp_api',
                    'breach_count' => $pwnedResult['count'],
                ];
            }
        } else {
            // API 無法使用時，使用備選清單
            $lowerPassword = strtolower($password);
            if (in_array($lowerPassword, self::FALLBACK_COMMON_PASSWORDS, true)) {
                return [
                    'is_common' => true,
                    'message' => '密碼過於常見，請選擇更安全的密碼',
                    'source' => 'fallback_list',
                    'breach_count' => 0,
                ];
            }
        }

        return [
            'is_common' => false,
            'message' => null,
            'source' => $pwnedResult['api_available'] ? 'hibp_api' : 'fallback_list',
            'breach_count' => 0,
        ];
    }

    private function hasExcessiveRepetition(string $password): bool
    {
        // 檢查是否有超過 3 個連續相同字元
        return preg_match('/(.)\1{3,}/', $password) === 1;
    }

    private function hasSequentialChars(string $password): bool
    {
        $sequences = [
            'abcdefghijklmnopqrstuvwxyz',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '0123456789',
            'qwertyuiop',
            'asdfghjkl',
            'zxcvbnm',
        ];

        foreach ($sequences as $sequence) {
            // 檢查正向和反向序列（長度 4+）
            for ($i = 0; $i <= strlen($sequence) - 4; $i++) {
                $subseq = substr($sequence, $i, 4);
                if (str_contains(strtolower($password), $subseq)) {
                    return true;
                }
                if (str_contains(strtolower($password), strrev($subseq))) {
                    return true;
                }
            }
        }

        return false;
    }
}
