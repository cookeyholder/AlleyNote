<?php

declare(strict_types=1);

namespace App\Shared\Cache\Services;

use App\Shared\Cache\Contracts\TaggedCacheInterface;
use App\Shared\Cache\ValueObjects\CacheTag;
use Psr\Log\LoggerInterface;

/**
 * 快取分組管理器
 *
 * 提供快取分組管理功能，支援分組內的快取關聯性和自動失效
 */
class CacheGroupManager
{
    /**
     * 分組快取實例
     * @var array<string, TaggedCacheInterface>
     */
    private array $groups = [];

    /**
     * 分組依賴關係
     * @var array<string, array<string>>
     */
    private array $dependencies = [];

    /**
     * 分組自動失效規則
     * @var array<string, array<string>>
     */
    private array $invalidationRules = [];

    public function __construct(
        private TaggedCacheInterface $taggedCache,
        private LoggerInterface $logger
    ) {
    }

    /**
     * 建立快取分組
     *
     * @param string $groupName 分組名稱
     * @param array<string> $tags 分組標籤
     * @return TaggedCacheInterface 標籤化快取實例
     */
    public function group(string $groupName, array $tags = []): TaggedCacheInterface
    {
        // 添加分組標籤
        $groupTag = CacheTag::group($groupName);
        $allTags = array_merge([$groupTag->getName()], $tags);

        // 建立分組快取實例
        $groupCache = $this->taggedCache->tags($allTags);
        $this->groups[$groupName] = $groupCache;

        $this->logger->debug('建立快取分組', [
            'group_name' => $groupName,
            'tags' => $allTags,
        ]);

        return $groupCache;
    }

    /**
     * 取得快取分組
     *
     * @param string $groupName 分組名稱
     * @return TaggedCacheInterface|null 分組快取實例
     */
    public function getGroup(string $groupName): ?TaggedCacheInterface
    {
        return $this->groups[$groupName] ?? null;
    }

    /**
     * 設定分組依賴關係
     *
     * 當父分組失效時，子分組也會自動失效
     *
     * @param string $parentGroup 父分組
     * @param array<string>|string $childGroups 子分組
     */
    public function setDependencies(string $parentGroup, array|string $childGroups): void
    {
        $childGroupsArray = is_array($childGroups) ? $childGroups : [$childGroups];

        if (!isset($this->dependencies[$parentGroup])) {
            $this->dependencies[$parentGroup] = [];
        }

        $this->dependencies[$parentGroup] = array_unique(
            array_merge($this->dependencies[$parentGroup], $childGroupsArray)
        );

        $this->logger->debug('設定分組依賴關係', [
            'parent_group' => $parentGroup,
            'child_groups' => $childGroupsArray,
        ]);
    }

    /**
     * 設定自動失效規則
     *
     * 當觸發條件滿足時，目標分組會自動失效
     *
     * @param string $triggerPattern 觸發模式（支援萬用字元）
     * @param array<string>|string $targetGroups 目標分組
     */
    public function setInvalidationRule(string $triggerPattern, array|string $targetGroups): void
    {
        $targetGroupsArray = is_array($targetGroups) ? $targetGroups : [$targetGroups];

        $this->invalidationRules[$triggerPattern] = $targetGroupsArray;

        $this->logger->debug('設定自動失效規則', [
            'trigger_pattern' => $triggerPattern,
            'target_groups' => $targetGroupsArray,
        ]);
    }

    /**
     * 清空分組快取
     *
     * @param string $groupName 分組名稱
     * @param bool $cascade 是否級聯清空依賴的子分組
     * @return int 清空的快取項目數量
     */
    public function flushGroup(string $groupName, bool $cascade = true): int
    {
        if (!isset($this->groups[$groupName])) {
            return 0;
        }

        $groupTag = CacheTag::group($groupName);
        $clearedCount = $this->taggedCache->flushByTags([$groupTag->getName()]);

        $this->logger->info('清空快取分組', [
            'group_name' => $groupName,
            'cleared_count' => $clearedCount,
            'cascade' => $cascade,
        ]);

        // 如果啟用級聯清空，清空依賴的子分組
        if ($cascade && isset($this->dependencies[$groupName])) {
            foreach ($this->dependencies[$groupName] as $childGroup) {
                $clearedCount += $this->flushGroup($childGroup, true);
            }
        }

        // 移除分組實例
        unset($this->groups[$groupName]);

        return $clearedCount;
    }

