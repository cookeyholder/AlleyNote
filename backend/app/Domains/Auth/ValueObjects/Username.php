<?php

declare(strict_types=1);

namespace App\Domains\Auth\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Username 值物件.
 *
 * 表示使用者名稱，提供驗證和格式化
 */
final readonly class Username implements JsonSerializable, Stringable
{
    private string $value;

    public function __construct(string $username)
    {
        $trimmedUsername = trim($username);

        if (empty($trimmedUsername)) {
            throw new InvalidArgumentException('使用者名稱不能為空');
        }

        if (mb_strlen($trimmedUsername) < 3) {
            throw new InvalidArgumentException('使用者名稱至少需要 3 個字元');
        }

        if (mb_strlen($trimmedUsername) > 50) {
            throw new InvalidArgumentException('使用者名稱不能超過 50 個字元');
        }

        // 只允許字母、數字、底線和連字號
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $trimmedUsername)) {
            throw new InvalidArgumentException('使用者名稱只能包含字母、數字、底線和連字號');
        }

        $this->value = $trimmedUsername;
    }

    /**
     * 從字串建立 Username.
     */
    public static function fromString(string $username): self
    {
        return new self($username);
    }

    /**
     * 取得使用者名稱.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * 取得長度.
     */
    public function getLength(): int
    {
        return mb_strlen($this->value);
    }

    /**
     * 轉換為小寫.
     */
    public function toLowercase(): string
    {
        return strtolower($this->value);
    }

    /**
     * 檢查是否與另一個 Username 相等.
     */
    public function equals(Username $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * 檢查是否與指定字串相等（不區分大小寫）.
     */
    public function equalsIgnoreCase(string $username): bool
    {
        return strcasecmp($this->value, $username) === 0;
    }

    /**
     * 轉換為字串.
     */
    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * JSON 序列化.
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
            'username' => $this->value,
            'length' => $this->getLength(),
        ];
    }
}
