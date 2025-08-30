<?php

declare(strict_types=1);

namespace Tests\Integration\Shared\Cache\Services;

use App\Shared\Cache\Contracts\TaggedCacheInterface;
use App\Shared\Cache\Contracts\TagRepositoryInterface;
use App\Shared\Cache\Services\TaggedCacheManager;
use App\Shared\Cache\ValueObjects\CacheTag;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;

/**
 * TaggedCacheInterface 實作整合測試
 */
class TaggedCacheIntegrationTest extends TestCase
{
    private TaggedCacheInterface $taggedCache;
    private TagRepositoryInterface $tagRepository;
    private RedisClient $redisClient;
    private string $testPrefix = 'test_cache:';

    protected function setUp(): void
    {
        // 設定 Redis 客戶端（測試環境）
        $this->redisClient = new RedisClient([
            'scheme' => 'tcp',
            'host' => '127.0.0.1',
            'port' => 6379,
            'database' => 15, // 使用不同的測試資料庫
        ]);

        // 清空測試資料庫
        $this->redisClient->flushdb();

        // 建立 TagRepository 和 TaggedCacheManager
        $this->tagRepository = new \App\Shared\Cache\Repositories\RedisTagRepository(
            $this->redisClient,
            $this->testPrefix
        );

        $this->taggedCache = new TaggedCacheManager(
            $this->tagRepository,
            $this->testPrefix
        );
    }

    protected function tearDown(): void
    {
        // 清理測試資料
        $this->redisClient->flushdb();
    }

    public function testBasicTaggedCaching(): void
    {
        $key = 'test_key';
        $value = 'test_value';
        $tags = ['user_123', 'module_posts'];

        // 測試帶標籤的快取儲存
        $result = $this->taggedCache->put($key, $value, 3600, $tags);
        $this->assertTrue($result);

        // 測試標籤是否正確建立
        foreach ($tags as $tagName) {
            $this->assertTrue($this->taggedCache->tagExists($tagName));
        }

        // 測試取得快取值
        $cachedValue = $this->taggedCache->get($key);
        $this->assertEquals($value, $cachedValue);

        // 測試透過標籤取得相關鍵
        $keysByTag = $this->taggedCache->getKeysByTag('user_123');
        $this->assertContains($key, $keysByTag);
    }

    public function testTagFlushing(): void
    {
        // 準備測試資料
        $testData = [
            'user_post_1' => ['value' => 'content1', 'tags' => ['user_123', 'module_posts']],
            'user_post_2' => ['value' => 'content2', 'tags' => ['user_123', 'module_posts']],
            'user_profile' => ['value' => 'profile', 'tags' => ['user_123', 'module_users']],
            'other_data' => ['value' => 'other', 'tags' => ['user_456', 'module_posts']],
        ];

        // 儲存測試資料
        foreach ($testData as $key => $data) {
            $this->taggedCache->put($key, $data['value'], 3600, $data['tags']);
        }

        // 驗證資料已儲存
        foreach (array_keys($testData) as $key) {
            $this->assertNotNull($this->taggedCache->get($key));
        }

        // 清空 user_123 標籤的快取
        $flushedCount = $this->taggedCache->flushByTags('user_123');
        $this->assertEquals(3, $flushedCount); // 應該清空 3 筆資料

        // 驗證 user_123 相關的快取已被清空
        $this->assertNull($this->taggedCache->get('user_post_1'));
        $this->assertNull($this->taggedCache->get('user_post_2'));
        $this->assertNull($this->taggedCache->get('user_profile'));

        // 驗證其他快取仍然存在
        $this->assertNotNull($this->taggedCache->get('other_data'));
    }

    public function testMultipleTagFlushing(): void
    {
        // 準備測試資料
        $keys = ['key1', 'key2', 'key3', 'key4', 'key5'];
        $tags = [
            'key1' => ['tag_a', 'tag_b'],
            'key2' => ['tag_a', 'tag_c'],
            'key3' => ['tag_b', 'tag_c'],
            'key4' => ['tag_c', 'tag_d'],
            'key5' => ['tag_d'],
        ];

        foreach ($keys as $key) {
            $this->taggedCache->put($key, "value_$key", 3600, $tags[$key]);
        }

        // 清空多個標籤
        $flushedCount = $this->taggedCache->flushByTags(['tag_a', 'tag_b']);
        $this->assertEquals(3, $flushedCount); // key1, key2, key3 應該被清空

        // 驗證結果
        $this->assertNull($this->taggedCache->get('key1'));
        $this->assertNull($this->taggedCache->get('key2'));
        $this->assertNull($this->taggedCache->get('key3'));
        $this->assertNotNull($this->taggedCache->get('key4'));
        $this->assertNotNull($this->taggedCache->get('key5'));
    }

