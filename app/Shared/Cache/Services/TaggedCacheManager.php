<?php

declare(strict_types=1);

namespace App\Shared\Cache\Services;

use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Contracts\TaggedCacheInterface;
use App\Shared\Cache\Contracts\TagRepositoryInterface;
use App\Shared\Cache\ValueObjects\CacheTag;
use App\Shared\Monitoring\Contracts\CacheMonitorInterface;
use Psr\Log\LoggerInterface;

/**
 * 標籤化快取管理器
 *
 * 提供基於標籤的快取管理功能，支援批量操作和自動失效
 */
class TaggedCacheManager implements TaggedCacheInterface
{
    /**
     * 當前標籤集合
     * @var array<string>
     */
    private array $tags = [];

    public function __construct(
        private CacheManagerInterface $cacheManager,
        private TagRepositoryInterface $tagRepository,
        private LoggerInterface $logger,
        private ?CacheMonitorInterface $monitor = null
    ) {
    }

    /**
     * 取得快取資料
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // 檢查快取鍵是否有標籤
        $keyTags = $this->tagRepository->getTags($key);
        if (!empty($keyTags)) {
            // 記錄標籤化快取存取
            $this->logTaggedAccess('get', $key, $keyTags);
        }

        return $this->cacheManager->get($key, $default);
    }

    /**
     * 設定快取資料
     */
    public function put(string $key, mixed $value, int $ttl = 3600): bool
    {
        // 先設定快取值
        $success = $this->cacheManager->put($key, $value, $ttl);

        if ($success && !empty($this->tags)) {
            // 設定標籤關聯
            $tagSuccess = $this->tagRepository->setTags($key, $this->tags, $ttl);

            if (!$tagSuccess) {
                $this->logger->warning('設定快取標籤失敗', [
                    'key' => $key,
                    'tags' => $this->tags,
                ]);
            }

            $this->logTaggedAccess('put', $key, $this->tags);
        }

        return $success;
    }

    /**
     * 檢查快取是否存在
     */
    public function has(string $key): bool
    {
        return $this->cacheManager->has($key);
    }

    /**
     * 刪除快取
     */
    public function forget(string $key): bool
    {
        $keyTags = $this->tagRepository->getTags($key);

        // 先刪除快取值
        $success = $this->cacheManager->forget($key);

        if ($success) {
            // 刪除標籤關聯
            $this->tagRepository->deleteKey($key);

            if (!empty($keyTags)) {
                $this->logTaggedAccess('forget', $key, $keyTags);
            }
        }

        return $success;
    }

    /**
     * 清空所有標籤化快取
     */
    public function flush(): bool
    {
        $success = $this->cacheManager->flush();

        if ($success) {
            // 清空所有標籤關聯
            $this->tagRepository->flush();
            $this->logger->info('所有標籤化快取已清空');
        }

        return $success;
    }

