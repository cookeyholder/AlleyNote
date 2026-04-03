<?php

declare(strict_types=1);

namespace App\Domains\Security\Enums;

enum ActivitySeverity: int
{
    case LOW = 1;
    case NORMAL = 2;
    case MEDIUM = 3;
    case HIGH = 4;
    case CRITICAL = 5;

    public function getDisplayName(): string
    {
        return match ($this) {
            self::LOW => '低',
            self::NORMAL => '正常',
            self::MEDIUM => '中等',
            self::HIGH => '高',
            self::CRITICAL => '關鍵',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::LOW => '一般性操作，對系統影響很小',
            self::NORMAL => '標準操作，對系統有正常影響',
            self::MEDIUM => '中等重要操作，需要留意',
            self::HIGH => '高重要性操作，需要特別關注',
            self::CRITICAL => '關鍵操作，對系統安全有重大影響',
        };
    }

    public function isAtLeast(self $other): bool
    {
        return $this->value >= $other->value;
    }

    public function isAtMost(self $other): bool
    {
        return $this->value <= $other->value;
    }

    public function isHighRisk(): bool
    {
        return $this->isAtLeast(self::HIGH);
    }

    public function isLowRisk(): bool
    {
        return $this->isAtMost(self::NORMAL);
    }

    /**
     * @return list<self>
     */
    public static function getAllLevels(): array
    {
        return self::cases();
    }

    public static function fromValue(int $value): ?self
    {
        return self::tryFrom($value);
    }
}
