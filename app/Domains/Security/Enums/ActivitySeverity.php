<?php

declare(strict_types=1);

namespace App\Domains\Security\Enums;

/**
 * 活動嚴重程度枚舉
 * 定義使用者行為的重要性和嚴重程度等級
 */
enum ActivitySeverity: int
{
    case LOW = 1;        // 一般操作，如瀏覽
    case NORMAL = 2;     // 標準操作，如編輯內容
    case MEDIUM = 3;     // 中等重要，如帳號設定變更
    case HIGH = 4;       // 高重要性，如權限變更
    case CRITICAL = 5;   // 關鍵操作，如系統設定變更

    /**
     * 取得嚴重程度顯示名稱
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::LOW => '低',
            self::NORMAL => '正常',
            self::MEDIUM => '中等',
            self::HIGH => '高',
            self::CRITICAL => '關鍵',
        };
    }

    /**
     * 取得嚴重程度描述
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::LOW => '一般性操作，對系統影響很小',
            self::NORMAL => '標準操作，對系統有正常影響',
            self::MEDIUM => '中等重要操作，需要留意',
            self::HIGH => '高重要性操作，需要特別關注',
            self::CRITICAL => '關鍵操作，對系統安全有重大影響',
        };
    }

    /**
     * 比較嚴重程度是否大於等於指定等級
     */
    public function isAtLeast(self $level): bool
    {
        return $this->value >= $level->value;
    }

    /**
     * 比較嚴重程度是否小於等於指定等級
     */
    public function isAtMost(self $level): bool
    {
        return $this->value <= $level->value;
    }

    /**
     * 判斷是否為高風險等級（HIGH 或 CRITICAL）
     */
    public function isHighRisk(): bool
    {
        return $this->isAtLeast(self::HIGH);
    }

    /**
     * 判斷是否為低風險等級（LOW 或 NORMAL）
     */
    public function isLowRisk(): bool
    {
        return $this->isAtMost(self::NORMAL);
    }

    /**
     * 取得所有嚴重程度等級
     * 
     * @return array<self>
     */
    public static function getAllLevels(): array
    {
        return self::cases();
    }

    /**
     * 根據數值取得對應的嚴重程度
     */
    public static function fromValue(int $value): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }
        return null;
    }
}
