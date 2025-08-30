<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Cache\Services;

use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Contracts\TaggedCacheInterface;
use App\Shared\Cache\Services\CacheGroupManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * CacheGroupManager 測試（修正版）
 */
class CacheGroupManagerTest extends TestCase
{
    private CacheGroupManager $groupManager;
    private TaggedCacheInterface&MockObject $taggedCache;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->taggedCache = $this->createMock(TaggedCacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // 設定通用的 tags() 方法模擬 - 回傳 mock 本身
        $this->taggedCache->method('tags')
            ->willReturn($this->taggedCache);

        $this->groupManager = new CacheGroupManager($this->taggedCache, $this->logger);
    }

    public function testCreateGroup(): void
    {
        $groupName = 'test_group';
        $tags = ['tag1', 'tag2'];

        // 驗證分組不存在
        $this->assertFalse($this->groupManager->hasGroup($groupName));

        // 建立分組
        $groupCache = $this->groupManager->group($groupName, $tags);

        // 驗證回傳的是 TaggedCacheInterface
        $this->assertInstanceOf(TaggedCacheInterface::class, $groupCache);

        // 驗證分組已建立
        $this->assertTrue($this->groupManager->hasGroup($groupName));
    }

    public function testGetExistingGroup(): void
    {
        $groupName = 'existing_group';
        $tags = ['tag1'];

        // 建立分組
        $originalGroup = $this->groupManager->group($groupName, $tags);

        // 重複呼叫應該回傳相同的分組
        $retrievedGroup = $this->groupManager->getGroup($groupName);

        $this->assertSame($originalGroup, $retrievedGroup);
    }

    public function testGetNonexistentGroup(): void
    {
        $result = $this->groupManager->getGroup('nonexistent_group');
        $this->assertNull($result);
    }

    public function testFlushGroup(): void
    {
        $groupName = 'test_group';
        $tags = ['tag1', 'tag2'];

        // 建立分組
        $this->groupManager->group($groupName, $tags);

        // 模擬標籤清空回傳計數（注意：flushByTags 需要接收陣列）
        $this->taggedCache->method('flushByTags')
            ->with($this->callback(function($tagsParam) {
                return is_array($tagsParam) && in_array('group_test_group', $tagsParam);
            }))
            ->willReturn(5);

        $clearedCount = $this->groupManager->flushGroup($groupName);

        $this->assertEquals(5, $clearedCount);
    }

    public function testFlushGroupWithCascade(): void
    {
        $parentGroup = 'parent_group';
        $childGroup = 'child_group';
        $parentTags = ['parent_tag'];
        $childTags = ['child_tag'];

        // 建立分組
        $this->groupManager->group($parentGroup, $parentTags);
        $this->groupManager->group($childGroup, $childTags);

        // 設定依賴關係
        $this->groupManager->setDependencies($parentGroup, [$childGroup]);

        // 模擬清空操作 - 注意 flushByTags 接收陣列參數
        $this->taggedCache->method('flushByTags')
            ->willReturnCallback(function($tags) {
                if (is_array($tags) && in_array('group_parent_group', $tags)) {
                    return 3;
                } elseif (is_array($tags) && in_array('group_child_group', $tags)) {
                    return 2;
                }
                return 0;
            });

        $totalCleared = $this->groupManager->flushGroup($parentGroup, true);

        $this->assertEquals(5, $totalCleared); // 3 + 2
    }

    public function testRemoveGroup(): void
    {
        $groupName = 'removable_group';
        $tags = ['tag1'];

        // 建立分組
        $this->groupManager->group($groupName, $tags);
        $this->assertTrue($this->groupManager->hasGroup($groupName));

        // 移除分組
        $result = $this->groupManager->removeGroup($groupName);
        $this->assertTrue($result);
        $this->assertFalse($this->groupManager->hasGroup($groupName));
    }

    public function testSetAndGetDependencies(): void
    {
        $parentGroup = 'parent';
        $children = ['child1', 'child2'];

        // 建立所有分組
        $this->groupManager->group($parentGroup, ['parent_tag']);
        foreach ($children as $child) {
            $this->groupManager->group($child, ["{$child}_tag"]);
        }

        // 設定依賴關係
        $this->groupManager->setDependencies($parentGroup, $children);

        // 驗證依賴關係
        $retrievedChildren = $this->groupManager->getDependencies($parentGroup);
        $this->assertEquals($children, $retrievedChildren);
    }

    public function testGetAllGroups(): void
    {
        $groups = ['group1', 'group2', 'group3'];

        // 建立多個分組
        foreach ($groups as $group) {
            $this->groupManager->group($group, ["{$group}_tag"]);
        }

        $allGroups = $this->groupManager->getAllGroups();
        $this->assertEquals($groups, $allGroups);
    }

    public function testGetGroupStatistics(): void
    {
        $groups = ['group1', 'group2'];

        // 建立分組
        foreach ($groups as $group) {
            $this->groupManager->group($group, ["{$group}_tag"]);
        }

        $stats = $this->groupManager->getGroupStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_groups', $stats);
        $this->assertArrayHasKey('groups', $stats);
        $this->assertEquals(2, $stats['total_groups']);
    }

    public function testUserGroup(): void
    {
        $userId = 123;
        $userGroup = $this->groupManager->userGroup($userId);

        $this->assertInstanceOf(TaggedCacheInterface::class, $userGroup);
        $this->assertTrue($this->groupManager->hasGroup("user_{$userId}"));
    }

