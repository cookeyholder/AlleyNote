<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\PasswordSecurityServiceInterface;
use App\Shared\Exceptions\ValidationException;
use Throwable;

/**
 * 密碼安全服務.
 *
 * 負責密碼強度驗證、安全性檢查、密碼生成等功能。
 * 實作完整的密碼安全策略，包括弱密碼檢測、重複字元檢查等。
 */
class PasswordSecurityService implements PasswordSecurityServiceInterface
{
    /**
     * 最小密碼長度.
     */
    private const MIN_LENGTH = 8;

    /**
     * 最大密碼長度.
     */
    private const MAX_LENGTH = 128;

    /**
     * 最小唯一字元數量.
     */
    private const MIN_UNIQUE_CHARS = 4;

    /**
     * 最大重複字元連續出現次數.
     */
    private const MAX_REPETITION = 3;

    /**
     * 常見弱密碼清單.
     */
    private const COMMON_PASSWORDS = [
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
        '000000',
        'iloveyou',
        'dragon',
        'sunshine',
        'princess',
        'password1',
        'rockyou',
        '12345678',
        'superman',
        'qwertyuiop',
        'trustno1',
        'passw0rd',
        'qwerty123',
        'zxcvbnm',
        'asdfgh',
        '654321',
        'master',
        'jordan',
        'harley',
        'ranger',
        'michelle',
        'charlie',
        'babygirl',
        'liverpool',
        'loveme',
        'shadow',
        'ashley',
        'blink182',
    ];

