<?php

declare(strict_types=1);

namespace App\Domains\Security\Enums;

enum ActivityStatus: string
{
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case ERROR = 'error';
    case BLOCKED = 'blocked';
    case PENDING = 'pending';

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

    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }

    public function isFailure(): bool
    {
        return in_array($this, [self::FAILED, self::ERROR, self::BLOCKED], true);
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isFinal(): bool
    {
        return !$this->isPending();
    }

    /**
     * @return array<int, string>
     */
    public static function getAvailableStatuses(): array
    {
        return array_map(
            static fn(self $status): string => $status->value,
            self::cases(),
        );
    }
}
