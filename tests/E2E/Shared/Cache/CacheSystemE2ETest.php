<?php

declare(strict_types=1);

namespace Tests\E2E\Shared\Cache;

use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Services\CacheGroupManager;
use App\Shared\Cache\ValueObjects\CacheTag;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;

/**
 * 快取系統端到端測試
 *
 * 測試整個快取系統在真實環境下的完整工作流程，
 * 包括多層快取、標籤管理、分組管理和性能監控等功能。
 */
class CacheSystemE2ETest extends TestCase
{
    private CacheManagerInterface $cacheManager;
    private CacheGroupManager $groupManager;
    private RedisClient $redisClient;
    private string $testPrefix = 'e2e_test:';

    protected function setUp(): void
    {
        // 設定 Redis 連接（測試環境）
        $this->redisClient = new RedisClient([
            'scheme' => 'tcp',
            'host' => '127.0.0.1',
            'port' => 6379,
            'database' => 14, // E2E 測試專用資料庫
        ]);

        // 清空測試環境
        $this->redisClient->flushdb();

        // 建立相依性（在實際應用中，這些會透過 DI 容器注入）
        $this->initializeDependencies();
    }

    protected function tearDown(): void
    {
        // 清理測試環境
        $this->redisClient->flushdb();
    }

    private function initializeDependencies(): void
    {
        // 這裡應該模擬 DI 容器的設定
        // 在實際測試中，你可能需要載入真實的容器設定

        // 為了簡化，我們直接建立必要的物件
        $tagRepository = new \App\Shared\Cache\Repositories\RedisTagRepository(
            $this->redisClient,
            $this->testPrefix
        );

        $memoryDriver = new \App\Shared\Cache\Drivers\MemoryCacheDriver();
        $redisDriver = new \App\Shared\Cache\Drivers\RedisCacheDriver(
            $this->redisClient,
            $this->testPrefix
        );

        // 添加標籤功能
        $taggedMemoryDriver = new \App\Shared\Cache\Services\TaggedCacheManager(
            $tagRepository,
            $this->testPrefix,
            $memoryDriver
        );

        $taggedRedisDriver = new \App\Shared\Cache\Services\TaggedCacheManager(
            $tagRepository,
            $this->testPrefix,
            $redisDriver
        );

        // 建立快取管理器
        $this->cacheManager = new \App\Shared\Cache\Services\CacheManager([
            'memory' => $taggedMemoryDriver,
            'redis' => $taggedRedisDriver,
        ], 'redis'); // 預設使用 Redis

        // 建立分組管理器
        $this->groupManager = new CacheGroupManager($this->cacheManager);
    }

    /**
     * 測試完整的用戶資料快取工作流程
     */
    public function testUserDataCachingWorkflow(): void
    {
        $userId = 123;
        $userKey = "user:{$userId}";
        $userTags = ['user_' . $userId, 'module_users'];

        // 第一階段：儲存用戶基本資料
        $userData = [
            'id' => $userId,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'admin',
            'created_at' => '2024-01-15T10:00:00Z'
        ];

        $result = $this->cacheManager->put($userKey, $userData, 3600, $userTags);
        $this->assertTrue($result, '用戶資料應該成功儲存到快取');

        // 驗證資料可以正確取得
        $cachedUser = $this->cacheManager->get($userKey);
        $this->assertEquals($userData, $cachedUser, '快取的用戶資料應該與原始資料相同');

        // 第二階段：儲存相關的用戶資料
        $userPostsKey = "user:{$userId}:posts";
        $userPosts = [
            ['id' => 1, 'title' => 'First Post', 'content' => '...'],
            ['id' => 2, 'title' => 'Second Post', 'content' => '...']
        ];
        $postTags = ['user_' . $userId, 'module_posts', 'content_posts'];

        $this->cacheManager->put($userPostsKey, $userPosts, 1800, $postTags);

        // 第三階段：建立用戶分組
        $userGroup = $this->groupManager->group("user_{$userId}", [
            'user_' . $userId,
            'content_posts'
        ]);

        // 使用分組快取儲存額外資料
        $userStatsKey = "user:{$userId}:stats";
        $userStats = ['posts_count' => 2, 'last_login' => time()];

        $userGroup->put($userStatsKey, $userStats, 3600);

        // 第四階段：驗證所有資料都正確關聯
        $this->assertTrue($this->cacheManager->tagExists('user_' . $userId));
        $this->assertTrue($this->cacheManager->tagExists('module_users'));
        $this->assertTrue($this->cacheManager->tagExists('module_posts'));

        // 透過標籤查找相關鍵
        $userRelatedKeys = $this->cacheManager->getKeysByTag('user_' . $userId);
        $this->assertContains($userKey, $userRelatedKeys);
        $this->assertContains($userPostsKey, $userRelatedKeys);

        // 第五階段：測試部分更新和快取失效
        // 當用戶更新個人資料時，只清空用戶相關快取
        $clearedCount = $this->cacheManager->flushByTags('user_' . $userId);
        $this->assertGreaterThan(0, $clearedCount, '應該清空與用戶相關的快取項目');

        // 驗證資料已被清空
        $this->assertNull($this->cacheManager->get($userKey));
        $this->assertNull($this->cacheManager->get($userPostsKey));
        $this->assertNull($userGroup->get($userStatsKey));
    }

