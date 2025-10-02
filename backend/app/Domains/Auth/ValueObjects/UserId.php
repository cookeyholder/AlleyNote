<?php

declare(strict_types=1);

namespace App\Domains\Auth\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * UserId 值物件.
 *
 * 表示使用者唯一識別符
 */
final readonly class UserId implements JsonSerializable, Stringable
{
    private int $value;

    public function __construct(int $userId)
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('使用者 ID 必須是正整數');
        }

        $this->value = $userId;
    }

    /**
     * 從整數建立 UserId.
     */
    public static function fromInt(int $userId): self
    {
        return new self($userId);
    }

    /**
     * 從字串建立 UserId.
     */
    public static function fromString(string $userId): self
    {
        if (!is_numeric($userId)) {
            throw new InvalidArgumentException('使用者 ID 必須是數字');
        }

        return new self((int) $userId);
    }

    /**
     * 取得使用者 ID 值
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * 檢查是否與另一個 UserId 相等.
     */
    public function equals(UserId $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * 轉換為字串.
     */
    public function toString(): string
    {
        return (string) $this->value;
    }

    /**
     * __toString 魔術方法.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * JsonSerializable 實作.
     */
    public function jsonSerialize(): int
    {
        return $this->value;
    }

    /**
     * 轉換為陣列.
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->value,
        ];
    }
}
