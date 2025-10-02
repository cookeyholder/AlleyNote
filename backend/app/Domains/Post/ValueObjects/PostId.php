<?php

declare(strict_types=1);

namespace App\Domains\Post\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * PostId 值物件.
 *
 * 表示文章的唯一識別符
 */
final readonly class PostId implements JsonSerializable, Stringable
{
    private int $value;

    public function __construct(int $id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('文章 ID 必須是正整數');
        }

        $this->value = $id;
    }

    /**
     * 從整數建立 PostId.
     */
    public static function fromInt(int $id): self
    {
        return new self($id);
    }

    /**
     * 從字串建立 PostId.
     */
    public static function fromString(string $id): self
    {
        if (!is_numeric($id)) {
            throw new InvalidArgumentException('文章 ID 必須是數字');
        }

        return new self((int) $id);
    }

    /**
     * 取得 ID 值.
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * 檢查是否與另一個 PostId 相等.
     */
    public function equals(PostId $other): bool
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

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * JSON 序列化.
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
            'post_id' => $this->value,
        ];
    }
}