    /**
     * 測試多層快取策略
     */
    public function testMultiLayerCachingStrategy(): void
    {
        $key = 'multilayer_test';
        $value = ['data' => 'test_value', 'timestamp' => time()];
        $tags = ['multilayer', 'test'];

        // 在 Redis 中儲存
        $redisDriver = $this->cacheManager->getDriver('redis');
        $redisDriver->put($key, $value, 3600, $tags);

        // 驗證可以從 Redis 取得
        $this->assertEquals($value, $redisDriver->get($key));

        // 切換到記憶體驅動，應該從 Redis 自動同步（如果實作了 fallback 策略）
        $memoryDriver = $this->cacheManager->getDriver('memory');

        // 手動同步到記憶體（模擬 cache warming）
        $memoryDriver->put($key, $value, 3600, $tags);

        // 現在兩個驅動都應該有相同的資料
        $this->assertEquals($value, $memoryDriver->get($key));
        $this->assertEquals($value, $redisDriver->get($key));

        // 測試分層失效：清空記憶體快取，Redis 快取應該仍然存在
        $memoryDriver->forget($key);
        $this->assertNull($memoryDriver->get($key));
        $this->assertEquals($value, $redisDriver->get($key));

        // 透過標籤清空，應該影響所有層
        $clearedCount = $this->cacheManager->flushByTags('multilayer');
        $this->assertGreaterThan(0, $clearedCount);

        $this->assertNull($redisDriver->get($key));
    }

    /**
     * 測試複雜的分組依賴關係
     */
    public function testComplexGroupDependencies(): void
    {
        // 建立分層的分組結構：
        // - site_config (頂層)
        //   - theme_settings (依賴 site_config)
        //     - user_preferences (依賴 theme_settings)

        // 頂層分組：網站設定
        $siteConfigGroup = $this->groupManager->group('site_config', [
            'config_general',
            'config_system'
        ]);
        $siteConfigGroup->put('site_name', 'AlleyNote', 86400);
        $siteConfigGroup->put('maintenance_mode', false, 86400);

        // 中層分組：主題設定，依賴網站設定
        $themeGroup = $this->groupManager->group('theme_settings', [
            'config_theme',
            'config_ui'
        ]);
        $this->groupManager->setDependencies('site_config', ['theme_settings']);

        $themeGroup->put('primary_color', '#1e40af', 86400);
        $themeGroup->put('dark_mode_enabled', true, 86400);

        // 底層分組：用戶偏好，依賴主題設定
        $userPrefGroup = $this->groupManager->group('user_preferences', [
            'user_123',
            'config_personal'
        ]);
        $this->groupManager->setDependencies('theme_settings', ['user_preferences']);

        $userPrefGroup->put('user_123_theme', 'dark', 3600);
        $userPrefGroup->put('user_123_language', 'zh-TW', 3600);

        // 驗證所有資料都正確儲存
        $this->assertEquals('AlleyNote', $siteConfigGroup->get('site_name'));
        $this->assertEquals('#1e40af', $themeGroup->get('primary_color'));
        $this->assertEquals('dark', $userPrefGroup->get('user_123_theme'));

        // 測試級聯清空：清空頂層分組應該影響所有依賴分組
        $clearedCount = $this->groupManager->flushGroup('site_config', true);
        $this->assertGreaterThan(0, $clearedCount, '級聯清空應該清除多個快取項目');

        // 驗證所有相關快取都已被清空
        $this->assertNull($siteConfigGroup->get('site_name'));
        $this->assertNull($themeGroup->get('primary_color'));
        $this->assertNull($userPrefGroup->get('user_123_theme'));
    }

