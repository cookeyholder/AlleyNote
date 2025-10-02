<?php

declare(strict_types=1);

namespace App\Domains\Post\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * ViewCount 值物件.
 *
 * 表示文章的瀏覽次數
 */
final readonly class ViewCount implements JsonSerializable, Stringable
{
    private int $value;

    public function __construct(int $count)
    {
        if ($count < 0) {
            throw new InvalidArgumentException('瀏覽次數不能為負數');
        }

        $this->value = $count;
    }

    /**
     * 從整數建立 ViewCount.
     */
    public static function fromInt(int $count): self
    {
        return new self($count);
    }

    /**
     * 建立零瀏覽次數.
     */
    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * 取得瀏覽次數.
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * 增加瀏覽次數.
     */
    public function increment(int $amount = 1): self
    {
        if ($amount < 1) {
            throw new InvalidArgumentException('增加數量必須至少為 1');
        }

        return new self($this->value + $amount);
    }

    /**
     * 檢查是否為零.
     */
    public function isZero(): bool
    {
        return $this->value === 0;
    }

    /**
     * 檢查是否超過指定值.
     */
    public function isGreaterThan(int $threshold): bool
    {
        return $this->value > $threshold;
    }

    /**
     * 檢查是否小於指定值.
     */
    public function isLessThan(int $threshold): bool
    {
        return $this->value < $threshold;
    }

    /**
     * 檢查是否與另一個 ViewCount 相等.
     */
    public function equals(ViewCount $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * 格式化顯示（如 1.2K, 1.5M）.
     */
    public function format(): string
    {
        if ($this->value >= 1000000) {
            return number_format($this->value / 1000000, 1) . 'M';
        }

        if ($this->value >= 1000) {
            return number_format($this->value / 1000, 1) . 'K';
        }

        return (string) $this->value;
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
            'views' => $this->value,
            'formatted' => $this->format(),
        ];
    }
}
