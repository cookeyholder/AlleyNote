<?php

declare(strict_types=1);

namespace App\Domains\Shared\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Email 值物件.
 *
 * 表示有效的電子郵件地址，確保電子郵件格式的正確性和一致性
 */
final readonly class Email implements JsonSerializable, Stringable
{
    private string $value;

    public function __construct(string $email)
    {
        $trimmedEmail = trim($email);

        if (empty($trimmedEmail)) {
            throw new InvalidArgumentException('Email 不能為空');
        }

        if (!filter_var($trimmedEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("無效的 Email 格式: {$trimmedEmail}");
        }

        // 檢查長度限制
        if (strlen($trimmedEmail) > 254) {
            throw new InvalidArgumentException('Email 長度不能超過 254 個字元');
        }

        $this->value = strtolower($trimmedEmail); // 統一轉換為小寫
    }

    /**
     * 從字串建立 Email.
     */
    public static function fromString(string $email): self
    {
        return new self($email);
    }

    /**
     * 取得 Email 值
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * 取得本地部分（@之前的部分）.
     */
    public function getLocalPart(): string
    {
        $parts = explode('@', $this->value);

        return $parts[0] ?? '';
    }

    /**
     * 取得網域部分（@之後的部分）.
     */
    public function getDomain(): string
    {
        $parts = explode('@', $this->value);

        return $parts[1] ?? '';
    }

    /**
     * 檢查是否與另一個 Email 相等.
     */
    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * 遮罩 Email 用於顯示（如：u***@example.com）
     */
    public function mask(): string
    {
        $parts = explode('@', $this->value);
        $local = $parts[0] ?? '';
        $domain = $parts[1] ?? '';

        if (strlen($local) <= 2) {
            return $local[0] . '***@' . $domain;
        }

        return $local[0] . '***' . substr($local, -1) . '@' . $domain;
    }

    /**
     * 轉換為字串.
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * __toString 魔術方法.
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * JsonSerializable 實作.
     */
    public function jsonSerialize(): string
    {
        return $this->value;
    }

    /**
     * 轉換為陣列.
     */
    public function toArray(): array
    {
        return [
            'email' => $this->value,
            'local_part' => $this->getLocalPart(),
            'domain' => $this->getDomain(),
        ];
    }
}