    /**
     * 檢查並觸發自動失效規則
     *
     * @param string $key 快取鍵
     */
    public function checkInvalidationRules(string $key): void
    {
        foreach ($this->invalidationRules as $pattern => $targetGroups) {
            if ($this->matchPattern($key, $pattern)) {
                foreach ($targetGroups as $group) {
                    $this->flushGroup($group);
                }

                $this->logger->info('觸發自動失效規則', [
                    'key' => $key,
                    'pattern' => $pattern,
                    'target_groups' => $targetGroups,
                ]);
            }
        }
    }

    /**
     * 取得分組統計資訊
     *
     * @return array<string, mixed> 統計資訊
     */
    public function getGroupStatistics(): array
    {
        $statistics = [
            'total_groups' => count($this->groups),
            'groups' => [],
            'dependencies' => $this->dependencies,
            'invalidation_rules_count' => count($this->invalidationRules),
        ];

        foreach ($this->groups as $groupName => $groupCache) {
            $groupTag = CacheTag::group($groupName);
            $keys = $this->taggedCache->getKeysByTag($groupTag->getName());

            $statistics['groups'][$groupName] = [
                'cache_count' => count($keys),
                'tags' => $groupCache->getTags(),
                'has_dependencies' => isset($this->dependencies[$groupName]),
                'child_groups_count' => count($this->dependencies[$groupName] ?? []),
            ];
        }

        return $statistics;
    }

    /**
     * 取得所有分組名稱
     *
     * @return array<string> 分組名稱陣列
     */
    public function getAllGroups(): array
    {
        return array_keys($this->groups);
    }

    /**
     * 檢查分組是否存在
     *
     * @param string $groupName 分組名稱
     * @return bool 是否存在
     */
    public function hasGroup(string $groupName): bool
    {
        return isset($this->groups[$groupName]);
    }

    /**
     * 移除分組
     *
     * @param string $groupName 分組名稱
     * @param bool $flushCache 是否清空分組快取
     * @return bool 是否成功
     */
    public function removeGroup(string $groupName, bool $flushCache = true): bool
    {
        if (!isset($this->groups[$groupName])) {
            return false;
        }

        if ($flushCache) {
            $this->flushGroup($groupName, false);
        }

        // 移除分組實例
        unset($this->groups[$groupName]);

        // 清理依賴關係
        unset($this->dependencies[$groupName]);
        foreach ($this->dependencies as $parent => &$children) {
            $children = array_filter($children, static fn($child) => $child !== $groupName);
        }
        unset($children);

        $this->logger->debug('移除快取分組', [
            'group_name' => $groupName,
            'flush_cache' => $flushCache,
        ]);

        return true;
    }

    /**
     * 建立使用者相關的快取分組
     *
     * @param int $userId 使用者 ID
     * @param array<string> $additionalTags 額外標籤
     * @return TaggedCacheInterface 使用者快取分組
     */
    public function userGroup(int $userId, array $additionalTags = []): TaggedCacheInterface
    {
        $userTag = CacheTag::user($userId);
        $groupName = "user_{$userId}";
        $tags = array_merge([$userTag->getName()], $additionalTags);

        return $this->group($groupName, $tags);
    }

    /**
     * 建立模組相關的快取分組
     *
     * @param string $moduleName 模組名稱
     * @param array<string> $additionalTags 額外標籤
     * @return TaggedCacheInterface 模組快取分組
     */
    public function moduleGroup(string $moduleName, array $additionalTags = []): TaggedCacheInterface
    {
        $moduleTag = CacheTag::module($moduleName);
        $groupName = "module_{$moduleName}";
        $tags = array_merge([$moduleTag->getName()], $additionalTags);

        return $this->group($groupName, $tags);
    }

    /**
     * 建立時間相關的快取分組
     *
     * @param string $period 時間週期
     * @param array<string> $additionalTags 額外標籤
     * @return TaggedCacheInterface 時間快取分組
     */
    public function temporalGroup(string $period, array $additionalTags = []): TaggedCacheInterface
    {
        $temporalTag = CacheTag::temporal($period);
        $groupName = "temporal_{$period}";
        $tags = array_merge([$temporalTag->getName()], $additionalTags);

        return $this->group($groupName, $tags);
    }

