<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Enums;

/**
 * 統計來源類型枚舉
 * 定義統計資料的來源類型.
 */
enum SourceType: string
{
    case WEB = 'web';                      // 網頁瀏覽
    case MOBILE_APP = 'mobile_app';        // 行動應用程式
    case API = 'api';                      // API 存取
    case RSS_FEED = 'rss_feed';           // RSS 訂閱
    case EMAIL_NEWSLETTER = 'email';       // 電子報
    case SOCIAL_MEDIA = 'social_media';    // 社群媒體
    case SEARCH_ENGINE = 'search';         // 搜尋引擎
    case DIRECT = 'direct';                // 直接存取
    case REFERRAL = 'referral';            // 外部連結
    case UNKNOWN = 'unknown';              // 未知來源

    /**
     * 取得來源類型顯示名稱.
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::WEB => '網頁',
            self::MOBILE_APP => '行動應用程式',
            self::API => 'API',
            self::RSS_FEED => 'RSS 訂閱',
            self::EMAIL_NEWSLETTER => '電子報',
            self::SOCIAL_MEDIA => '社群媒體',
            self::SEARCH_ENGINE => '搜尋引擎',
            self::DIRECT => '直接存取',
            self::REFERRAL => '外部連結',
            self::UNKNOWN => '未知來源',
        };
    }

    /**
     * 取得來源類型描述.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::WEB => '透過網頁瀏覽器直接存取的流量',
            self::MOBILE_APP => '透過手機或平板應用程式的存取',
            self::API => '透過 API 介面的程式化存取',
            self::RSS_FEED => '透過 RSS 訂閱器的存取',
            self::EMAIL_NEWSLETTER => '透過電子報連結的存取',
            self::SOCIAL_MEDIA => '透過社群媒體平台的存取',
            self::SEARCH_ENGINE => '透過搜尋引擎結果的存取',
            self::DIRECT => '直接輸入網址或書籤的存取',
            self::REFERRAL => '透過其他網站連結的存取',
            self::UNKNOWN => '無法識別來源的存取',
        };
    }

    /**
     * 取得來源類型優先順序（1最高，10最低）.
     */
    public function getPriority(): int
    {
        return match ($this) {
            self::WEB => 1,
            self::MOBILE_APP => 2,
            self::SEARCH_ENGINE => 3,
            self::SOCIAL_MEDIA => 4,
            self::DIRECT => 5,
            self::REFERRAL => 6,
            self::API => 7,
            self::RSS_FEED => 8,
            self::EMAIL_NEWSLETTER => 9,
            self::UNKNOWN => 10,
        };
    }

    /**
     * 判斷是否為主要流量來源.
     */
    public function isPrimarySource(): bool
    {
        return in_array($this, [
            self::WEB,
            self::MOBILE_APP,
            self::SEARCH_ENGINE,
            self::SOCIAL_MEDIA,
        ], true);
    }

    /**
     * 判斷是否為程式化存取.
     */
    public function isProgrammaticAccess(): bool
    {
        return in_array($this, [
            self::API,
            self::RSS_FEED,
        ], true);
    }

    /**
     * 判斷是否為外部來源.
     */
    public function isExternalSource(): bool
    {
        return in_array($this, [
            self::SEARCH_ENGINE,
            self::SOCIAL_MEDIA,
            self::EMAIL_NEWSLETTER,
            self::REFERRAL,
        ], true);
    }

    /**
     * 取得所有來源類型.
     *
     * @return array<self>
     */
    public static function getAllTypes(): array
    {
        return self::cases();
    }

    /**
     * 取得主要來源類型.
     *
     * @return array<self>
     */
    public static function getPrimarySources(): array
    {
        return array_filter(
            self::cases(),
            fn(self $source) => $source->isPrimarySource(),
        );
    }

    /**
     * 根據字串值取得對應的來源類型.
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
