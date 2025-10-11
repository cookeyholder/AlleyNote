<?php

declare(strict_types=1);

namespace App\Shared\Services;

/**
 * 密碼驗證服務.
 *
 * 提供密碼強度驗證和建議
 */
class PasswordValidationService
{
    /**
     * 驗證密碼並回傳詳細結果.
     */
    public function validate(
        string $password,
        ?string $username = null,
        ?string $email = null,
    ): array {
        $errors = [];
        $warnings = [];
        $score = 0;

        // 長度檢查
        if (strlen($password) < 8) {
            $errors[] = '密碼長度至少需要 8 個字元';
        } else {
            $score += 20;
        }

        if (strlen($password) > 128) {
            $errors[] = '密碼長度不能超過 128 個字元';
        }

        // 包含小寫字母
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = '密碼必須包含至少一個小寫字母';
        } else {
            $score += 15;
        }

        // 包含大寫字母
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = '密碼必須包含至少一個大寫字母';
        } else {
            $score += 15;
        }

        // 包含數字
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = '密碼必須包含至少一個數字';
        } else {
            $score += 15;
        }

        // 包含特殊符號（加分項）
        if (preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
            $score += 20;
        } else {
            $warnings[] = '建議包含至少一個特殊符號以增加安全性';
        }

        // 連續字元檢查
        if ($this->hasSequentialChars($password)) {
            $errors[] = '密碼不能包含連續的英文字母或數字（如 abc, 123）';
            $score -= 10;
        }

        // 重複字元檢查
        if ($this->hasRepeatingChars($password)) {
            $errors[] = '密碼不能包含重複的字元（如 aaa, 111）';
            $score -= 10;
        }

        // 常見密碼檢查
        if ($this->isCommonPassword($password)) {
            $errors[] = '此密碼過於常見，請使用更安全的密碼';
            $score -= 20;
        }

        // 個人資訊檢查
        if ($this->containsPersonalInfo($password, $username, $email)) {
            $errors[] = '密碼不能包含使用者名稱或電子郵件';
            $score -= 15;
        }

        // 長度加分
        if (strlen($password) >= 12) {
            $score += 10;
        }
        if (strlen($password) >= 16) {
            $score += 10;
        }

        // 確保分數在 0-100 之間
        $score = max(0, min(100, $score));

        return [
            'is_valid' => empty($errors),
            'score' => $score,
            'strength' => $this->getStrengthLevel($score),
            'errors' => $errors,
            'warnings' => $warnings,
            'suggestions' => $this->getSuggestions($errors, $warnings),
        ];
    }

    /**
     * 檢查是否包含連續字元.
     */
    private function hasSequentialChars(string $password): bool
    {
        $lower = strtolower($password);
        $length = strlen($lower);

        for ($i = 0; $i < $length - 2; $i++) {
            $char1 = ord($lower[$i]);
            $char2 = ord($lower[$i + 1]);
            $char3 = ord($lower[$i + 2]);

            if (
                ($char2 === $char1 + 1 && $char3 === $char2 + 1)
                || ($char2 === $char1 - 1 && $char3 === $char2 - 1)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * 檢查是否包含重複字元.
     */
    private function hasRepeatingChars(string $password): bool
    {
        return preg_match('/(.)\1{2,}/', $password) === 1;
    }

    /**
     * 檢查是否為常見密碼
     */
    private function isCommonPassword(string $password): bool
    {
        /** @var array<string>|null */
        static $commonPasswords = null;

        if ($commonPasswords === null) {
            $file = __DIR__ . '/../../../resources/data/common-passwords.txt';
            if (file_exists($file)) {
                $passwords = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $commonPasswords = $passwords !== false
                    ? array_map('strtolower', array_map('trim', $passwords))
                    : [];
            } else {
                $commonPasswords = [];
            }
        }

        return in_array(strtolower($password), $commonPasswords, true);
    }

    /**
     * 檢查是否包含個人資訊.
     */
    private function containsPersonalInfo(
        string $password,
        ?string $username,
        ?string $email,
    ): bool {
        $lower = strtolower($password);

        if ($username && strlen($username) >= 3) {
            if (str_contains($lower, strtolower($username))) {
                return true;
            }
        }

        if ($email) {
            $emailParts = explode('@', $email);
            if (isset($emailParts[0]) && strlen($emailParts[0]) >= 3) {
                if (str_contains($lower, strtolower($emailParts[0]))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 獲取強度等級.
     */
    private function getStrengthLevel(int $score): string
    {
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

    /**
     * 獲取建議.
     *
     * @param array<string> $errors
     * @param array<string> $warnings
     * @return array<string>
     */
    private function getSuggestions(array $errors, array $warnings): array
    {
        $suggestions = [];

        foreach ($errors as $error) {
            if (is_string($error) && str_contains($error, '長度')) {
                $suggestions[] = '使用更長的密碼（建議 12 個字元以上）';
                break;
            }
        }

        foreach ($errors as $error) {
            if (is_string($error) && (str_contains($error, '字母') || str_contains($error, '數字'))) {
                $suggestions[] = '混合使用大小寫字母、數字和特殊符號';
                break;
            }
        }

        foreach ($errors as $error) {
            if (is_string($error) && (str_contains($error, '連續') || str_contains($error, '重複'))) {
                $suggestions[] = '避免使用簡單的模式或重複字元';
                break;
            }
        }

        foreach ($errors as $error) {
            if (is_string($error) && str_contains($error, '常見')) {
                $suggestions[] = '使用獨特的密碼組合，不要使用常見單字';
                break;
            }
        }

        if (empty($suggestions) && !empty($warnings)) {
            $suggestions[] = '已經很好！可以加入特殊符號讓密碼更安全';
        }

        return array_values(array_unique($suggestions));
    }
}