    public function hashPassword(string $password): string
    {
        $hash = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3,
        ]);

        if ($hash === false) {
            throw new ValidationException('密碼雜湊失敗');
        }

        return $hash;
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        try {
            return password_verify($password, $hash);
        } catch (Throwable $e) {
            error_log("Error in PasswordSecurityService.php: " . $e->getMessage());
            throw $e;
        }
    }

    public function isStrongPassword(string $password): bool
    {
        try {
            $this->validatePassword($password);

            return true;
        } catch (Throwable $e) {
            error_log("Error in PasswordSecurityService.php: " . $e->getMessage());
            throw $e;
        }
    }

    public function calculatePasswordStrength(string $password): int
    {
        $score = 0;

        // 基礎分數
        $score += min(strlen($password) * 2, 50);

        // 字元種類分數
        if (preg_match('/[a-z]/', $password)) {
            $score += 10;
        }
        if (preg_match('/[A-Z]/', $password)) {
            $score += 10;
        }
        if (preg_match('/[0-9]/', $password)) {
            $score += 10;
        }
        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            $score += 15;
        }

        // 複雜度分數
        $uniqueChars = count(array_unique(str_split($password)));
        $score += min($uniqueChars * 2, 20);

        // 懲罰項目
        if ($this->isCommonPassword($password)) {
            $score -= 30;
        }
        if ($this->hasExcessiveRepetition($password)) {
            $score -= 20;
        }
        if ($this->hasSequentialChars($password)) {
            $score -= 15;
        }

        return max(0, min(100, $score));
    }

    public function validatePassword(string $password): void
    {
        // 檢查長度
        if (strlen($password) < self::MIN_LENGTH) {
            throw ValidationException::fromSingleError(
                'password',
                '密碼長度不得少於 ' . self::MIN_LENGTH . ' 個字元',
            );
        }

        if (strlen($password) > self::MAX_LENGTH) {
            throw ValidationException::fromSingleError(
                'password',
                '密碼長度不得超過 ' . self::MAX_LENGTH . ' 個字元',
            );
        }

        // 檢查字元種類
        $hasLower = preg_match('/[a-z]/', $password);
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);

        $charTypes = (int) $hasLower + (int) $hasUpper + (int) $hasNumber + (int) $hasSpecial;

        if ($charTypes < 3) {
            throw ValidationException::fromSingleError(
                'password',
                '密碼必須包含至少3種字元類型（小寫字母、大寫字母、數字、特殊符號）',
            );
        }

        // 檢查唯一字元數量
        $uniqueChars = count(array_unique(str_split($password)));
        if ($uniqueChars < self::MIN_UNIQUE_CHARS) {
            throw ValidationException::fromSingleError(
                'password',
                '密碼至少需要包含 ' . self::MIN_UNIQUE_CHARS . ' 個不同的字元',
            );
        }

        // 檢查是否為常見弱密碼
        if ($this->isCommonPassword($password)) {
            throw ValidationException::fromSingleError(
                'password',
                '此密碼過於常見，請選擇更安全的密碼',
            );
        }

        // 檢查重複字元
        if ($this->hasExcessiveRepetition($password)) {
            throw ValidationException::fromSingleError(
                'password',
                '密碼不能包含過多重複字元',
            );
        }

        // 檢查順序字元
        if ($this->hasSequentialChars($password)) {
            throw ValidationException::fromSingleError(
                'password',
                '密碼不能包含連續的字元序列',
            );
        }
    }

    public function generateSecurePassword(int $length = 16): string
    {
        if ($length < self::MIN_LENGTH) {
            $length = self::MIN_LENGTH;
        }

        if ($length > self::MAX_LENGTH) {
            $length = self::MAX_LENGTH;
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
        $passwordArray = str_split($password);
        shuffle($passwordArray);
        $password = implode('', $passwordArray);

        // 驗證生成的密碼是否符合要求
        if (!$this->isStrongPassword($password)) {
            // 如果不符合要求，重新生成
            return $this->generateSecurePassword($length);
        }

        return $password;
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3,
        ]);
    }

    public function getPasswordPolicy(): array
    {
        return [
            'min_length' => self::MIN_LENGTH,
            'max_length' => self::MAX_LENGTH,
            'min_unique_chars' => self::MIN_UNIQUE_CHARS,
            'max_repetition' => self::MAX_REPETITION,
            'requirements' => [
                'lowercase' => true,
                'uppercase' => true,
                'numbers' => true,
                'special_chars' => true,
                'min_char_types' => 3,
            ],
            'prohibited' => [
                'common_passwords' => true,
                'excessive_repetition' => true,
                'sequential_chars' => true,
                'dictionary_words' => false, // 可擴展功能
            ],
        ];
    }

    /**
     * 檢查是否為常見密碼.
     */
    private function isCommonPassword(string $password): bool
    {
        $lowerPassword = strtolower($password);

        // 檢查完全匹配
        if (in_array($lowerPassword, array_map('strtolower', self::COMMON_PASSWORDS), true)) {
            return true;
        }

        // 檢查包含常見密碼作為子字串
        foreach (self::COMMON_PASSWORDS as $commonPassword) {
            if (str_contains($lowerPassword, strtolower($commonPassword))) {
                return true;
            }
        }

        return false;
    }

    /**
     * 檢查是否有過度重複的字元.
     */
    private function hasExcessiveRepetition(string $password): bool
    {
        $chars = str_split($password);
        $consecutiveCount = 1;
        $lastChar = '';

        foreach ($chars as $char) {
            if ($char === $lastChar) {
                $consecutiveCount++;
                if ($consecutiveCount > self::MAX_REPETITION) {
                    return true;
                }
            } else {
                $consecutiveCount = 1;
                $lastChar = $char;
            }
        }

        return false;
    }

    /**
     * 檢查是否包含順序字元.
     */
    private function hasSequentialChars(string $password): bool
    {
        $sequences = [
            'abcdefghijklmnopqrstuvwxyz',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '0123456789',
            'qwertyuiopasdfghjklzxcvbnm',
            'QWERTYUIOPASDFGHJKLZXCVBNM',
        ];

        foreach ($sequences as $sequence) {
            // 檢查正向和反向序列
            if ($this->containsSequence($password, $sequence)
                || $this->containsSequence($password, strrev($sequence))) {
                return true;
            }
        }

        return false;
    }

    /**
     * 檢查密碼是否包含指定序列.
     */
    private function containsSequence(string $password, string $sequence): bool
    {
        $minSequenceLength = 3;

        for ($i = 0; $i <= strlen($sequence) - $minSequenceLength; $i++) {
            $subSequence = substr($sequence, $i, $minSequenceLength);
            if (str_contains(strtolower($password), strtolower($subSequence))) {
                return true;
            }

            // 檢查更長的序列
            for ($len = $minSequenceLength + 1; $len <= 6 && $i + $len <= strlen($sequence); $len++) {
                $longerSequence = substr($sequence, $i, $len);
                if (str_contains(strtolower($password), strtolower($longerSequence))) {
                    return true;
                }
            }
        }

        return false;
    }
}