    public function testModuleGroup(): void
    {
        $module = 'posts';
        $moduleGroup = $this->groupManager->moduleGroup($module);

        $this->assertInstanceOf(TaggedCacheInterface::class, $moduleGroup);
        $this->assertTrue($this->groupManager->hasGroup("module_{$module}"));
    }

    public function testTemporalGroup(): void
    {
        $period = 'daily';
        $temporalGroup = $this->groupManager->temporalGroup($period);

        $this->assertInstanceOf(TaggedCacheInterface::class, $temporalGroup);
        $this->assertTrue($this->groupManager->hasGroup("temporal_{$period}"));
    }

    public function testFlushMultipleGroups(): void
    {
        $groups = ['group1', 'group2'];

        // 建立分組
        foreach ($groups as $group) {
            $this->groupManager->group($group, ["{$group}_tag"]);
        }

        // 模擬清空回傳值
        $this->taggedCache->method('flushByTags')
            ->willReturnCallback(function($tags) {
                if (is_array($tags)) {
                    return count($tags) * 2; // 每個標籤清空 2 個項目
                }
                return 0;
            });

        $results = $this->groupManager->flushMultipleGroups($groups);
        
        // 驗證結果結構
        $this->assertIsArray($results);
        $this->assertArrayHasKey('group1', $results);
        $this->assertArrayHasKey('group2', $results);
        $this->assertEquals(2, $results['group1']); // 1 tag * 2 items
        $this->assertEquals(2, $results['group2']); // 1 tag * 2 items
    }

    public function testFlushByPattern(): void
    {
        // 建立符合模式的分組
        $this->groupManager->group('user_123', ['user_tag']);
        $this->groupManager->group('user_456', ['user_tag']);
        $this->groupManager->group('module_posts', ['module_tag']);

        // 檢查所有分組是否都已建立
        $allGroups = $this->groupManager->getAllGroups();
        $this->assertContains('user_123', $allGroups);
        $this->assertContains('user_456', $allGroups);
        $this->assertContains('module_posts', $allGroups);

        // 模擬清空 - 每個分組清空 2 個項目
        $this->taggedCache->method('flushByTags')
            ->willReturnCallback(function($tags) {
                // 檢查是否為分組標籤
                if (is_array($tags) && count($tags) === 1) {
                    $tag = $tags[0];
                    // 如果是 user_* 分組標籤，返回 2
                    // 注意：CacheTag::group("user_123") 經過 normalizeName 後變成 "group_user_123"
                    if (strpos($tag, 'group_user_') === 0) {
                        return 2;
                    }
                }
                return 0;
            });

        $clearedCount = $this->groupManager->flushByPattern('user_*');
        $this->assertEquals(4, $clearedCount); // 2 user groups * 2 items each
    }

    public function testInvalidationRules(): void
    {
        $groupName = 'test_group';
        $tags = ['tag1'];
        $rules = [
            'max_age' => 3600,
            'invalidate_on' => ['user_update']
        ];

        $this->groupManager->group($groupName, $tags);
        $this->groupManager->setInvalidationRules($groupName, $rules);

        $retrievedRules = $this->groupManager->getInvalidationRules($groupName);
        $this->assertEquals($rules, $retrievedRules);
    }

    public function testCheckInvalidationRules(): void
    {
        $groupName = 'test_group';
        $this->groupManager->group($groupName, ['tag1']);

        // 設定過期規則
        $this->groupManager->setInvalidationRules($groupName, [
            'max_age' => 1, // 1 秒後過期
        ]);

        // 立即檢查，不應該失效
        $this->assertFalse($this->groupManager->shouldInvalidate($groupName));

        // 等待過期
        sleep(2);

        // 現在應該失效
        $this->assertTrue($this->groupManager->shouldInvalidate($groupName));
    }

    public function testLoggingCalls(): void
    {
        $groupName = 'logged_group';

        // 驗證記錄檔會被呼叫
        $this->logger->expects($this->atLeastOnce())
            ->method('info');

        $this->groupManager->group($groupName, ['tag1']);
        $this->groupManager->flushGroup($groupName);
    }

    public function testErrorHandling(): void
    {
        // 嘗試清空不存在的分組
        $clearedCount = $this->groupManager->flushGroup('nonexistent_group');
        $this->assertEquals(0, $clearedCount);

        // 嘗試移除不存在的分組
        $result = $this->groupManager->removeGroup('nonexistent_group');
        $this->assertFalse($result);
    }

    public function testGroupLifecycle(): void
    {
        $groupName = 'lifecycle_group';
        $tags = ['lifecycle_tag'];

        // 1. 建立分組
        $group = $this->groupManager->group($groupName, $tags);
        $this->assertInstanceOf(TaggedCacheInterface::class, $group);
        $this->assertTrue($this->groupManager->hasGroup($groupName));

        // 2. 設定依賴關係
        $this->groupManager->setDependencies($groupName, []);

        // 3. 設定失效規則
        $rules = ['max_age' => 3600];
        $this->groupManager->setInvalidationRules($groupName, $rules);
        $this->assertEquals($rules, $this->groupManager->getInvalidationRules($groupName));

        // 4. 清空分組（設定 mock 回傳值）
        $this->taggedCache->method('flushByTags')
            ->willReturnCallback(function($tags) {
                return is_array($tags) ? 1 : 0;
            });
        
        $clearedCount = $this->groupManager->flushGroup($groupName);
        $this->assertEquals(1, $clearedCount);

        // 5. 驗證分組已被移除
        $this->assertFalse($this->groupManager->hasGroup($groupName));
    }
}
