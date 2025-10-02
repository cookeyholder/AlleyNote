<?php

declare(strict_types=1);

namespace App\Domains\Post\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * PostTitle 值物件.
 *
 * 表示文章標題，確保標題的有效性和一致性
 */
final readonly class PostTitle implements JsonSerializable, Stringable
{
    private const MIN_LENGTH = 1;

    private const MAX_LENGTH = 255;

    private string $value;

    public function __construct(string $title)
    {
        $trimmedTitle = trim($title);

        if (empty($trimmedTitle)) {
            throw new InvalidArgumentException('文章標題不能為空');
        }

        $length = mb_strlen($trimmedTitle, 'UTF-8');

        if ($length < self::MIN_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('文章標題長度不能少於 %d 個字元', self::MIN_LENGTH),
            );
        }

        if ($length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('文章標題長度不能超過 %d 個字元', self::MAX_LENGTH),
            );
        }

        // 檢查是否包含有效內容（不只是空白字元或特殊字符）
        if (!preg_match('/[\p{L}\p{N}]/u', $trimmedTitle)) {
            throw new InvalidArgumentException('文章標題必須包含有效的字母或數字');
        }

        $this->value = $trimmedTitle;
    }

    /**
     * 從字串建立 PostTitle.
     */
    public static function fromString(string $title): self
    {
        return new self($title);
    }

    /**
     * 取得標題值
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * 取得標題長度.
     */
    public function getLength(): int
    {
        return mb_strlen($this->value, 'UTF-8');
    }

    /**
     * 取得截斷的標題（用於預覽）.
     */
    public function truncate(int $length = 50, string $suffix = '...'): string
    {
        if ($this->getLength() <= $length) {
            return $this->value;
        }

        return mb_substr($this->value, 0, $length, 'UTF-8') . $suffix;
    }

    /**
     * 檢查是否與另一個 PostTitle 相等.
     */
    public function equals(PostTitle $other): bool
    {
        return $this->value === $other->value;
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
            'title' => $this->value,
            'length' => $this->getLength(),
        ];
    }
}
