<?php

declare(strict_types=1);

namespace Tests\Integration\Shared\Cache\Services;

use App\Shared\Cache\Contracts\TaggedCacheInterface;
use App\Shared\Cache\Contracts\TagRepositoryInterface;
use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Services\TaggedCacheManager;
use App\Shared\Cache\Services\CacheManager;
use App\Shared\Cache\Strategies\DefaultCacheStrategy;
use App\Shared\Cache\Drivers\MemoryCacheDriver;
use App\Shared\Cache\Repositories\MemoryTagRepository;
use App\Shared\Cache\ValueObjects\CacheTag;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * TaggedCacheInterface 實作整合測試
 */
class TaggedCacheIntegrationTest extends TestCase
{
    private TaggedCacheInterface $taggedCache;
    private TagRepositoryInterface $tagRepository;
    private CacheManagerInterface $cacheManager;
    private string $testPrefix = 'test_cache:';

    protected function setUp(): void
    {
        // 建立 CacheManager 與 MemoryCacheDriver
        $memoryDriver = new MemoryCacheDriver();

        // 建立一個簡單的策略，不會調整 TTL（測試專用）
        $strategy = new class implements \App\Shared\Cache\Contracts\CacheStrategyInterface {
            public function shouldCache(string $key, mixed $value, int $ttl): bool {
                return true;
            }

            public function selectDriver(array $drivers, string $key, mixed $value): ?\App\Shared\Cache\Contracts\CacheDriverInterface {
                return reset($drivers) ?: null;
            }

            public function decideTtl(string $key, mixed $value, int $requestedTtl): int {
                return $requestedTtl; // 不調整 TTL
            }

            public function handleMiss(string $key, callable $callback): mixed {
                return $callback();
            }

            public function handleDriverFailure(
                \App\Shared\Cache\Contracts\CacheDriverInterface $failedDriver,
                array $availableDrivers,
                string $operation,
                array $params
            ): mixed {
                return null;
            }

            public function getStats(): array {
                return [];
            }

            public function resetStats(): void {
                // Nothing to reset
            }
        };

        $this->cacheManager = new CacheManager($strategy, new NullLogger());
        $this->cacheManager->addDriver('memory', $memoryDriver);
        $this->cacheManager->setDefaultDriver('memory');

        // 建立 MemoryTagRepository
        $this->tagRepository = new MemoryTagRepository();

        // 建立 TaggedCacheManager
        $this->taggedCache = new TaggedCacheManager(
            $this->cacheManager,
            $this->tagRepository,
            new NullLogger()
        );
    }

    protected function tearDown(): void
    {
        // 清理測試資料
        $this->cacheManager->clear();
        $this->tagRepository->flush();
    }

    public function testBasicTaggedCaching(): void
    {
        $key = 'test_key';
        $value = 'test_value';
        $tags = ['user_123', 'module_posts'];

        // 測試帶標籤的快取儲存
        $result = $this->taggedCache->putWithTags($key, $value, $tags, 3600);
        $this->assertTrue($result);

        // 測試標籤是否正確建立
        foreach ($tags as $tagName) {
            $this->assertTrue($this->tagRepository->tagExists($tagName));
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
            $this->taggedCache->putWithTags($key, $data['value'], $data['tags'], 3600);
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
            $this->taggedCache->putWithTags($key, "value_$key", $tags[$key], 3600);
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
        $this->taggedCache->putWithTags('user_1_post', 'content1', ['user_1', 'posts'], 3600);
        $this->taggedCache->putWithTags('user_1_profile', 'profile1', ['user_1', 'profiles'], 3600);
        $this->taggedCache->putWithTags('user_2_post', 'content2', ['user_2', 'posts'], 3600);

        $stats = $this->taggedCache->getTagStatistics();

        // 修正：TaggedCacheManager.getTagStatistics() 回傳的是簡單的標籤統計
        $this->assertArrayHasKey('user_1', $stats);
        $this->assertArrayHasKey('posts', $stats);
        $this->assertEquals(2, $stats['user_1']);  // user_1 有 2 個快取項目
        $this->assertEquals(2, $stats['posts']);   // posts 有 2 個快取項目
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

        $this->taggedCache->putWithTags($key, $complexValue, $tags, 3600);

        $retrievedValue = $this->taggedCache->get($key);
        $this->assertEquals($complexValue, $retrievedValue);

        // 驗證所有標籤都已建立
        foreach ($tags as $tag) {
            $this->assertTrue($this->tagRepository->tagExists($tag));
            $keys = $this->taggedCache->getKeysByTag($tag);
            $this->assertContains($key, $keys);
        }
    }

    public function testTagExpiration(): void
    {
        $key = $this->testPrefix . 'expiring_key';
        $value = 'expiring_value';
        $tags = ['temp_tag'];

        // 使用 2 秒 TTL 測試過期
        $ttl = 2;

        $putResult = $this->taggedCache->putWithTags($key, $value, $tags, $ttl);
        $this->assertTrue($putResult);

        // 立即驗證資料存在
        $retrievedValue = $this->taggedCache->get($key);
        $this->assertEquals($value, $retrievedValue);
        $this->assertTrue($this->tagRepository->tagExists('temp_tag'));

        // 等待過期
        sleep($ttl + 1);

        // 驗證資料已過期
        $retrievedValue = $this->taggedCache->get($key);
        $this->assertNull($retrievedValue);

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
            $this->taggedCache->putWithTags($key, "value_$i", [$sharedTag, "unique_$i"], 3600);
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
            'user:123' => 'isUserTag',
            'module:posts' => 'isModuleTag',
            'time:daily' => 'isTemporalTag',
            'group:admins' => 'isGroupTag',
        ];

        foreach ($testCases as $tagName => $typeMethod) {
            $this->taggedCache->putWithTags("test_key_$tagName", 'value', [$tagName], 3600);

            // 驗證標籤類型正確分類
            $tag = new CacheTag($tagName);
            $this->assertTrue($tag->$typeMethod());
        }

        // 測試標籤統計
        $stats = $this->taggedCache->getTagStatistics();
        foreach ($testCases as $tagName => $typeMethod) {
            $this->assertArrayHasKey($tagName, $stats);
        }
    }

    public function testErrorRecovery(): void
    {
        // 測試在部分操作失敗時的錯誤恢復
        $key = 'error_test_key';
        $value = 'error_test_value';
        $tags = ['valid_tag', 'another_tag'];

        // 正常儲存
        $this->assertTrue($this->taggedCache->putWithTags($key, $value, $tags, 3600));

        // 嘗試使用無效標籤（空字串） - 應該拋出例外
        $invalidTags = ['valid_tag', '', 'another_tag'];

        $this->expectException(\InvalidArgumentException::class);
        $this->taggedCache->putWithTags($key . '_invalid', $value, $invalidTags, 3600);
        $this->assertEquals($value, $this->taggedCache->get($key));
    }
}
