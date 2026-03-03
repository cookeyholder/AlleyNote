<?php

declare(strict_types=1);

namespace App\Shared\ValueObjects;

use App\Shared\Exceptions\ValidationException;

/**
 * 安全密碼值物件.
 *
 * 確保密碼符合以下安全要求：
 * - 至少 8 個字元
 * - 包含大小寫字母
 * - 包含數字
 * - 不能是常見密碼
 * - 不能包含連續字元
 * - 不能包含重複字元
 * - 不能包含使用者資訊
 */
final class SecurePassword
{
    private const MIN_LENGTH = 8;

    private const MAX_LENGTH = 128;

    private static ?array $commonPasswords = null;

    private static ?array $commonWords = null;

    public function __construct(
        private readonly string $value,
        private readonly ?string $username = null,
        private readonly ?string $email = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        $errors = [];

        // 長度檢查
        if (strlen($this->value) < self::MIN_LENGTH) {
            $errors[] = sprintf('密碼長度至少需要 %d 個字元', self::MIN_LENGTH);
        }

        if (strlen($this->value) > self::MAX_LENGTH) {
            $errors[] = sprintf('密碼長度不能超過 %d 個字元', self::MAX_LENGTH);
        }

        // 字母數字檢查
        if (!preg_match('/[a-z]/', $this->value)) {
            $errors[] = '密碼必須包含至少一個小寫字母';
        }

        if (!preg_match('/[A-Z]/', $this->value)) {
            $errors[] = '密碼必須包含至少一個大寫字母';
        }

        if (!preg_match('/[0-9]/', $this->value)) {
            $errors[] = '密碼必須包含至少一個數字';
        }

        // 連續字元檢查
        if ($this->hasSequentialChars()) {
            $errors[] = '密碼不能包含連續的英文字母或數字（如 abc, 123）';
        }

        // 重複字元檢查
        if ($this->hasRepeatingChars()) {
            $errors[] = '密碼不能包含重複的字元（如 aaa, 111）';
        }

        // 常見密碼檢查
        if ($this->isCommonPassword()) {
            $errors[] = '此密碼過於常見，請使用更安全的密碼';
        }

        // 常見單字檢查
        if ($this->containsCommonWord()) {
            $errors[] = '密碼不能包含常見的英文單字';
        }

        // 個人資訊檢查
        if ($this->containsPersonalInfo()) {
            $errors[] = '密碼不能包含使用者名稱或電子郵件';
        }

        if (!empty($errors)) {
            throw ValidationException::fromMultipleErrors(['password' => $errors]);
        }
    }

