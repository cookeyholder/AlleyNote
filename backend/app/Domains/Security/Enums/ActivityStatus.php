<?php

declare(strict_types=1);

namespace App\Domains\Security\Enums;

/**
 * 活動狀態枚舉
 * 定義使用者行為記錄的執行狀態.
 */
enum ActivityStatus: string
{
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case ERROR = 'error';
    case BLOCKED = 'blocked';
    case PENDING = 'pending';

    /**
     * 取得狀態顯示名稱.
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::SUCCESS => '成功',
            self::FAILED => '失敗',
            self::ERROR => '錯誤',
            self::BLOCKED => '被封鎖',
            self::PENDING => '待處理',
        };
    }

    /**
     * 判斷是否為成功狀態.
     */
    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }

    /**
     * 判斷是否為失敗狀態.
     */
    public function isFailure(): bool
    {
        return in_array($this, [self::FAILED, self::ERROR, self::BLOCKED], true);
    }

    /**
     * 判斷是否為待處理狀態.
     */
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * 判斷是否為終結狀態（不會再改變）.
     */
    public function isFinal(): bool
    {
        return $this !== self::PENDING;
    }

    /**
     * 取得所有可用狀態.
     *
     * @return array<string>
     */
    public static function getAvailableStatuses(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