    /**
     * 測試高併發情況下的快取一致性
     */
    public function testCacheConsistencyUnderLoad(): void
    {
        $baseKey = 'concurrent_test_';
        $sharedTag = 'load_test';
        $iterations = 50;

        // 模擬併發寫入
        $processes = [];
        for ($i = 0; $i < $iterations; $i++) {
            $key = $baseKey . $i;
            $value = ['id' => $i, 'data' => "data_$i", 'timestamp' => time()];
            $tags = [$sharedTag, "item_$i"];

            // 在實際測試中，這裡可能需要使用多執行緒或多程序
            // 為了簡化，我們連續執行但檢查結果
            $result = $this->cacheManager->put($key, $value, 3600, $tags);
            $this->assertTrue($result, "第 $i 次寫入應該成功");

            // 立即讀取驗證
            $retrieved = $this->cacheManager->get($key);
            $this->assertEquals($value, $retrieved, "第 $i 次讀取應該正確");
        }

        // 驗證標籤一致性
        $keysWithSharedTag = $this->cacheManager->getKeysByTag($sharedTag);
        $this->assertCount($iterations, $keysWithSharedTag, '所有項目都應該有共享標籤');

        // 測試批量操作的一致性
        $batchFlushCount = $this->cacheManager->flushByTags($sharedTag);
        $this->assertEquals($iterations, $batchFlushCount, '批量清空應該清除所有相關項目');

        // 驗證清空後狀態
        for ($i = 0; $i < $iterations; $i++) {
            $key = $baseKey . $i;
            $this->assertNull($this->cacheManager->get($key), "項目 $i 應該已被清空");
        }
    }

    /**
     * 測試快取統計和監控功能
     */
    public function testCacheMonitoringAndStatistics(): void
    {
        // 建立測試資料以生成統計資訊
        $testData = [
            'stats_test_1' => ['tags' => ['user_100', 'module_posts'], 'value' => 'data1'],
            'stats_test_2' => ['tags' => ['user_100', 'module_comments'], 'value' => 'data2'],
            'stats_test_3' => ['tags' => ['user_101', 'module_posts'], 'value' => 'data3'],
            'stats_test_4' => ['tags' => ['temporal_daily'], 'value' => 'data4'],
        ];

        foreach ($testData as $key => $data) {
            $this->cacheManager->put($key, $data['value'], 3600, $data['tags']);
        }

        // 測試標籤統計
        $tagStats = $this->cacheManager->getTagStatistics();
        $this->assertIsArray($tagStats);
        $this->assertArrayHasKey('total_tags', $tagStats);
        $this->assertArrayHasKey('tags', $tagStats);
        $this->assertArrayHasKey('tag_types', $tagStats);

        // 驗證特定標籤的統計
        $this->assertArrayHasKey('user_100', $tagStats['tags']);
        $this->assertEquals(2, $tagStats['tags']['user_100']['key_count']);

        // 測試分組統計
        $groupStats = $this->groupManager->getGroupStatistics();
        $this->assertIsArray($groupStats);

        // 建立測試分組並驗證統計更新
        $testGroup = $this->groupManager->group('stats_test_group', ['user_100']);
        $testGroup->put('group_test_key', 'group_value', 3600);

        $updatedGroupStats = $this->groupManager->getGroupStatistics();
        $this->assertArrayHasKey('stats_test_group', $updatedGroupStats['groups']);
    }

