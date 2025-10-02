<?php

declare(strict_types=1);

namespace App\Domains\Post\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * PostContent 值物件.
 *
 * 表示文章內容，提供內容驗證和處理邏輯
 */
final readonly class PostContent implements JsonSerializable, Stringable
{
    private string $value;

    public function __construct(string $content)
    {
        $trimmedContent = trim($content);

        if (empty($trimmedContent)) {
            throw new InvalidArgumentException('文章內容不能為空');
        }

        if (mb_strlen($trimmedContent) > 1000000) { // 1MB 文字限制
            throw new InvalidArgumentException('文章內容不能超過 1,000,000 個字元');
        }

        $this->value = $trimmedContent;
    }

    /**
     * 從字串建立 PostContent.
     */
    public static function fromString(string $content): self
    {
        return new self($content);
    }

    /**
     * 取得內容值.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * 取得內容長度.
     */
    public function getLength(): int
    {
        return mb_strlen($this->value);
    }

    /**
     * 取得內容的摘要.
     */
    public function getExcerpt(int $length = 200): string
    {
        if ($this->getLength() <= $length) {
            return $this->value;
        }

        return mb_substr($this->value, 0, $length) . '...';
    }

    /**
     * 檢查內容是否包含特定文字.
     */
    public function contains(string $needle): bool
    {
        return str_contains($this->value, $needle);
    }

    /**
     * 計算字數（以空格分隔）.
     */
    public function getWordCount(): int
    {
        return str_word_count(strip_tags($this->value));
    }

    /**
     * 檢查內容是否為空.
     */
    public function isEmpty(): bool
    {
        return empty($this->value);
    }

    /**
     * 檢查是否與另一個 PostContent 相等.
     */
    public function equals(PostContent $other): bool
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
            'content' => $this->value,
            'length' => $this->getLength(),
            'word_count' => $this->getWordCount(),
            'excerpt' => $this->getExcerpt(),
        ];
    }
}