    /**
     * 批量清空多個分組
     *
     * @param array<string> $groupNames 分組名稱陣列
     * @param bool $cascade 是否級聯清空
     * @return int 總清空的項目數量
     */
    public function flushGroups(array $groupNames, bool $cascade = true): int
    {
        $totalCleared = 0;

        foreach ($groupNames as $groupName) {
            $totalCleared += $this->flushGroup($groupName, $cascade);
        }

        return $totalCleared;
    }

    /**
     * 按模式清空分組
     *
     * @param string $pattern 分組名稱模式（支援 * 萬用字元）
     * @param bool $cascade 是否級聯清空
     * @return int 清空的項目數量
     */
    public function flushByPattern(string $pattern, bool $cascade = true): int
    {
        $matchingGroups = [];

        foreach ($this->groups as $groupName => $group) {
            if ($this->matchPattern($groupName, $pattern)) {
                $matchingGroups[] = $groupName;
            }
        }

        return $this->flushGroups($matchingGroups, $cascade);
    }

    /**
     * 設定分組失效規則
     *
     * @param string $groupName 分組名稱
     * @param array<string, mixed> $rules 失效規則
     */
    public function setInvalidationRules(string $groupName, array $rules): void
    {
        $this->invalidationRules[$groupName] = $rules;

        $this->logger->debug('設定分組失效規則', [
            'group_name' => $groupName,
            'rules' => $rules,
        ]);
    }

    /**
     * 取得分組失效規則
     *
     * @param string $groupName 分組名稱
     * @return array<string, mixed> 失效規則
     */
    public function getInvalidationRules(string $groupName): array
    {
        return $this->invalidationRules[$groupName] ?? [];
    }

    /**
     * 取得分組依賴關係
     *
     * @param string $groupName 分組名稱
     * @return array<string> 依賴的子分組
     */
    public function getDependencies(string $groupName): array
    {
        return $this->dependencies[$groupName] ?? [];
    }

    /**
     * 檢查分組是否應該失效
     *
     * @param string $groupName 分組名稱
     * @return bool 是否應該失效
     */
    public function shouldInvalidate(string $groupName): bool
    {
        $rules = $this->getInvalidationRules($groupName);

        if (empty($rules)) {
            return false;
        }

        // 檢查最大年齡規則
        if (isset($rules['max_age'])) {
            $group = $this->getGroup($groupName);
            if ($group && $this->isGroupExpired($groupName, $rules['max_age'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * 檢查分組是否過期
     *
     * @param string $groupName 分組名稱
     * @param int $maxAge 最大年齡（秒）
     * @return bool 是否過期
     */
    private function isGroupExpired(string $groupName, int $maxAge): bool
    {
        // 這裡應該檢查分組的建立時間，但為了簡化，我們使用一個簡單的實作
        static $groupCreationTimes = [];

        if (!isset($groupCreationTimes[$groupName])) {
            $groupCreationTimes[$groupName] = time();
            return false;
        }

        return (time() - $groupCreationTimes[$groupName]) > $maxAge;
    }

    /**
     * 批量清空多個分組
     *
     * @param array<string> $groupNames 分組名稱陣列
     * @param bool $cascade 是否級聯清空
     * @return array<string, int> 每個分組清空的項目數量
     */
    public function flushMultipleGroups(array $groupNames, bool $cascade = true): array
    {
        $results = [];

        foreach ($groupNames as $groupName) {
            $results[$groupName] = $this->flushGroup($groupName, $cascade);
        }

        return $results;
    }

    /**
     * 檢查模式匹配
     *
     * @param string $text 要匹配的文字
     * @param string $pattern 匹配模式（支援 * 萬用字元）
     * @return bool 是否匹配
     */
    private function matchPattern(string $text, string $pattern): bool
    {
        // 先替換萬用字元為佔位符，然後轉義，最後替換佔位符為正規表示式
        $placeholder = '___WILDCARD___';
        $escapedPattern = str_replace('*', $placeholder, $pattern);
        $quotedPattern = preg_quote($escapedPattern, '/');
        $regex = '/^' . str_replace($placeholder, '.*', $quotedPattern) . '$/';
        
        return preg_match($regex, $text) === 1;
    }
}