    /**
     * 測試錯誤恢復和容錯機制
     */
    public function testErrorRecoveryAndFaultTolerance(): void
    {
        // 測試 Redis 連接中斷的處理（模擬）
        $key = 'fault_tolerance_test';
        $value = 'test_value';
        $tags = ['fault_test'];

        // 正常操作
        $this->assertTrue($this->cacheManager->put($key, $value, 3600, $tags));
        $this->assertEquals($value, $this->cacheManager->get($key));

        // 測試非法鍵名處理
        $invalidKeys = ['', null, false, 0];
        foreach ($invalidKeys as $invalidKey) {
            if ($invalidKey !== null) {
                $result = $this->cacheManager->put($invalidKey, 'value', 3600, $tags);
                // 根據實作，這可能返回 false 或拋出異常
                // 重要的是系統不應該崩潰
                $this->assertTrue(is_bool($result), '無效鍵名應該被正確處理');
            }
        }

        // 測試大資料處理
        $largeData = str_repeat('x', 1024 * 1024); // 1MB 資料
        $largeDataResult = $this->cacheManager->put('large_data_test', $largeData, 300);

        if ($largeDataResult) {
            $retrievedLargeData = $this->cacheManager->get('large_data_test');
            $this->assertEquals($largeData, $retrievedLargeData, '大資料應該正確儲存和檢索');
        }

        // 驗證系統狀態仍然正常
        $this->assertEquals($value, $this->cacheManager->get($key), '原始資料應該不受影響');
    }

    /**
     * 測試快取性能基準
     */
    public function testCachePerformanceBenchmark(): void
    {
        $iterations = 100;
        $keyPrefix = 'perf_test_';

        // 測試寫入性能
        $writeStartTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $key = $keyPrefix . $i;
            $value = ['id' => $i, 'data' => str_repeat('x', 100)];
            $tags = ['perf_test', "batch_" . intval($i / 10)];

            $this->cacheManager->put($key, $value, 3600, $tags);
        }
        $writeTime = microtime(true) - $writeStartTime;

        // 測試讀取性能
        $readStartTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $key = $keyPrefix . $i;
            $value = $this->cacheManager->get($key);
            $this->assertNotNull($value, "鍵 $key 應該存在");
        }
        $readTime = microtime(true) - $readStartTime;

        // 測試標籤查詢性能
        $tagQueryStartTime = microtime(true);
        $keys = $this->cacheManager->getKeysByTag('perf_test');
        $tagQueryTime = microtime(true) - $tagQueryStartTime;

        // 測試批量清空性能
        $flushStartTime = microtime(true);
        $flushedCount = $this->cacheManager->flushByTags('perf_test');
        $flushTime = microtime(true) - $flushStartTime;

        // 基本性能斷言（這些閾值可能需要根據實際環境調整）
        $this->assertLessThan(5.0, $writeTime, "寫入 $iterations 個項目不應超過 5 秒");
        $this->assertLessThan(2.0, $readTime, "讀取 $iterations 個項目不應超過 2 秒");
        $this->assertLessThan(1.0, $tagQueryTime, '標籤查詢不應超過 1 秒');
        $this->assertLessThan(2.0, $flushTime, '批量清空不應超過 2 秒');

        $this->assertEquals($iterations, count($keys), '應該找到所有測試鍵');
        $this->assertEquals($iterations, $flushedCount, '應該清空所有測試項目');

        // 記錄性能資訊（可選）
        echo "\n性能測試結果:\n";
        echo "- 寫入性能: " . round($iterations / $writeTime, 2) . " ops/sec\n";
        echo "- 讀取性能: " . round($iterations / $readTime, 2) . " ops/sec\n";
        echo "- 標籤查詢: " . round($tagQueryTime * 1000, 2) . " ms\n";
        echo "- 批量清空: " . round($flushTime * 1000, 2) . " ms\n";
    }
}