    public function testTagStatistics(): void
    {
        // 準備測試資料
        $this->taggedCache->put('user_1_post', 'content1', 3600, ['user_1', 'posts']);
        $this->taggedCache->put('user_1_profile', 'profile1', 3600, ['user_1', 'profiles']);
        $this->taggedCache->put('user_2_post', 'content2', 3600, ['user_2', 'posts']);

        $stats = $this->taggedCache->getTagStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_tags', $stats);
        $this->assertArrayHasKey('tags', $stats);
        $this->assertEquals(3, $stats['total_tags']); // user_1, user_2, posts, profiles

        // 驗證各標籤的統計資訊
        $this->assertArrayHasKey('user_1', $stats['tags']);
        $this->assertArrayHasKey('posts', $stats['tags']);
        $this->assertEquals(2, $stats['tags']['user_1']['key_count']);
        $this->assertEquals(2, $stats['tags']['posts']['key_count']);
    }

    public function testTaggingWithComplexValues(): void
    {
        // 測試複雜資料結構的標籤快取
        $complexValue = [
            'id' => 123,
            'data' => ['nested' => 'value'],
            'timestamp' => time()
        ];

        $key = 'complex_data';
        $tags = ['complex', 'structured', 'user_123'];

        $this->taggedCache->put($key, $complexValue, 3600, $tags);

        $retrievedValue = $this->taggedCache->get($key);
        $this->assertEquals($complexValue, $retrievedValue);

        // 驗證所有標籤都已建立
        foreach ($tags as $tag) {
            $this->assertTrue($this->taggedCache->tagExists($tag));
            $keys = $this->taggedCache->getKeysByTag($tag);
            $this->assertContains($key, $keys);
        }
    }

    public function testTagExpiration(): void
    {
        $key = 'expiring_key';
        $value = 'expiring_value';
        $tags = ['temp_tag'];

        // 設定短過期時間（1秒）
        $this->taggedCache->put($key, $value, 1, $tags);

        // 立即驗證資料存在
        $this->assertEquals($value, $this->taggedCache->get($key));
        $this->assertTrue($this->taggedCache->tagExists('temp_tag'));

        // 等待過期
        sleep(2);

        // 驗證資料已過期
        $this->assertNull($this->taggedCache->get($key));

        // 注意：標籤可能仍存在於標籤儲存庫中，這取決於實作策略
        // 在生產環境中，可能需要定期清理過期的標籤關聯
    }

    public function testConcurrentTagging(): void
    {
        // 測試併發情況下的標籤一致性
        $baseKey = 'concurrent_';
        $sharedTag = 'shared_tag';

        // 模擬併發寫入
        for ($i = 0; $i < 10; $i++) {
            $key = $baseKey . $i;
            $this->taggedCache->put($key, "value_$i", 3600, [$sharedTag, "unique_$i"]);
        }

        // 驗證所有鍵都與共享標籤關聯
        $keysWithSharedTag = $this->taggedCache->getKeysByTag($sharedTag);
        $this->assertCount(10, $keysWithSharedTag);

        for ($i = 0; $i < 10; $i++) {
            $expectedKey = $baseKey . $i;
            $this->assertContains($expectedKey, $keysWithSharedTag);
        }

        // 清空共享標籤，應該清空所有相關的快取
        $flushedCount = $this->taggedCache->flushByTags($sharedTag);
        $this->assertEquals(10, $flushedCount);

        // 驗證所有資料都已被清空
        for ($i = 0; $i < 10; $i++) {
            $this->assertNull($this->taggedCache->get($baseKey . $i));
        }
    }

    public function testTagTypeClassification(): void
    {
        // 測試不同類型標籤的分類
        $testCases = [
            'user_123' => CacheTag::TYPE_USER,
            'role_admin' => CacheTag::TYPE_ROLE,
            'module_posts' => CacheTag::TYPE_MODULE,
            'temporal_daily' => CacheTag::TYPE_TEMPORAL,
            'custom_special' => CacheTag::TYPE_CUSTOM
        ];

        foreach ($testCases as $tagName => $expectedType) {
            $this->taggedCache->put("test_key_$tagName", 'value', 3600, [$tagName]);

            // 驗證標籤類型正確分類
            $tag = CacheTag::create($tagName);
            $this->assertEquals($expectedType, $tag->getType());
        }

        // 測試標籤統計中的類型分組
        $stats = $this->taggedCache->getTagStatistics();
        $this->assertArrayHasKey('tag_types', $stats);

        foreach ($testCases as $tagName => $expectedType) {
            $this->assertArrayHasKey($expectedType, $stats['tag_types']);
        }
    }

    public function testErrorRecovery(): void
    {
        // 測試在部分操作失敗時的錯誤恢復
        $key = 'error_test_key';
        $value = 'error_test_value';
        $tags = ['valid_tag', 'another_tag'];

        // 正常儲存
        $this->assertTrue($this->taggedCache->put($key, $value, 3600, $tags));

        // 嘗試使用無效標籤（空字串）
        $invalidTags = ['valid_tag', '', 'another_tag'];
        $result = $this->taggedCache->put($key . '_invalid', $value, 3600, $invalidTags);

        // 根據實作，這可能成功（忽略無效標籤）或失敗
        // 這裡我們測試系統的一致性
        if ($result) {
            // 如果操作成功，有效標籤應該仍然有效
            $this->assertTrue($this->taggedCache->tagExists('valid_tag'));
            $this->assertTrue($this->taggedCache->tagExists('another_tag'));
        }

        // 原始資料應該不受影響
        $this->assertEquals($value, $this->taggedCache->get($key));
    }
}
