<?php

declare(strict_types=1);

namespace App\Shared\Cache\Repositories;

use App\Shared\Cache\Contracts\TagRepositoryInterface;

/**
 * Redis 標籤儲存庫
 *
 * 使用 Redis 管理快取標籤的關聯性，適用於生產環境
 * 使用 Redis 的 Set 和 Hash 資料結構來高效管理標籤關係
 */
class RedisTagRepository implements TagRepositoryInterface
{
    private const KEY_PREFIX = 'cache_tags:';
    private const TAG_PREFIX = 'tag:';
    private const KEY_TAG_PREFIX = 'key_tags:';
    
    /**
     * @var \Redis Redis 連線實例
     */
    private \Redis $redis;

    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * 為快取鍵設定標籤
     */
    public function setTags(string $key, array $tags, int $ttl = 3600): bool
    {
        if (empty($tags)) {
            return true;
        }

        // 開始 Redis 交易
        $this->redis->multi();

        try {
            $normalizedTags = $this->normalizeTags($tags);
            $expiryTime = time() + $ttl;
            $keyTagsKey = $this->getKeyTagsKey($key);

            // 先清除舊的標籤關聯
            $this->deleteKeyInternal($key);

            // 設定快取鍵的標籤 (使用 Hash 儲存標籤和過期時間)
            $tagData = [];
            foreach ($normalizedTags as $tag) {
                $tagData[$tag] = $expiryTime;
            }
            $this->redis->hMSet($keyTagsKey, $tagData);
            $this->redis->expire($keyTagsKey, $ttl);

            // 將快取鍵添加到每個標籤的集合中
            foreach ($normalizedTags as $tag) {
                $tagKey = $this->getTagKey($tag);
                $this->redis->hSet($tagKey, $key, $expiryTime);
                $this->redis->expire($tagKey, $ttl + 3600); // 標籤索引保留較長時間
            }

            $this->redis->exec();
            return true;

        } catch (\Exception $e) {
            $this->redis->discard();
            return false;
        }
    }

    /**
     * 取得快取鍵的所有標籤
     */
    public function getTags(string $key): array
    {
        $keyTagsKey = $this->getKeyTagsKey($key);
        $tagData = $this->redis->hGetAll($keyTagsKey);

        if (empty($tagData)) {
            return [];
        }

        $currentTime = time();
        $validTags = [];

        foreach ($tagData as $tag => $expiryTime) {
            if ((int)$expiryTime > $currentTime) {
                $validTags[] = $tag;
            }
        }

        // 如果有過期標籤，清理它們
        if (count($validTags) !== count($tagData)) {
            $this->cleanupExpiredTagsForKey($key);
        }

        return $validTags;
    }

    /**
     * 為快取鍵添加標籤
     */
    public function addTags(string $key, array $tags): bool
    {
        if (empty($tags)) {
            return true;
        }

        $normalizedTags = $this->normalizeTags($tags);
        
        // 取得現有標籤的最大過期時間
        $keyTagsKey = $this->getKeyTagsKey($key);
        $existingTags = $this->redis->hGetAll($keyTagsKey);
        
        $maxExpiryTime = time() + 3600; // 預設 1 小時
        if (!empty($existingTags)) {
            $maxExpiryTime = max(array_values($existingTags));
        }

        $this->redis->multi();

        try {
            // 添加新標籤到快取鍵
            $newTagData = [];
            foreach ($normalizedTags as $tag) {
                $newTagData[$tag] = $maxExpiryTime;
            }
            $this->redis->hMSet($keyTagsKey, $newTagData);

            // 將快取鍵添加到每個新標籤的集合中
            foreach ($normalizedTags as $tag) {
                $tagKey = $this->getTagKey($tag);
                $this->redis->hSet($tagKey, $key, $maxExpiryTime);
            }

            $this->redis->exec();
            return true;

        } catch (\Exception $e) {
            $this->redis->discard();
            return false;
        }
    }

