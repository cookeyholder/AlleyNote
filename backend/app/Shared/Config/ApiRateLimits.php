<?php

declare(strict_types=1);

namespace App\Shared\Config;

/**
 * API 使用率限制配置.
 *
 * 定義各 API 端點的使用率限制規則
 */
class ApiRateLimits
{
    /**
     * 預設限制: 每分鐘請求次數.
     */
    public const DEFAULT_REQUESTS_PER_MINUTE = 60;

    /**
     * 預設限制: 每小時請求次數.
     */
    public const DEFAULT_REQUESTS_PER_HOUR = 1000;

    /**
     * 預設限制: 每天請求次數.
     */
    public const DEFAULT_REQUESTS_PER_DAY = 10000;

    /**
     * 認證端點限制（較嚴格，防止暴力破解）.
     */
    public const AUTH_ENDPOINTS = [
        'login' => [
            'requests_per_minute' => 5,
            'requests_per_hour' => 20,
            'requests_per_day' => 100,
            'description' => '登入端點限制較嚴格，防止暴力破解',
        ],
        'register' => [
            'requests_per_minute' => 3,
            'requests_per_hour' => 10,
            'requests_per_day' => 50,
            'description' => '註冊端點限制較嚴格，防止惡意註冊',
        ],
        'refresh' => [
            'requests_per_minute' => 10,
            'requests_per_hour' => 100,
            'requests_per_day' => 500,
            'description' => 'Token 刷新端點',
        ],
    ];

    /**
     * 查詢端點限制（相對寬鬆）.
     */
    public const READ_ENDPOINTS = [
        'list' => [
            'requests_per_minute' => 100,
            'requests_per_hour' => 2000,
            'requests_per_day' => 20000,
            'description' => '列表查詢端點',
        ],
        'detail' => [
            'requests_per_minute' => 120,
            'requests_per_hour' => 3000,
            'requests_per_day' => 30000,
            'description' => '詳細資訊查詢端點',
        ],
    ];

    /**
     * 寫入端點限制（中等嚴格）.
     */
    public const WRITE_ENDPOINTS = [
        'create' => [
            'requests_per_minute' => 20,
            'requests_per_hour' => 200,
            'requests_per_day' => 1000,
            'description' => '建立資源端點',
        ],
        'update' => [
            'requests_per_minute' => 30,
            'requests_per_hour' => 300,
            'requests_per_day' => 2000,
            'description' => '更新資源端點',
        ],
        'delete' => [
            'requests_per_minute' => 10,
            'requests_per_hour' => 100,
            'requests_per_day' => 500,
            'description' => '刪除資源端點',
        ],
    ];

    /**
     * 管理員端點限制（相對寬鬆）.
     */
    public const ADMIN_ENDPOINTS = [
        'requests_per_minute' => 200,
        'requests_per_hour' => 5000,
        'requests_per_day' => 50000,
        'description' => '管理員端點限制較寬鬆',
    ];

    /**
     * 檔案上傳端點限制（最嚴格）.
     */
    public const UPLOAD_ENDPOINTS = [
        'requests_per_minute' => 5,
        'requests_per_hour' => 30,
        'requests_per_day' => 100,
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'],
        'description' => '檔案上傳端點限制最嚴格',
    ];

    /**
     * 搜尋端點限制.
     */
    public const SEARCH_ENDPOINTS = [
        'requests_per_minute' => 30,
        'requests_per_hour' => 500,
        'requests_per_day' => 5000,
        'description' => '搜尋端點有中等限制',
    ];

    /**
     * 取得端點的限制配置.
     */
    public static function getLimit(string $endpoint, string $type = 'default'): array
    {
        return match ($type) {
            'auth' => self::AUTH_ENDPOINTS[$endpoint] ?? self::AUTH_ENDPOINTS['login'],
            'read' => self::READ_ENDPOINTS[$endpoint] ?? self::READ_ENDPOINTS['list'],
            'write' => self::WRITE_ENDPOINTS[$endpoint] ?? self::WRITE_ENDPOINTS['create'],
            'admin' => self::ADMIN_ENDPOINTS,
            'upload' => self::UPLOAD_ENDPOINTS,
            'search' => self::SEARCH_ENDPOINTS,
            default => [
                'requests_per_minute' => self::DEFAULT_REQUESTS_PER_MINUTE,
                'requests_per_hour' => self::DEFAULT_REQUESTS_PER_HOUR,
                'requests_per_day' => self::DEFAULT_REQUESTS_PER_DAY,
                'description' => '預設限制',
            ],
        };
    }

    /**
     * 取得所有限制配置.
     */
    public static function getAllLimits(): array
    {
        return [
            'default' => [
                'requests_per_minute' => self::DEFAULT_REQUESTS_PER_MINUTE,
                'requests_per_hour' => self::DEFAULT_REQUESTS_PER_HOUR,
                'requests_per_day' => self::DEFAULT_REQUESTS_PER_DAY,
            ],
            'auth' => self::AUTH_ENDPOINTS,
            'read' => self::READ_ENDPOINTS,
            'write' => self::WRITE_ENDPOINTS,
            'admin' => self::ADMIN_ENDPOINTS,
            'upload' => self::UPLOAD_ENDPOINTS,
            'search' => self::SEARCH_ENDPOINTS,
        ];
    }
}
