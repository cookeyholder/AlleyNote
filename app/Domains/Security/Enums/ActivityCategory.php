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
        return match($this) {
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

/**
 * 活動嚴重程度枚舉
 */
enum ActivitySeverity: string
{
    case INFO = 'info';
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function getDisplayName(): string
    {
        return match($this) {
            self::INFO => '資訊',
            self::LOW => '低',
            self::MEDIUM => '中等',
            self::HIGH => '高',
            self::CRITICAL => '嚴重',
        };
    }

    /**
     * 取得數字等級（用於排序和比較）
     */
    public function getLevel(): int
    {
        return match($this) {
            self::INFO => 1,
            self::LOW => 2,
            self::MEDIUM => 3,
            self::HIGH => 4,
            self::CRITICAL => 5,
        };
    }
}

/**
 * 活動狀態枚舉
 */
enum ActivityStatus: string
{
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case ERROR = 'error';
    case BLOCKED = 'blocked';
    case PENDING = 'pending';

    public function getDisplayName(): string
    {
        return match($this) {
            self::SUCCESS => '成功',
            self::FAILED => '失敗',
            self::ERROR => '錯誤',
            self::BLOCKED => '已阻擋',
            self::PENDING => '待處理',
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::SUCCESS;
    }

    public function isFailure(): bool
    {
        return in_array($this, [self::FAILED, self::ERROR, self::BLOCKED]);
    }
}