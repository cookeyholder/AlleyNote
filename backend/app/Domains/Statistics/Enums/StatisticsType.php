<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Enums;

/**
 * 統計類型枚舉.
 *
 * 定義系統支援的統計資料類型
 */
enum StatisticsType: string
{
    case OVERVIEW = 'overview';
    case POSTS = 'posts';
    case SOURCES = 'sources';
    case USERS = 'users';
    case POPULAR = 'popular';

    /**
     * 取得類型的顯示名稱.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::OVERVIEW => '概覽統計',
            self::POSTS => '文章統計',
            self::SOURCES => '來源統計',
            self::USERS => '使用者統計',
            self::POPULAR => '熱門統計',
        };
    }

    /**
     * 取得類型的描述.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::OVERVIEW => '整體系統統計概覽',
            self::POSTS => '文章相關統計資料',
            self::SOURCES => '來源分布統計資料',
            self::USERS => '使用者活動統計資料',
            self::POPULAR => '熱門內容統計資料',
        };
    }

    /**
     * 檢查是否為內容相關的統計類型.
     */
    public function isContentRelated(): bool
    {
        return match ($this) {
            self::POSTS, self::POPULAR => true,
            default => false,
        };
    }

    /**
     * 檢查是否為使用者相關的統計類型.
     */
    public function isUserRelated(): bool
    {
        return match ($this) {
            self::USERS => true,
            default => false,
        };
    }

    /**
     * 取得所有類型的值.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * 取得所有類型的標籤對應.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->getLabel();
        }

        return $labels;
    }
}