    /**
     * 檢查是否包含連續字元.
     */
    private function hasSequentialChars(): bool
    {
        $length = strlen($this->value);

        for ($i = 0; $i < $length - 2; $i++) {
            $char1 = $this->value[$i];
            $char2 = $this->value[$i + 1];
            $char3 = $this->value[$i + 2];

            // 只檢查字母和數字的連續
            if (!ctype_alnum($char1) || !ctype_alnum($char2) || !ctype_alnum($char3)) {
                continue;
            }

            $ord1 = ord($char1);
            $ord2 = ord($char2);
            $ord3 = ord($char3);

            // 檢查連續遞增或遞減
            if (
                ($ord2 === $ord1 + 1 && $ord3 === $ord2 + 1)
                || ($ord2 === $ord1 - 1 && $ord3 === $ord2 - 1)
            ) {
                return true;
            }
        }

        // 也檢查小寫版本的字母序列（處理大小寫混合的情況）
        $lower = strtolower($this->value);
        for ($i = 0; $i < $length - 2; $i++) {
            $char1 = $lower[$i];
            $char2 = $lower[$i + 1];
            $char3 = $lower[$i + 2];

            // 只檢查字母的連續
            if (!ctype_alpha($char1) || !ctype_alpha($char2) || !ctype_alpha($char3)) {
                continue;
            }

            // 如果原始密碼在這個位置也是連續的，跳過（避免重複檢測）
            if (ctype_alnum($this->value[$i]) && ctype_alnum($this->value[$i + 1]) && ctype_alnum($this->value[$i + 2])) {
                $origOrd1 = ord($this->value[$i]);
                $origOrd2 = ord($this->value[$i + 1]);
                $origOrd3 = ord($this->value[$i + 2]);
                if (
                    ($origOrd2 === $origOrd1 + 1 && $origOrd3 === $origOrd2 + 1)
                    || ($origOrd2 === $origOrd1 - 1 && $origOrd3 === $origOrd2 - 1)
                ) {
                    continue;
                }
            }

            $ord1 = ord($char1);
            $ord2 = ord($char2);
            $ord3 = ord($char3);

            if (
                ($ord2 === $ord1 + 1 && $ord3 === $ord2 + 1)
                || ($ord2 === $ord1 - 1 && $ord3 === $ord2 - 1)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * 檢查是否包含重複字元.
     */
    private function hasRepeatingChars(): bool
    {
        // 檢查 3+ 個相同字元
        return preg_match('/(.)\\1{2,}/', $this->value) === 1;
    }

    /**
     * 檢查是否為常見密碼
     */
    private function isCommonPassword(): bool
    {
        if (self::$commonPasswords === null) {
            self::$commonPasswords = $this->loadCommonPasswords();
        }

        return in_array(strtolower($this->value), self::$commonPasswords, true);
    }

    /**
     * 檢查是否包含常見單字.
     */
    private function containsCommonWord(): bool
    {
        if (self::$commonWords === null) {
            self::$commonWords = $this->loadCommonWords();
        }

        $lower = strtolower($this->value);

        foreach (self::$commonWords as $word) {
            if (is_string($word) && strlen($word) >= 4 && str_contains($lower, $word)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 檢查是否包含個人資訊.
     */
    private function containsPersonalInfo(): bool
    {
        $lower = strtolower($this->value);

        if ($this->username && strlen($this->username) >= 3) {
            if (str_contains($lower, strtolower($this->username))) {
                return true;
            }
        }

        if ($this->email) {
            $emailParts = explode('@', $this->email);
            if (isset($emailParts[0]) && strlen($emailParts[0]) >= 3) {
                if (str_contains($lower, strtolower($emailParts[0]))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 載入常見密碼列表.
     */
    private function loadCommonPasswords(): array
    {
        $file = __DIR__ . '/../../../resources/data/common-passwords.txt';

        if (!file_exists($file)) {
            return [];
        }

        $passwords = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($passwords === false) {
            return [];
        }

        return array_map('strtolower', array_map('trim', $passwords));
    }

    /**
     * 載入常見單字列表.
     */
    private function loadCommonWords(): array
    {
        $file = __DIR__ . '/../../../resources/data/common-words.txt';

        if (!file_exists($file)) {
            return [];
        }

        $words = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($words === false) {
            return [];
        }

        return array_map('strtolower', array_map('trim', $words));
    }

    /**
     * 計算密碼強度分數 (0-100).
     */
    public function calculateScore(): int
    {
        $score = 0;

        // 長度分數
        $length = strlen($this->value);
        if ($length >= 8) {
            $score += 20;
        }
        if ($length >= 12) {
            $score += 10;
        }
        if ($length >= 16) {
            $score += 10;
        }

        // 字元類型分數
        if (preg_match('/[a-z]/', $this->value)) {
            $score += 15;
        }
        if (preg_match('/[A-Z]/', $this->value)) {
            $score += 15;
        }
        if (preg_match('/[0-9]/', $this->value)) {
            $score += 15;
        }
        if (preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $this->value)) {
            $score += 15;
        }

        // 扣分項
        if ($this->hasSequentialChars()) {
            $score -= 10;
        }
        if ($this->hasRepeatingChars()) {
            $score -= 10;
        }

        return max(0, min(100, $score));
    }

    /**
     * 獲取密碼強度等級.
     */
    public function getStrengthLevel(): string
    {
        $score = $this->calculateScore();

        if ($score >= 80) {
            return 'very-strong';
        }
        if ($score >= 60) {
            return 'strong';
        }
        if ($score >= 40) {
            return 'medium';
        }
        if ($score >= 20) {
            return 'weak';
        }

        return 'very-weak';
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