    /**
     * 從快取鍵移除標籤
     */
    public function removeTags(string $key, array $tags): bool
    {
        if (empty($tags)) {
            return true;
        }

        $normalizedTags = $this->normalizeTags($tags);
        $keyTagsKey = $this->getKeyTagsKey($key);

        $this->redis->multi();

        try {
            // 從快取鍵的標籤集合中移除
            $this->redis->hDel($keyTagsKey, ...$normalizedTags);

            // 從每個標籤的快取鍵集合中移除
            foreach ($normalizedTags as $tag) {
                $tagKey = $this->getTagKey($tag);
                $this->redis->hDel($tagKey, $key);
            }

            $this->redis->exec();
            return true;

        } catch (\Exception $e) {
            $this->redis->discard();
            return false;
        }
    }

    /**
     * 檢查快取鍵是否包含指定標籤
     */
    public function hasTag(string $key, string $tag): bool
    {
        $keyTagsKey = $this->getKeyTagsKey($key);
        $expiryTime = $this->redis->hGet($keyTagsKey, $tag);
        
        if ($expiryTime === false) {
            return false;
        }

        return (int)$expiryTime > time();
    }

    /**
     * 取得指定標籤的所有快取鍵
     */
    public function getKeysByTag(string $tag): array
    {
        $tagKey = $this->getTagKey($tag);
        $keyData = $this->redis->hGetAll($tagKey);

        if (empty($keyData)) {
            return [];
        }

        $currentTime = time();
        $validKeys = [];

        foreach ($keyData as $key => $expiryTime) {
            if ((int)$expiryTime > $currentTime) {
                $validKeys[] = $key;
            }
        }

        // 如果有過期快取鍵，清理它們
        if (count($validKeys) !== count($keyData)) {
            $this->cleanupExpiredKeysForTag($tag);
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
                $this->deleteKeyInternal($key);
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
        return $this->deleteKeyInternal($key);
    }

    /**
     * 取得所有標籤
     */
    public function getAllTags(): array
    {
        $pattern = self::KEY_PREFIX . self::TAG_PREFIX . '*';
        $tagKeys = $this->redis->keys($pattern);
        
        $tags = [];
        foreach ($tagKeys as $tagKey) {
            $tag = str_replace(self::KEY_PREFIX . self::TAG_PREFIX, '', $tagKey);
            
            // 檢查標籤是否還有有效的快取鍵
            if (!empty($this->getKeysByTag($tag))) {
                $tags[] = $tag;
            }
        }

        return $tags;
    }

    /**
     * 清除未使用的標籤
     */
    public function cleanupUnusedTags(): int
    {
        $pattern = self::KEY_PREFIX . self::TAG_PREFIX . '*';
        $tagKeys = $this->redis->keys($pattern);
        $cleanedCount = 0;

        foreach ($tagKeys as $tagKey) {
            $tag = str_replace(self::KEY_PREFIX . self::TAG_PREFIX, '', $tagKey);
            
            // 清理過期的快取鍵
            $this->cleanupExpiredKeysForTag($tag);
            
            // 如果標籤沒有有效的快取鍵，刪除標籤
            if (empty($this->getKeysByTag($tag))) {
                $this->redis->del($tagKey);
                $cleanedCount++;
            }
        }

        return $cleanedCount;
    }

    /**
     * 取得標籤統計資訊
     */
    public function getTagStatistics(): array
    {
        $allTags = $this->getAllTags();
        $statistics = [];

        foreach ($allTags as $tag) {
            $keyCount = count($this->getKeysByTag($tag));
            if ($keyCount > 0) {
                $statistics[$tag] = $keyCount;
            }
        }

        return $statistics;
    }

    /**
     * 檢查標籤是否存在
     */
    public function tagExists(string $tag): bool
    {
        $tagKey = $this->getTagKey($tag);
        return $this->redis->exists($tagKey) && !empty($this->getKeysByTag($tag));
    }

    /**
     * 更新快取鍵的過期時間
     */
    public function touch(string $key, int $ttl): bool
    {
        $keyTagsKey = $this->getKeyTagsKey($key);
        $tagData = $this->redis->hGetAll($keyTagsKey);

        if (empty($tagData)) {
            return false;
        }

        $expiryTime = time() + $ttl;

        $this->redis->multi();

        try {
            // 更新快取鍵的標籤過期時間
            $updatedTagData = [];
            foreach ($tagData as $tag => $oldExpiryTime) {
                $updatedTagData[$tag] = $expiryTime;
            }
            $this->redis->hMSet($keyTagsKey, $updatedTagData);
            $this->redis->expire($keyTagsKey, $ttl);

            // 更新每個標籤中的快取鍵過期時間
            foreach (array_keys($tagData) as $tag) {
                $tagKey = $this->getTagKey($tag);
                $this->redis->hSet($tagKey, $key, $expiryTime);
            }

            $this->redis->exec();
            return true;

        } catch (\Exception $e) {
            $this->redis->discard();
            return false;
        }
    }

    /**
     * 清除所有標籤記錄
     */
    public function flush(): bool
    {
        $pattern = self::KEY_PREFIX . '*';
        $keys = $this->redis->keys($pattern);
        
        if (!empty($keys)) {
            $this->redis->del(...$keys);
        }
        
        return true;
    }

    /**
     * 取得快取鍵的標籤儲存鍵
     */
    private function getKeyTagsKey(string $key): string
    {
        return self::KEY_PREFIX . self::KEY_TAG_PREFIX . $key;
    }

    /**
     * 取得標籤的儲存鍵
     */
    private function getTagKey(string $tag): string
    {
        return self::KEY_PREFIX . self::TAG_PREFIX . $tag;
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
     * 內部刪除快取鍵方法
     */
    private function deleteKeyInternal(string $key): bool
    {
        $keyTagsKey = $this->getKeyTagsKey($key);
        $tagData = $this->redis->hGetAll($keyTagsKey);

        if (empty($tagData)) {
            return true;
        }

        $this->redis->multi();

        try {
            // 刪除快取鍵的標籤記錄
            $this->redis->del($keyTagsKey);

            // 從每個標籤的快取鍵集合中移除
            foreach (array_keys($tagData) as $tag) {
                $tagKey = $this->getTagKey($tag);
                $this->redis->hDel($tagKey, $key);
            }

            $this->redis->exec();
            return true;

        } catch (\Exception $e) {
            $this->redis->discard();
            return false;
        }
    }

    /**
     * 清理快取鍵的過期標籤
     */
    private function cleanupExpiredTagsForKey(string $key): void
    {
        $keyTagsKey = $this->getKeyTagsKey($key);
        $tagData = $this->redis->hGetAll($keyTagsKey);
        $currentTime = time();

        $this->redis->multi();

        foreach ($tagData as $tag => $expiryTime) {
            if ((int)$expiryTime <= $currentTime) {
                // 從快取鍵的標籤集合中移除過期標籤
                $this->redis->hDel($keyTagsKey, $tag);
                
                // 從標籤的快取鍵集合中移除過期項目
                $tagKey = $this->getTagKey($tag);
                $this->redis->hDel($tagKey, $key);
            }
        }

        $this->redis->exec();
    }

    /**
     * 清理標籤的過期快取鍵
     */
    private function cleanupExpiredKeysForTag(string $tag): void
    {
        $tagKey = $this->getTagKey($tag);
        $keyData = $this->redis->hGetAll($tagKey);
        $currentTime = time();

        $this->redis->multi();

        foreach ($keyData as $key => $expiryTime) {
            if ((int)$expiryTime <= $currentTime) {
                // 從標籤的快取鍵集合中移除過期項目
                $this->redis->hDel($tagKey, $key);
                
                // 從快取鍵的標籤集合中移除過期標籤
                $keyTagsKey = $this->getKeyTagsKey($key);
                $this->redis->hDel($keyTagsKey, $tag);
            }
        }

        $this->redis->exec();
    }
}