<?php

declare(strict_types=1);

namespace App\Domains\Post\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * PostSlug 值物件.
 *
 * 表示文章的 URL slug，確保符合 URL 規範
 */
final readonly class PostSlug implements JsonSerializable, Stringable
{
    private string $value;

    public function __construct(string $slug)
    {
        $trimmedSlug = trim($slug);

        if (empty($trimmedSlug)) {
            throw new InvalidArgumentException('Slug 不能為空');
        }

        // 驗證 slug 格式：允許 Unicode 字母、數字和連字號
        if (!preg_match('/^[\p{L}\p{N}]+([-][\p{L}\p{N}]+)*$/u', $trimmedSlug)) {
            throw new InvalidArgumentException('Slug 只能包含字母、數字和連字號，且不能以連字號開頭或結尾');
        }

        if (mb_strlen($trimmedSlug) > 255) {
            throw new InvalidArgumentException('Slug 不能超過 255 個字元');
        }

        $this->value = $trimmedSlug;
    }

    /**
     * 從字串建立 PostSlug.
     */
    public static function fromString(string $slug): self
    {
        return new self($slug);
    }

    /**
     * 從標題自動產生 slug.
     */
    public static function fromTitle(string $title): self
    {
        // 轉換為小寫並替換空格為連字號
        $slug = trim($title);

        // 嘗試轉譯中文為拼音（如果有可能）或保留原文
        // 移除特殊字符，但保留中文、數字、字母和空格
        $result = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $slug);

        if ($result === null) {
            throw new InvalidArgumentException('無法處理標題字串');
        }

        $slug = $result;
        $result2 = preg_replace('/[\s-]+/', '-', $slug);

        if ($result2 === null) {
            throw new InvalidArgumentException('無法處理標題字串');
        }

        $slug = trim($result2, '-');

        if (empty($slug)) {
            throw new InvalidArgumentException('無法從標題產生有效的 slug');
        }

        return new self(strtolower($slug));
    }

    /**
     * 取得 slug 值.
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
     * 檢查是否與另一個 PostSlug 相等.
     */
    public function equals(PostSlug $other): bool
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
            'slug' => $this->value,
            'length' => $this->getLength(),
        ];
    }
}
