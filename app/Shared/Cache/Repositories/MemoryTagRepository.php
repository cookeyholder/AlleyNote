<?php

declare(strict_types=1);

namespace App\Shared\Cache\Repositories;

use App\Shared\Cache\Contracts\TagRepositoryInterface;

/**
 * 記憶體標籤儲存庫
 *
 * 在記憶體中管理快取標籤的關聯性，適用於開發和測試環境
 */
class MemoryTagRepository implements TagRepositoryInterface
{
    /**
     * 快取鍵到標籤的對應
     * @var array<string, array<string, int>> key => [tag => expiry_time]
     */
    private array $keyToTags = [];

    /**
     * 標籤到快取鍵的對應
     * @var array<string, array<string, int>> tag => [key => expiry_time]
     */
    private array $tagToKeys = [];

    /**
     * 為快取鍵設定標籤
     */
    public function setTags(string $key, array $tags, int $ttl = 3600): bool
    {
        // 先清除舊的標籤關聯
        $this->deleteKey($key);

        $expiryTime = time() + $ttl;
        $normalizedTags = $this->normalizeTags($tags);

        // 建立新的關聯
        $this->keyToTags[$key] = [];
        foreach ($normalizedTags as $tag) {
            $this->keyToTags[$key][$tag] = $expiryTime;
            
            if (!isset($this->tagToKeys[$tag])) {
                $this->tagToKeys[$tag] = [];
            }
            $this->tagToKeys[$tag][$key] = $expiryTime;
        }

        return true;
    }

    /**
     * 取得快取鍵的所有標籤
     */
    public function getTags(string $key): array
    {
        if (!isset($this->keyToTags[$key])) {
            return [];
        }

        $currentTime = time();
        $validTags = [];

        foreach ($this->keyToTags[$key] as $tag => $expiryTime) {
            if ($expiryTime > $currentTime) {
                $validTags[] = $tag;
            }
        }

        // 清理過期的標籤
        if (count($validTags) !== count($this->keyToTags[$key])) {
            $this->cleanupExpiredTags($key);
        }

        return $validTags;
    }

    /**
     * 為快取鍵添加標籤
     */
    public function addTags(string $key, array $tags): bool
    {
        $normalizedTags = $this->normalizeTags($tags);
        $currentTime = time();
        
        // 取得現有標籤的過期時間，使用最大值作為新標籤的過期時間
        $maxExpiryTime = $currentTime + 3600; // 預設 1 小時
        if (isset($this->keyToTags[$key])) {
            foreach ($this->keyToTags[$key] as $expiryTime) {
                $maxExpiryTime = max($maxExpiryTime, $expiryTime);
            }
        }

        // 初始化 key 的標籤陣列
        if (!isset($this->keyToTags[$key])) {
            $this->keyToTags[$key] = [];
        }

        // 添加新標籤
        foreach ($normalizedTags as $tag) {
            $this->keyToTags[$key][$tag] = $maxExpiryTime;
            
            if (!isset($this->tagToKeys[$tag])) {
                $this->tagToKeys[$tag] = [];
            }
            $this->tagToKeys[$tag][$key] = $maxExpiryTime;
        }

        return true;
    }

    /**
     * 從快取鍵移除標籤
     */
    public function removeTags(string $key, array $tags): bool
    {
        if (!isset($this->keyToTags[$key])) {
            return true;
        }

        $normalizedTags = $this->normalizeTags($tags);

        foreach ($normalizedTags as $tag) {
            // 從快取鍵的標籤列表移除
            unset($this->keyToTags[$key][$tag]);
            
            // 從標籤的快取鍵列表移除
            if (isset($this->tagToKeys[$tag])) {
                unset($this->tagToKeys[$tag][$key]);
                
                // 如果標籤沒有關聯的快取鍵，移除標籤
                if (empty($this->tagToKeys[$tag])) {
                    unset($this->tagToKeys[$tag]);
                }
            }
        }

        // 如果快取鍵沒有任何標籤，移除記錄
        if (empty($this->keyToTags[$key])) {
            unset($this->keyToTags[$key]);
        }

        return true;
    }

    /**
     * 檢查快取鍵是否包含指定標籤
     */
    public function hasTag(string $key, string $tag): bool
    {
        $tags = $this->getTags($key);
        return in_array($tag, $tags, true);
    }

    /**
     * 取得指定標籤的所有快取鍵
     */
    public function getKeysByTag(string $tag): array
    {
        if (!isset($this->tagToKeys[$tag])) {
            return [];
        }

        $currentTime = time();
        $validKeys = [];

        foreach ($this->tagToKeys[$tag] as $key => $expiryTime) {
            if ($expiryTime > $currentTime) {
                $validKeys[] = $key;
            }
        }

        // 清理過期的快取鍵
        if (count($validKeys) !== count($this->tagToKeys[$tag])) {
            $this->cleanupExpiredKeys($tag);
        }

        return $validKeys;
    }

    /**
     * 按標籤刪除快取鍵記錄
     */
    public function deleteByTags(string|array $tags): array
    {
        $tagsArray = is_array($tags) ? $tags : [$tags];
        $deletedKeys = [];

        foreach ($tagsArray as $tag) {
            $keys = $this->getKeysByTag($tag);
            foreach ($keys as $key) {
                $this->deleteKey($key);
                $deletedKeys[] = $key;
            }
        }

        return array_unique($deletedKeys);
    }

