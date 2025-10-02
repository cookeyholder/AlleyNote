<?php

declare(strict_types=1);

namespace App\Domains\Auth\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Password 值物件.
 *
 * 表示使用者密碼，提供驗證和安全處理
 */
final readonly class Password implements JsonSerializable
{
    private string $hashedValue;

    private function __construct(string $hashedValue)
    {
        $this->hashedValue = $hashedValue;
    }

    /**
     * 從明文密碼建立 Password.
     */
    public static function fromPlainText(string $plainPassword): self
    {
        if (empty(trim($plainPassword))) {
            throw new InvalidArgumentException('密碼不能為空');
        }

        if (mb_strlen($plainPassword) < 8) {
            throw new InvalidArgumentException('密碼至少需要 8 個字元');
        }

        if (mb_strlen($plainPassword) > 100) {
            throw new InvalidArgumentException('密碼不能超過 100 個字元');
        }

        // 檢查密碼強度
        self::validatePasswordStrength($plainPassword);

        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        return new self($hashedPassword);
    }

    /**
     * 從已雜湊的密碼建立 Password.
     */
    public static function fromHash(string $hashedPassword): self
    {
        if (empty($hashedPassword)) {
            throw new InvalidArgumentException('雜湊密碼不能為空');
        }

        return new self($hashedPassword);
    }

    /**
     * 驗證密碼強度.
     */
    private static function validatePasswordStrength(string $password): void
    {
        $hasUpperCase = preg_match('/[A-Z]/', $password);
        $hasLowerCase = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);

        if (!$hasUpperCase || !$hasLowerCase || !$hasNumber) {
            throw new InvalidArgumentException(
                '密碼必須包含至少一個大寫字母、一個小寫字母和一個數字',
            );
        }
    }

    /**
     * 取得雜湊後的密碼.
     */
    public function getHash(): string
    {
        return $this->hashedValue;
    }

    /**
     * 驗證明文密碼是否匹配.
     */
    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->hashedValue);
    }

    /**
     * 檢查密碼是否需要重新雜湊.
     */
    public function needsRehash(): bool
    {
        return password_needs_rehash($this->hashedValue, PASSWORD_DEFAULT);
    }

    /**
     * 重新雜湊密碼（如果需要）.
     */
    public function rehash(string $plainPassword): self
    {
        if (!$this->verify($plainPassword)) {
            throw new InvalidArgumentException('密碼驗證失敗，無法重新雜湊');
        }

        return self::fromPlainText($plainPassword);
    }

    /**
     * JSON 序列化（不暴露密碼）.
     */
    public function jsonSerialize(): string
    {
        return '********';
    }

    /**
     * 轉換為陣列（不暴露密碼）.
     */
    public function toArray(): array
    {
        return [
            'password' => '********',
            'algorithm' => password_get_info($this->hashedValue)['algoName'] ?? 'unknown',
        ];
    }
}
