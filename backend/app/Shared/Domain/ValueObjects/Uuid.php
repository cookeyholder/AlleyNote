<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObjects;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Ramsey\Uuid\UuidInterface;
use Stringable;

/**
 * UUID 值物件
 * 封裝 UUID 識別碼的值物件.
 */
readonly class Uuid implements Stringable
{
    private function __construct(
        private UuidInterface $value,
    ) {}

    /**
     * 產生新的 UUID.
     */
    public static function generate(): self
    {
        return new self(RamseyUuid::uuid4());
    }

    /**
     * 從字串建立 UUID.
     */
    public static function fromString(string $uuid): self
    {
        if (!RamseyUuid::isValid($uuid)) {
            throw new InvalidArgumentException("Invalid UUID format: {$uuid}");
        }

        return new self(RamseyUuid::fromString($uuid));
    }

    /**
     * 取得 UUID 字串表示.
     */
    public function toString(): string
    {
        return $this->value->toString();
    }

    /**
     * 取得原始 UUID 物件.
     */
    public function getValue(): UuidInterface
    {
        return $this->value;
    }

    /**
     * 比較兩個 UUID 是否相等.
     */
    public function equals(Uuid $other): bool
    {
        return $this->value->equals($other->value);
    }

    /**
     * 轉換為字串.
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
