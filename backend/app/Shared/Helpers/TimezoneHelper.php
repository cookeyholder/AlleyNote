<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use PDO;

/**
 * 時區處理輔助類.
 *
 * 負責處理網站時區與 UTC 之間的轉換
 * 資料庫統一儲存 RFC3339 格式的 UTC 時間
 */
class TimezoneHelper
{
    private static ?string $siteTimezone = null;

    /**
     * 獲取網站時區設定.
     */
    public static function getSiteTimezone(): string
    {
        if (self::$siteTimezone === null) {
            // 從資料庫讀取時區設定
            try {
                $dbPath = $_ENV['DB_DATABASE'] ?? '/var/www/html/database/alleynote.sqlite3';
                $pdo = new PDO("sqlite:{$dbPath}");
                $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'site_timezone' LIMIT 1");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                self::$siteTimezone = $result['value'] ?? 'Asia/Taipei';
            } catch (Exception $e) {
                // 如果讀取失敗，使用預設值
                self::$siteTimezone = 'Asia/Taipei';
            }
        }

        return self::$siteTimezone;
    }

    /**
     * 重設快取的時區設定.
     */
    public static function resetTimezoneCache(): void
    {
        self::$siteTimezone = null;
    }

    /**
     * 將 UTC 時間轉換為網站時區
     *
     * @param string $utcTime RFC3339 格式的 UTC 時間（例如：2025-10-11T04:30:00Z）
     * @return string RFC3339 格式的網站時區時間（例如：2025-10-11T12:30:00+08:00）
     */
    public static function utcToSiteTimezone(string $utcTime): string
    {
        try {
            $dt = new DateTimeImmutable($utcTime, new DateTimeZone('UTC'));
            $siteTimezone = new DateTimeZone(self::getSiteTimezone());
            $dt = $dt->setTimezone($siteTimezone);

            return $dt->format('c'); // RFC3339 格式
        } catch (Exception $e) {
            // 如果轉換失敗，返回原始時間
            return $utcTime;
        }
    }

    /**
     * 將網站時區時間轉換為 UTC.
     *
     * @param string $siteTime 網站時區時間（可以是多種格式）
     * @return string RFC3339 格式的 UTC 時間（例如：2025-10-11T04:30:00Z）
     */
    public static function siteTimezoneToUtc(string $siteTime): string
    {
        try {
            $siteTimezone = new DateTimeZone(self::getSiteTimezone());
            $dt = new DateTimeImmutable($siteTime, $siteTimezone);
            $dt = $dt->setTimezone(new DateTimeZone('UTC'));

            return $dt->format('Y-m-d\TH:i:s\Z'); // RFC3339 UTC 格式
        } catch (Exception $e) {
            // 如果轉換失敗，假設輸入已經是 UTC
            try {
                $dt = new DateTimeImmutable($siteTime);

                return $dt->format('Y-m-d\TH:i:s\Z');
            } catch (Exception $e2) {
                // 完全失敗，返回當前 UTC 時間
                return gmdate('Y-m-d\TH:i:s\Z');
            }
        }
    }

    /**
     * 獲取當前 UTC 時間（RFC3339 格式）.
     */
    public static function nowUtc(): string
    {
        return gmdate('Y-m-d\TH:i:s\Z');
    }

    /**
     * 獲取當前網站時區時間（RFC3339 格式）.
     */
    public static function nowSiteTimezone(): string
    {
        try {
            $dt = new DateTimeImmutable('now', new DateTimeZone(self::getSiteTimezone()));

            return $dt->format('c');
        } catch (Exception $e) {
            return self::nowUtc();
        }
    }

    /**
     * 驗證 RFC3339 格式.
     */
    public static function isValidRfc3339(string $dateTime): bool
    {
        try {
            $dt = new DateTimeImmutable($dateTime);

            // 檢查是否能成功解析
            return $dt !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 格式化顯示時間（用於前端顯示）.
     *
     * @param string $utcTime UTC 時間
     * @param string $format 格式（預設：Y-m-d H:i:s）
     * @return string 格式化後的網站時區時間
     */
    public static function formatForDisplay(string $utcTime, string $format = 'Y-m-d H:i:s'): string
    {
        try {
            $dt = new DateTimeImmutable($utcTime, new DateTimeZone('UTC'));
            $siteTimezone = new DateTimeZone(self::getSiteTimezone());
            $dt = $dt->setTimezone($siteTimezone);

            return $dt->format($format);
        } catch (Exception $e) {
            return $utcTime;
        }
    }

    /**
     * 獲取時區偏移量.
     *
     * @return string 例如：+08:00
     */
    public static function getTimezoneOffset(): string
    {
        try {
            $timezone = new DateTimeZone(self::getSiteTimezone());
            $dt = new DateTimeImmutable('now', $timezone);
            $offset = $timezone->getOffset($dt);

            $hours = intdiv($offset, 3600);
            $minutes = abs(intdiv($offset % 3600, 60));

            return sprintf('%+03d:%02d', $hours, $minutes);
        } catch (Exception $e) {
            return '+00:00';
        }
    }

    /**
     * 獲取常用時區列表.
     */
    public static function getCommonTimezones(): array
    {
        return [
            'UTC' => 'UTC (協調世界時)',
            'Asia/Taipei' => 'Asia/Taipei (台北時間 UTC+8)',
            'Asia/Tokyo' => 'Asia/Tokyo (東京時間 UTC+9)',
            'Asia/Shanghai' => 'Asia/Shanghai (上海時間 UTC+8)',
            'Asia/Hong_Kong' => 'Asia/Hong_Kong (香港時間 UTC+8)',
            'Asia/Singapore' => 'Asia/Singapore (新加坡時間 UTC+8)',
            'America/New_York' => 'America/New_York (紐約時間 UTC-5/-4)',
            'America/Los_Angeles' => 'America/Los_Angeles (洛杉磯時間 UTC-8/-7)',
            'America/Chicago' => 'America/Chicago (芝加哥時間 UTC-6/-5)',
            'Europe/London' => 'Europe/London (倫敦時間 UTC+0/+1)',
            'Europe/Paris' => 'Europe/Paris (巴黎時間 UTC+1/+2)',
            'Europe/Berlin' => 'Europe/Berlin (柏林時間 UTC+1/+2)',
            'Australia/Sydney' => 'Australia/Sydney (雪梨時間 UTC+10/+11)',
        ];
    }
}