    /**
     * 記憶化取得
     */
    public function remember(string $key, callable $callback, int $ttl = 3600): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        try {
            $value = $callback();
            $this->put($key, $value, $ttl);
            return $value;
        } catch (\Exception $e) {
            $this->logger->error('標籤化快取記憶化失敗', [
                'key' => $key,
                'tags' => $this->tags,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 增加新標籤到快取管理器
     */
    public function addTags(string|array $tags): TaggedCacheInterface
    {
        $tagsArray = is_array($tags) ? $tags : [$tags];

        foreach ($tagsArray as $tag) {
            if (!in_array($tag, $this->tags, true)) {
                // 驗證標籤
                $cacheTag = new CacheTag($tag);
                $this->tags[] = $cacheTag->getName();
            }
        }

        return $this;
    }

    /**
     * 取得當前快取管理器的所有標籤
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * 按標籤清空快取
     */
    public function flushByTags(string|array $tags): int
    {
        $tagsArray = is_array($tags) ? $tags : [$tags];
        $deletedKeys = [];

        foreach ($tagsArray as $tag) {
            $keys = $this->tagRepository->getKeysByTag($tag);
            foreach ($keys as $key) {
                $this->cacheManager->forget($key);
                $deletedKeys[] = $key;
            }
        }

        // 刪除標籤關聯
        $this->tagRepository->deleteByTags($tagsArray);

        $deletedCount = count(array_unique($deletedKeys));

        $this->logger->info('按標籤清空快取', [
            'tags' => $tagsArray,
            'deleted_keys_count' => $deletedCount,
        ]);

        return $deletedCount;
    }

    /**
     * 取得標籤下的所有快取鍵
     */
    public function getTaggedKeys(): array
    {
        $keys = [];

        foreach ($this->tags as $tag) {
            $tagKeys = $this->tagRepository->getKeysByTag($tag);
            $keys = array_merge($keys, $tagKeys);
        }

        return array_unique($keys);
    }

    // ========== 進階標籤功能實作 ==========

    /**
     * 使用指定標籤存放快取項目
     */
    public function putWithTags(string $key, mixed $value, array $tags, int $ttl = 3600): bool
    {
        // 驗證所有標籤
        foreach ($tags as $tag) {
            new CacheTag($tag); // 驗證標籤格式
        }

        // 先設定快取值
        $success = $this->cacheManager->put($key, $value, $ttl);

        if ($success) {
            // 設定標籤關聯
            $tagSuccess = $this->tagRepository->setTags($key, $tags, $ttl);

            if (!$tagSuccess) {
                $this->logger->warning('設定快取標籤失敗', [
                    'key' => $key,
                    'tags' => $tags,
                ]);
            }

            $this->logTaggedAccess('putWithTags', $key, $tags);
        }

        return $success;
    }

    /**
     * 取得指定標籤的所有快取鍵
     */
    public function getKeysByTag(string $tag): array
    {
        return $this->tagRepository->getKeysByTag($tag);
    }

    /**
     * 取得快取項目的所有標籤
     */
    public function getTagsByKey(string $key): array
    {
        return $this->tagRepository->getTags($key);
    }

    /**
     * 為現有快取項目添加標籤
     */
    public function addTagsToKey(string $key, string|array $tags): bool
    {
        if (!$this->cacheManager->has($key)) {
            return false;
        }

        $tagsArray = is_array($tags) ? $tags : [$tags];

        // 驗證所有標籤
        foreach ($tagsArray as $tag) {
            new CacheTag($tag);
        }

        $success = $this->tagRepository->addTags($key, $tagsArray);

        if ($success) {
            $this->logTaggedAccess('addTags', $key, $tagsArray);
        }

        return $success;
    }

    /**
     * 從快取項目移除標籤
     */
    public function removeTagsFromKey(string $key, string|array $tags): bool
    {
        $tagsArray = is_array($tags) ? $tags : [$tags];

        $success = $this->tagRepository->removeTags($key, $tagsArray);

        if ($success) {
            $this->logTaggedAccess('removeTags', $key, $tagsArray);
        }

        return $success;
    }

    /**
     * 檢查快取項目是否包含指定標籤
     */
    public function hasTag(string $key, string $tag): bool
    {
        return $this->tagRepository->hasTag($key, $tag);
    }

    /**
     * 取得所有系統標籤
     */
    public function getAllTags(): array
    {
        return $this->tagRepository->getAllTags();
    }

    /**
     * 清除未使用的標籤
     */
    public function cleanupUnusedTags(): int
    {
        $cleanedCount = $this->tagRepository->cleanupUnusedTags();

        if ($cleanedCount > 0) {
            $this->logger->info('清除未使用的標籤', [
                'cleaned_count' => $cleanedCount,
            ]);
        }

        return $cleanedCount;
    }

    /**
     * 取得標籤統計資訊
     */
    public function getTagStatistics(): array
    {
        return $this->tagRepository->getTagStatistics();
    }

    /**
     * 建立新的標籤化快取實例
     */
    public function tags(string|array $tags): TaggedCacheInterface
    {
        $instance = clone $this;
        $instance->tags = [];
        $instance->addTags($tags);

        return $instance;
    }

    /**
     * 批量設定帶標籤的快取
     *
     * @param array<string, mixed> $items 快取項目 key => value
     * @param array<string> $tags 標籤陣列
     * @param int $ttl 存活時間
     * @return array<string, bool> 設定結果 key => success
     */
    public function putMany(array $items, array $tags, int $ttl = 3600): array
    {
        $results = [];

        foreach ($items as $key => $value) {
            $results[$key] = $this->putWithTags($key, $value, $tags, $ttl);
        }

        $this->logger->info('批量設定標籤化快取', [
            'items_count' => count($items),
            'tags' => $tags,
            'success_count' => count(array_filter($results)),
        ]);

        return $results;
    }

    /**
     * 按標籤批量獲取快取
     *
     * @param string $tag 標籤
     * @return array<string, mixed> 快取項目 key => value
     */
    public function getManyByTag(string $tag): array
    {
        $keys = $this->getKeysByTag($tag);
        $results = [];

        foreach ($keys as $key) {
            $value = $this->get($key);
            if ($value !== null) {
                $results[$key] = $value;
            }
        }

        return $results;
    }

    /**
     * 記錄標籤化快取存取
     *
     * @param string $operation 操作類型
     * @param string $key 快取鍵
     * @param array<string> $tags 標籤陣列
     */
    private function logTaggedAccess(string $operation, string $key, array $tags): void
    {
        if ($this->monitor) {
            $this->monitor->recordOperation($operation, 'tagged', true, 0, [
                'key' => $key,
                'tags' => $tags,
                'tags_count' => count($tags),
            ]);
        }
    }
}
