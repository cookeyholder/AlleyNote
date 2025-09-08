<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Enums;

/**
 * 統計週期類型枚舉
 * 定義統計資料的時間範圍類型.
 */
enum PeriodType: string
{
    case DAILY = 'daily';        // 日統計
    case WEEKLY = 'weekly';      // 週統計
    case MONTHLY = 'monthly';    // 月統計
    case YEARLY = 'yearly';      // 年統計

    /**
     * 取得週期類型顯示名稱.
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
     * 取得週期類型描述.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::DAILY => '每日統計資料，涵蓋單日內的所有活動',
            self::WEEKLY => '每週統計資料，涵蓋一週內的所有活動',
            self::MONTHLY => '每月統計資料，涵蓋一個月內的所有活動',
            self::YEARLY => '每年統計資料，涵蓋一年內的所有活動',
        };
    }

    /**
     * 取得預設快取時間（秒）.
     */
    public function getDefaultCacheTtl(): int
    {
        return match ($this) {
            self::DAILY => 6 * 3600,        // 6 小時
            self::WEEKLY => 24 * 3600,      // 1 天
            self::MONTHLY => 7 * 24 * 3600, // 7 天
            self::YEARLY => 30 * 24 * 3600, // 30 天
        };
    }

    /**
     * 判斷是否為短期統計（日/週）.
     */
    public function isShortTerm(): bool
    {
        return in_array($this, [self::DAILY, self::WEEKLY], true);
    }

    /**
     * 判斷是否為長期統計（月/年）.
     */
    public function isLongTerm(): bool
    {
        return in_array($this, [self::MONTHLY, self::YEARLY], true);
    }

    /**
     * 取得所有週期類型.
     */
    public static function getAllTypes(): array
    {
        return self::cases();
    }

    /**
     * 根據字串值取得對應的週期類型.
     */
    public static function fromValue(string $value): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        return null;
    }
}
