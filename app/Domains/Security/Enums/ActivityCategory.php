<?php

declare(strict_types=1);

namespace App\Domains\Security\Enums;

/**
 * 活動分類枚舉
 */
enum ActivityCategory: string
{
    case AUTHENTICATION = 'authentication';
    case CONTENT = 'content';
    case FILE = 'file';
    case USER_MANAGEMENT = 'user_management';
    case AUTHORIZATION = 'authorization';
    case SECURITY = 'security';
    case ADMINISTRATION = 'administration';
    case API = 'api';
    case DATA_MANAGEMENT = 'data_management';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::AUTHENTICATION => '身分驗證',
            self::CONTENT => '內容管理',
            self::FILE => '檔案操作',
            self::USER_MANAGEMENT => '使用者管理',
            self::AUTHORIZATION => '權限管理',
            self::SECURITY => '安全事件',
            self::ADMINISTRATION => '系統管理',
            self::API => 'API 操作',
            self::DATA_MANAGEMENT => '資料管理',
        };
    }
}
