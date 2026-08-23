<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Cache\Drivers;

use App\Shared\Cache\Drivers\MemoryCacheDriver;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\Support\UnitTestCase;

/**
 * MemoryCacheDriver 單元測試.
 */
final class MemoryCacheDriverTest extends UnitTestCase
{
    private MemoryCacheDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = new MemoryCacheDriver();
    }

    #[Test]
    public function putAndGetRoundTrip(): void
    {
        $this->assertTrue($this->driver->put('key', 'value', 60));
        $this->assertSame('value', $this->driver->get('key'));
        $this->assertTrue($this->driver->has('key'));

        // 複雜型別
        $data = ['nested' => ['a' => 1]];
        $this->driver->put('complex', $data, 60);
        $this->assertSame($data, $this->driver->get('complex'));
    }

    #[Test]
    public function getReturnsDefaultForMissingKey(): void
    {
        $this->assertNull($this->driver->get('missing'));
        $this->assertSame('fallback', $this->driver->get('missing', 'fallback'));
        $this->assertFalse($this->driver->has('missing'));
    }

    #[Test]
    public function expiredEntryIsTreatedAsMissAndRemoved(): void
    {
        // 以反射將過期時間改為過去，避免等待真實 TTL
        $this->driver->put('stale', 'value', 60);
        $reflection = new ReflectionClass($this->driver);
        $cache = $reflection->getProperty('cache');
        /** @var array<string, array{value: mixed, expires_at: int}> $entries */
        $entries = $cache->getValue($this->driver);
        $entries['stale']['expires_at'] = time() - 10;
        $cache->setValue($this->driver, $entries);

        $this->assertNull($this->driver->get('stale'));
        $this->assertFalse($this->driver->has('stale'));
        // 過期項目應在存取時被移除
        /** @var array<string, array{value: mixed, expires_at: int}> $afterEntries */
        $afterEntries = $cache->getValue($this->driver);
        $this->assertArrayNotHasKey('stale', $afterEntries);
    }

    #[Test]
    public function forgetRemovesEntry(): void
    {
        $this->driver->put('gone', 'value', 60);
        $this->assertTrue($this->driver->forget('gone'));
        $this->assertFalse($this->driver->forget('never-there'));
        $this->assertFalse($this->driver->has('gone'));
    }

    #[Test]
    public function flushClearsEverything(): void
    {
        $this->driver->putMany(['a' => 1, 'b' => 2], 60);
        $this->assertTrue($this->driver->flush());
        $this->assertFalse($this->driver->has('a'));
        $this->assertSame(1, $this->driver->getStats()['clears']);
    }

    #[Test]
    public function manyReturnsValuesWithDefaultsForMissingKeys(): void
    {
        $this->driver->put('m1', 'v1', 60);
        $result = $this->driver->many(['m1', 'm2']);
        $this->assertSame(['m1' => 'v1', 'm2' => null], $result);
    }

    #[Test]
    public function putManyStoresAllEntries(): void
    {
        $this->assertTrue($this->driver->putMany(['p1' => 1, 'p2' => 2], 30));
        $this->assertTrue($this->driver->has('p1'));
        $this->assertTrue($this->driver->has('p2'));
    }

    #[Test]
    public function forgetManyReturnsTrueOnlyWhenAllKeysRemoved(): void
    {
        $this->driver->put('d1', 1, 60);

        // d2 不存在，forget 回傳 false，因此整批結果為 false
        $this->assertFalse($this->driver->forgetMany(['d1', 'd2']));
        $this->assertFalse($this->driver->has('d1'));
    }

    #[Test]
    public function forgetPatternSupportsWildcards(): void
    {
        $this->driver->putMany([
            'post:1' => 1,
            'post:2' => 2,
            'user:1' => 3,
        ], 60);

        $deleted = $this->driver->forgetPattern('post:*');
        $this->assertSame(2, $deleted);
        $this->assertFalse($this->driver->has('post:1'));
        $this->assertFalse($this->driver->has('post:2'));
        $this->assertTrue($this->driver->has('user:1'));

        // 問號匹配單一字元
        $this->driver->put('axc', 9, 60);
        $this->assertSame(0, $this->driver->forgetPattern('zzz*'));
    }

    #[Test]
    public function incrementAndDecrementHandleNumericAndNonNumericValues(): void
    {
        $this->assertSame(5, $this->driver->increment('counter', 5));
        $this->assertSame(8, $this->driver->increment('counter', 3));
        $this->assertSame(6, $this->driver->decrement('counter', 2));

        // 非數值：遞增以 value 覆寫，遞減以 -value 起始
        $this->driver->put('text', 'abc', 60);
        $this->assertSame(4, $this->driver->increment('text', 4));
        $this->assertSame(-7, $this->driver->decrement('fresh-key', 7));
    }

    #[Test]
    public function rememberExecutesCallbackOnce(): void
    {
        $calls = 0;
        $callback = static function () use (&$calls): string {
            $calls++;

            return 'computed';
        };
        $this->assertSame('computed', $this->driver->remember('memo', $callback, 60));
        $this->assertSame('computed', $this->driver->remember('memo', $callback, 60));
        $this->assertSame(1, $calls);
    }

    #[Test]
    public function rememberDoesNotStoreNullResult(): void
    {
        $result = $this->driver->remember('null-memo', static fn(): ?int => null, 60);
        $this->assertNull($result);
        $this->assertFalse($this->driver->has('null-memo'));
    }

    #[Test]
    public function rememberForeverStoresPermanentEntry(): void
    {
        $this->assertSame('kept', $this->driver->rememberForever(
            'forever',
            static fn(): string => 'kept',
        ));

        $reflection = new ReflectionClass($this->driver);
        $cache = $reflection->getProperty('cache');
        /** @var array<string, array{value: mixed, expires_at: int}> $entries */
        $entries = $cache->getValue($this->driver);
        $this->assertSame(0, $entries['forever']['expires_at']);
    }

    #[Test]
    public function isAvailableAlwaysTrue(): void
    {
        $this->assertTrue($this->driver->isAvailable());
    }

    #[Test]
    public function cleanupRemovesExpiredEntriesOnly(): void
    {
        $this->driver->put('keep', 1, 3600);
        $this->driver->put('permanent-entry', 2, 0);

        // 直接注入過期項目
        $reflection = new ReflectionClass($this->driver);
        $cacheProperty = $reflection->getProperty('cache');
        /** @var array<string, array{value: mixed, expires_at: int}> $entries */
        $entries = [
            'e1'              => ['value' => 1, 'expires_at' => time() - 10],
            'e2'              => ['value' => 2, 'expires_at' => time() - 20],
            'keep'            => ['value' => 1, 'expires_at' => time() + 3600],
            'permanent-entry' => ['value' => 2, 'expires_at' => 0],
        ];
        $cacheProperty->setValue($this->driver, $entries);

        $cleaned = $this->driver->cleanup();
        $this->assertSame(2, $cleaned);
        $this->assertTrue($this->driver->has('keep'));
        $this->assertTrue($this->driver->has('permanent-entry'));
        $this->assertFalse($this->driver->has('e1'));
    }

    #[Test]
    public function getConnectionReturnsInternalCacheArray(): void
    {
        $this->driver->put('conn', 'v', 60);
        $connection = $this->driver->getConnection();
        $this->assertIsArray($connection);
        $this->assertArrayHasKey('conn', $connection);
    }

    #[Test]
    public function getStatsReportsCountersAndItems(): void
    {
        $this->driver->put('s1', 'v', 60);
        $this->driver->get('s1');
        $this->driver->get('missing');

        $stats = $this->driver->getStats();
        $this->assertSame(1, $stats['hits']);
        $this->assertSame(1, $stats['misses']);
        $this->assertSame(50.0, $stats['hit_rate']);
        $this->assertSame(1, $stats['total_items']);
        $this->assertSame(1000, $stats['max_items']);
        $this->assertGreaterThan(0, $stats['memory_usage']);
        $this->assertSame(0, $stats['expired_items']);
    }

    #[Test]
    public function resetStatsRestoresInitialCounters(): void
    {
        $this->driver->put('r', 'v', 60);
        $this->driver->get('r');
        $this->driver->resetStats();

        $stats = $this->driver->getStats();
        foreach (['hits', 'misses', 'sets', 'deletes', 'clears'] as $key) {
            $this->assertSame(0, $stats[$key]);
        }
    }

    #[Test]
    public function evictionOccursWhenMaxItemsReached(): void
    {
        $driver = new MemoryCacheDriver(3);
        $driver->setMaxItems(2);

        $driver->put('first', 1, 60);
        $driver->put('second', 2, 60);
        $driver->put('third', 3, 60); // 觸發淘汰 first

        $this->assertFalse($driver->has('first'));
        $this->assertTrue($driver->has('second'));
        $this->assertTrue($driver->has('third'));

        // 降低上限時主動淘汰多餘項目
        $driver->setMaxItems(1);
        $this->assertSame(1, $driver->getMaxItems());
        $this->assertFalse($driver->has('second'));
        $this->assertTrue($driver->has('third'));
    }

    #[Test]
    public function taggedOperationsManageIndexesCorrectly(): void
    {
        // 以標籤存放並取得標籤下的鍵
        $tagged = $this->driver->tags(['posts']);
        $this->assertInstanceOf(MemoryCacheDriver::class, $tagged);
        $tagged->put('t1', 'v', 60);
        $this->assertSame(['posts'], $tagged->getTags());
        $this->assertSame(['t1'], $tagged->getTaggedKeys());
        // 原實例不受影響
        $this->assertSame([], $this->driver->getTags());

        // 在原實例上也建立 posts 標籤資料，供後續交集與統計驗證使用
        $this->driver->putWithTags('t1', 'v', ['posts']);

        // putWithTags 使用後恢復原標籤狀態
        $this->assertTrue($this->driver->putWithTags('w1', 'v', ['news']));
        $this->assertTrue($this->driver->hasTag('w1', 'news'));
        $this->assertSame([], $this->driver->getTags());

        // addTags / addTagsToKey
        $this->driver->addTags('extra');
        $this->assertSame(['extra'], $this->driver->getTags());
        $this->driver->addTagsToKey('w1', 'hot');
        $this->assertSame(['news', 'hot'], $this->driver->getTagsByKey('w1'));

        // removeTagsFromKey
        $this->assertTrue($this->driver->removeTagsFromKey('w1', 'hot'));
        $this->assertSame(['news'], $this->driver->getTagsByKey('w1'));
        // 移除不存在的標籤回傳 false
        $this->assertFalse($this->driver->removeTagsFromKey('w1', 'ghost'));

        // getKeysByTag / tagExists
        $this->assertSame(['w1'], $this->driver->getKeysByTag('news'));
        $this->assertTrue($this->driver->tagExists('news'));
        $this->assertFalse($this->driver->tagExists('ghost'));

        // getKeysByTags 取交集（news 僅含 w1，hot 含 w1 與 t1）
        $this->driver->addTagsToKey('w1', 'hot');
        $this->driver->addTagsToKey('t1', 'hot');
        $this->assertSame(['w1'], $this->driver->getKeysByTags(['news', 'hot']));
        // 單一標籤回傳該標籤全部有效鍵
        $hotKeys = $this->driver->getKeysByTags(['hot']);
        sort($hotKeys);
        $this->assertSame(['t1', 'w1'], $hotKeys);
        $this->assertSame([], $this->driver->getKeysByTags([]));

        // getAllTags / getTagStatistics
        $allTags = $this->driver->getAllTags();
        sort($allTags);
        $this->assertSame(['hot', 'news', 'posts'], $allTags);
        $statistics = $this->driver->getTagStatistics();
        $this->assertSame(3, $statistics['total_tags']);
        $tagsInfo = $statistics['tags'];
        $this->assertIsArray($tagsInfo);
        $postsInfo = $tagsInfo['posts'] ?? null;
        $this->assertIsArray($postsInfo);
        $this->assertSame(1, $postsInfo['key_count']);

        // flushByTags 清除該標籤下所有快取
        $flushed = $this->driver->flushByTags('hot');
        $this->assertSame(2, $flushed);
        $this->assertFalse($this->driver->has('w1'));
        $this->assertFalse($this->driver->has('t1'));
        $this->assertFalse($this->driver->tagExists('hot'));

        // flushByTags 支援字串與不存在的標籤
        $this->assertSame(0, $this->driver->flushByTags(['ghost-tag']));
    }

    #[Test]
    public function cleanupUnusedTagsRemovesTagsWithoutValidKeys(): void
    {
        $this->driver->putWithTags('k1', 'v', ['live']);
        $this->driver->putWithTags('k2', 'v', ['stale']);

        // 讓 k2 過期但未被存取，標籤索引仍保留該鍵
        $reflection = new ReflectionClass($this->driver);
        $cacheProperty = $reflection->getProperty('cache');
        /** @var array<string, array{value: mixed, expires_at: int}> $entries */
        $entries = $cacheProperty->getValue($this->driver);
        $entries['k2']['expires_at'] = time() - 10;
        $cacheProperty->setValue($this->driver, $entries);

        $cleaned = $this->driver->cleanupUnusedTags();
        $this->assertSame(1, $cleaned);
        $this->assertTrue($this->driver->tagExists('live'));
        $this->assertFalse($this->driver->tagExists('stale'));
    }
}
