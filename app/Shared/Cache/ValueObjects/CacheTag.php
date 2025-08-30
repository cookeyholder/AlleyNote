<?php

declare(strict_types=1);

namespace App\Shared\Cache\ValueObjects;

/**
 * 快取標籤值物件
 *
 * 表示快取項目的標籤，提供標籤驗證和正規化功能
 */
class CacheTag
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $this->normalizeName($name);
        $this->validateName($this->name);
    }

    /**
     * 取得標籤名稱
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 正規化標籤名稱
     */
    private function normalizeName(string $name): string
    {
        // 轉換為小寫，移除多餘空白，替換特殊字符
        $normalized = strtolower(trim($name));
        $normalized = preg_replace('/[^a-z0-9_\-\.]/', '_', $normalized);
        $normalized = preg_replace('/_{2,}/', '_', $normalized);
        
        return trim($normalized, '_');
    }

    /**
     * 驗證標籤名稱
     */
    private function validateName(string $name): void
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('標籤名稱不能為空');
        }

        if (strlen($name) > 50) {
            throw new \InvalidArgumentException('標籤名稱不能超過 50 個字符');
        }

        if (!preg_match('/^[a-z0-9_\-\.]+$/', $name)) {
            throw new \InvalidArgumentException('標籤名稱只能包含英文字母、數字、底線、連字號和點號');
        }

        // 保留標籤名稱檢查
        $reserved = ['all', 'none', 'cache', 'tag', 'key', 'system', 'admin'];
        if (in_array($name, $reserved, true)) {
            throw new \InvalidArgumentException("標籤名稱 '{$name}' 為系統保留字");
        }
    }

    /**
     * 轉換為字串
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * 比較兩個標籤是否相等
     */
    public function equals(CacheTag $other): bool
    {
        return $this->name === $other->name;
    }

    /**
     * 從字串陣列建立標籤陣列
     *
     * @param array<string> $names 標籤名稱陣列
     * @return array<CacheTag> 標籤陣列
     */
    public static function fromArray(array $names): array
    {
        return array_map(static fn(string $name) => new self($name), $names);
    }

    /**
     * 將標籤陣列轉換為字串陣列
     *
     * @param array<CacheTag> $tags 標籤陣列
     * @return array<string> 字串陣列
     */
    public static function toArray(array $tags): array
    {
        return array_map(static fn(CacheTag $tag) => $tag->getName(), $tags);
    }

    /**
     * 檢查標籤名稱是否有效
     */
    public static function isValidName(string $name): bool
    {
        try {
            new self($name);
            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * 建立標籤群組標籤
     *
     * @param string $group 群組名稱
     * @return self 群組標籤
     */
    public static function group(string $group): self
    {
        return new self("group:{$group}");
    }

    /**
     * 建立使用者相關標籤
     *
     * @param int $userId 使用者 ID
     * @return self 使用者標籤
     */
    public static function user(int $userId): self
    {
        return new self("user:{$userId}");
    }

    /**
     * 建立模組相關標籤
     *
     * @param string $module 模組名稱
     * @return self 模組標籤
     */
    public static function module(string $module): self
    {
        return new self("module:{$module}");
    }

    /**
     * 建立時間相關標籤
     *
     * @param string $period 時間週期 (daily, weekly, monthly)
     * @return self 時間標籤
     */
    public static function temporal(string $period): self
    {
        return new self("time:{$period}");
    }

    /**
     * 檢查是否為群組標籤
     */
    public function isGroupTag(): bool
    {
        return str_starts_with($this->name, 'group:');
    }

    /**
     * 檢查是否為使用者標籤
     */
    public function isUserTag(): bool
    {
        return str_starts_with($this->name, 'user:');
    }

    /**
     * 檢查是否為模組標籤
     */
    public function isModuleTag(): bool
    {
        return str_starts_with($this->name, 'module:');
    }

    /**
     * 檢查是否為時間標籤
     */
    public function isTemporalTag(): bool
    {
        return str_starts_with($this->name, 'time:');
    }
}