    /**
     * 刪除快取鍵的標籤記錄
     */
    public function deleteKey(string $key): bool
    {
        if (!isset($this->keyToTags[$key])) {
            return true;
        }

        // 從所有相關標籤中移除這個快取鍵
        foreach ($this->keyToTags[$key] as $tag => $expiryTime) {
            if (isset($this->tagToKeys[$tag])) {
                unset($this->tagToKeys[$tag][$key]);
                
                // 如果標籤沒有關聯的快取鍵，移除標籤
                if (empty($this->tagToKeys[$tag])) {
                    unset($this->tagToKeys[$tag]);
                }
            }
        }

        // 移除快取鍵記錄
        unset($this->keyToTags[$key]);

        return true;
    }

    /**
     * 取得所有標籤
     */
    public function getAllTags(): array
    {
        $this->cleanupExpiredData();
        return array_keys($this->tagToKeys);
    }

    /**
     * 清除未使用的標籤
     */
    public function cleanupUnusedTags(): int
    {
        $initialCount = count($this->tagToKeys);
        
        $this->cleanupExpiredData();
        
        // 移除沒有關聯快取鍵的標籤
        foreach ($this->tagToKeys as $tag => $keys) {
            if (empty($keys)) {
                unset($this->tagToKeys[$tag]);
            }
        }

        return $initialCount - count($this->tagToKeys);
    }

    /**
     * 取得標籤統計資訊
     */
    public function getTagStatistics(): array
    {
        $this->cleanupExpiredData();
        
        $statistics = [];
        foreach ($this->tagToKeys as $tag => $keys) {
            $statistics[$tag] = count($keys);
        }

        return $statistics;
    }

    /**
     * 檢查標籤是否存在
     */
    public function tagExists(string $tag): bool
    {
        $this->cleanupExpiredData();
        return isset($this->tagToKeys[$tag]) && !empty($this->tagToKeys[$tag]);
    }

    /**
     * 更新快取鍵的過期時間
     */
    public function touch(string $key, int $ttl): bool
    {
        if (!isset($this->keyToTags[$key])) {
            return false;
        }

        $expiryTime = time() + $ttl;

        // 更新快取鍵的所有標籤過期時間
        foreach ($this->keyToTags[$key] as $tag => $oldExpiryTime) {
            $this->keyToTags[$key][$tag] = $expiryTime;
            
            if (isset($this->tagToKeys[$tag][$key])) {
                $this->tagToKeys[$tag][$key] = $expiryTime;
            }
        }

        return true;
    }

    /**
     * 清除所有標籤記錄
     */
    public function flush(): bool
    {
        $this->keyToTags = [];
        $this->tagToKeys = [];
        return true;
    }

    /**
     * 正規化標籤陣列
     *
     * @param array<string> $tags
     * @return array<string>
     */
    private function normalizeTags(array $tags): array
    {
        return array_unique(array_map('trim', array_filter($tags, static fn($tag) => !empty($tag))));
    }

    /**
     * 清理指定快取鍵的過期標籤
     */
    private function cleanupExpiredTags(string $key): void
    {
        if (!isset($this->keyToTags[$key])) {
            return;
        }

        $currentTime = time();
        
        foreach ($this->keyToTags[$key] as $tag => $expiryTime) {
            if ($expiryTime <= $currentTime) {
                unset($this->keyToTags[$key][$tag]);
                
                if (isset($this->tagToKeys[$tag])) {
                    unset($this->tagToKeys[$tag][$key]);
                    
                    if (empty($this->tagToKeys[$tag])) {
                        unset($this->tagToKeys[$tag]);
                    }
                }
            }
        }

        if (empty($this->keyToTags[$key])) {
            unset($this->keyToTags[$key]);
        }
    }

    /**
     * 清理指定標籤的過期快取鍵
     */
    private function cleanupExpiredKeys(string $tag): void
    {
        if (!isset($this->tagToKeys[$tag])) {
            return;
        }

        $currentTime = time();
        
        foreach ($this->tagToKeys[$tag] as $key => $expiryTime) {
            if ($expiryTime <= $currentTime) {
                unset($this->tagToKeys[$tag][$key]);
                
                if (isset($this->keyToTags[$key])) {
                    unset($this->keyToTags[$key][$tag]);
                    
                    if (empty($this->keyToTags[$key])) {
                        unset($this->keyToTags[$key]);
                    }
                }
            }
        }

        if (empty($this->tagToKeys[$tag])) {
            unset($this->tagToKeys[$tag]);
        }
    }

    /**
     * 清理所有過期資料
     */
    private function cleanupExpiredData(): void
    {
        $currentTime = time();
        
        // 清理過期的快取鍵標籤
        foreach ($this->keyToTags as $key => $tags) {
            foreach ($tags as $tag => $expiryTime) {
                if ($expiryTime <= $currentTime) {
                    $this->cleanupExpiredTags($key);
                    break;
                }
            }
        }
        
        // 清理空的標籤
        foreach ($this->tagToKeys as $tag => $keys) {
            if (empty($keys)) {
                unset($this->tagToKeys[$tag]);
            }
        }
    }
}