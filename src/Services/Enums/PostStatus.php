<?php

declare(strict_types=1);

namespace App\Services\Enums;

enum PostStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    /**
     * 取得狀態的中文說明
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => '草稿',
            self::PUBLISHED => '已發布',
            self::ARCHIVED => '已封存'
        };
    }

    /**
     * 檢查是否可以轉換到目標狀態
     */
    public function canTransitionTo(self $targetStatus): bool
    {
        if ($this === $targetStatus) {
            return true; // 允許保持相同狀態
        }

        return match ($this) {
            self::DRAFT => true, // 草稿可以轉換到任何狀態
            self::PUBLISHED => $targetStatus === self::ARCHIVED, // 已發布只能轉換到封存
            self::ARCHIVED => false // 已封存不能轉換到其他狀態
        };
    }
}
