<?php

declare(strict_types=1);

namespace App\Domains\Statistics\ValueObjects;

enum PeriodType: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::DAILY => '日統計',
            self::WEEKLY => '週統計',
            self::MONTHLY => '月統計',
            self::YEARLY => '年統計',
        };
    }

    public function getSortOrder(): int
    {
        return match ($this) {
            self::DAILY => 1,
            self::WEEKLY => 2,
            self::MONTHLY => 3,
            self::YEARLY => 4,
        };
    }

    /**
     * @return list<self>
     */
    public static function getAllTypes(): array
    {
        return self::cases();
    }

    /**
     * @return list<string>
     */
    public static function getAllValues(): array
    {
        return array_map(static fn(self $type): string => $type->value, self::cases());
    }
}
