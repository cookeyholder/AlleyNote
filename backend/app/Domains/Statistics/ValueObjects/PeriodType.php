<?php

declare(strict_types=1);

namespace App\Domains\Statistics\ValueObjects;

/**
 * 統計週期類型枚舉.
 *
 * 定義系統支援的統計時間週期類型。
 */
enum PeriodType: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    /**
     * 取得週期類型的中文名稱.
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::DAILY => '日統計',
            self::WEEKLY => '週統計',
            self::MONTHLY => '月統計',
            self::YEARLY => '年統計',
        };
    }

    /**
     * 取得週期的排序權重（用於排序顯示）.
     */
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
     * 取得所有可用的週期類型.
     *
     * @return array<PeriodType>
     */
    public static function getAllTypes(): array
    {
        return [
            self::DAILY,
            self::WEEKLY,
            self::MONTHLY,
            self::YEARLY,
        ];
    }

    /**
     * 取得所有週期類型的字串值.
     *
     * @return array<string>
     */
    public static function getAllValues(): array
    {
        return array_map(static fn(PeriodType $type): string => $type->value, self::getAllTypes());
    }
}
