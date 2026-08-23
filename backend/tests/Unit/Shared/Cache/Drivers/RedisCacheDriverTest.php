<?php

declare(strict_types=1);

namespace {
    // 載入 phpredis 相容 stub；stub 檔已自 PHPStan 分析排除，
    // 避免空類別定義污染全專案的靜態分析結果
    require_once __DIR__ . '/../../../../Support/Stubs/redis.php';
}

namespace Tests\Unit\Shared\Cache\Drivers {
    use App\Shared\Cache\Drivers\RedisCacheDriver;
    use Mockery;
    use Mockery\MockInterface;
    use Redis;
    use RedisException;
    use ReflectionClass;
    use Tests\Support\UnitTestCase;

    /**
     * RedisCacheDriver 單元測試.
     *
     * 容器環境未安裝 phpredis 擴充功能，因此以 Mockery 模擬 Redis 連線，
     * 透過反射替換驅動內部的連線實例來驗證指令轉發與錯誤處理邏輯。
     */
    final class RedisCacheDriverTest extends UnitTestCase
    {
        private const PREFIX = 'test:cache:';

        private MockInterface&Redis $redis;

        private RedisCacheDriver $driver;

        protected function setUp(): void
        {
            parent::setUp();
            $reflection = new ReflectionClass(RedisCacheDriver::class);
            /** @var RedisCacheDriver $driver */
            $driver = $reflection->newInstanceWithoutConstructor();
            $this->redis = Mockery::mock(Redis::class);
            // 允許解構函式在測試結束後安全地檢查並關閉連線
            $this->redis->shouldReceive('isConnected')->andReturn(false)->byDefault();
            $this->redis->shouldReceive('close')->andReturnNull()->byDefault();
            // 統計資訊查詢預設回傳空陣列，避免干擾各測試的重點驗證
            $this->redis->shouldReceive('info')->andReturn([])->byDefault();
            $this->redis->shouldReceive('ping')->andReturn(false)->byDefault();
            $property = $reflection->getProperty('redis');
            $property->setValue($driver, $this->redis);
            $driver->setPrefix(self::PREFIX);
            $this->driver = $driver;
        }

        // ===== 基本讀寫操作 =====

        public function testGetReturnsValueOnHit(): void
        {
            $value = ['id' => 1, 'name' => '公告'];
            $this->redis->shouldReceive('get')
                ->with(self::PREFIX . 'key1')
                ->once()
                ->andReturn(serialize($value));

            $this->assertSame($value, $this->driver->get('key1'));
            $stats = $this->driver->getStats();
            $this->assertSame(1, $stats['hits']);
            $this->assertSame(0, $stats['misses']);
        }

        public function testGetReturnsDefaultOnMiss(): void
        {
            $this->redis->shouldReceive('get')
                ->with(self::PREFIX . 'missing')
                ->once()
                ->andReturn(false);

            $this->assertSame('fallback', $this->driver->get('missing', 'fallback'));
            $stats = $this->driver->getStats();
            $this->assertSame(0, $stats['hits']);
            $this->assertSame(1, $stats['misses']);
        }

        public function testGetReturnsDefaultOnRedisException(): void
        {
            $this->redis->shouldReceive('get')
                ->with(self::PREFIX . 'error')
                ->once()
                ->andThrow(new RedisException('connection lost'));

            $this->assertNull($this->driver->get('error'));
        }

        public function testPutWithTtlUsesSetex(): void
        {
            $serialized = serialize('value');
            $this->redis->shouldReceive('setex')
                ->with(self::PREFIX . 'key', 60, $serialized)
                ->once()
                ->andReturn(true);

            $this->assertTrue($this->driver->put('key', 'value', 60));
            $this->assertSame(1, $this->driver->getStats()['sets']);
        }

        public function testPutWithoutTtlUsesSet(): void
        {
            $serialized = serialize('forever');
            $this->redis->shouldReceive('set')
                ->with(self::PREFIX . 'forever', $serialized)
                ->once()
                ->andReturn(true);

            $this->assertTrue($this->driver->put('forever', 'forever', 0));
        }

        public function testPutReturnsFalseOnRedisException(): void
        {
            $this->redis->shouldReceive('setex')
                ->with(self::PREFIX . 'fail', 10, serialize('v'))
                ->once()
                ->andThrow(new RedisException('write error'));

            $this->assertFalse($this->driver->put('fail', 'v', 10));
        }

        public function testHasReturnsTrueWhenKeyExists(): void
        {
            $this->redis->shouldReceive('exists')
                ->with(self::PREFIX . 'exists')
                ->once()
                ->andReturn(1);
            $this->redis->shouldReceive('exists')
                ->with(self::PREFIX . 'not-exists')
                ->once()
                ->andReturn(0);

            $this->assertTrue($this->driver->has('exists'));
            $this->assertFalse($this->driver->has('not-exists'));
        }

        public function testHasReturnsFalseOnRedisException(): void
        {
            $this->redis->shouldReceive('exists')
                ->andThrow(new RedisException('read error'));

            $this->assertFalse($this->driver->has('any'));
        }

        public function testForgetDeletesKeyAndUpdatesTagsIndex(): void
        {
            $tagIndexKey = self::PREFIX . 'tag_index:user';
            $this->redis->shouldReceive('del')
                ->with(self::PREFIX . 'key1')
                ->once()
                ->andReturn(1);
            $this->redis->shouldReceive('keys')
                ->with(self::PREFIX . 'tag_index:*')
                ->once()
                ->andReturn([$tagIndexKey]);
            $this->redis->shouldReceive('sRem')
                ->with($tagIndexKey, self::PREFIX . 'key1')
                ->once()
                ->andReturn(1);

            $this->assertTrue($this->driver->forget('key1'));
            $this->assertSame(1, $this->driver->getStats()['deletes']);

            // 鍵不存在時回傳 false
            $this->redis->shouldReceive('del')
                ->with(self::PREFIX . 'none')
                ->once()
                ->andReturn(0);
            $this->assertFalse($this->driver->forget('none'));

            // 例外時回傳 false
            $this->redis->shouldReceive('del')
                ->andThrow(new RedisException('delete error'));
            $this->assertFalse($this->driver->forget('boom'));
        }

        public function testFlushDeletesOnlyPrefixedKeys(): void
        {
            $keys = [self::PREFIX . 'a', self::PREFIX . 'b'];
            $this->redis->shouldReceive('keys')
                ->with(self::PREFIX . '*')
                ->once()
                ->andReturn($keys);
            $this->redis->shouldReceive('del')
                ->with($keys)
                ->once()
                ->andReturn(2);

            $this->assertTrue($this->driver->flush());
            $this->assertSame(1, $this->driver->getStats()['clears']);

            // 無鍵時仍回傳 true
            $this->redis->shouldReceive('keys')
                ->with(self::PREFIX . '*')
                ->once()
                ->andReturn([]);
            $this->assertTrue($this->driver->flush());

            // 例外時回傳 false
            $this->redis->shouldReceive('keys')
                ->andThrow(new RedisException('flush error'));
            $this->assertFalse($this->driver->flush());
        }

        public function testManyUsesMgetAndCountsHitsAndMisses(): void
        {
            $keys = ['k1', 'k2'];
            $values = [serialize('v1'), false];
            $this->redis->shouldReceive('mget')
                ->with([self::PREFIX . 'k1', self::PREFIX . 'k2'])
                ->once()
                ->andReturn($values);

            $result = $this->driver->many($keys);
            $this->assertSame('v1', $result['k1']);
            $this->assertNull($result['k2']);
            $stats = $this->driver->getStats();
            $this->assertSame(1, $stats['hits']);
            $this->assertSame(1, $stats['misses']);

            // 例外時回退到單一操作
            $this->redis->shouldReceive('mget')
                ->andThrow(new RedisException('mget failed'));
            $this->redis->shouldReceive('get')
                ->with(self::PREFIX . 'k1')
                ->once()
                ->andReturn(false);
            $this->redis->shouldReceive('get')
                ->with(self::PREFIX . 'k2')
                ->once()
                ->andReturn(serialize('v2'));

            $fallback = $this->driver->many($keys);
            $this->assertNull($fallback['k1']);
            $this->assertSame('v2', $fallback['k2']);
        }

        public function testPutManyUsesPipeline(): void
        {
            /** @var MockInterface&Redis $pipe */
            $pipe = Mockery::mock(Redis::class);
            $pipe->shouldReceive('setex')->twice()->andReturnSelf();
            $pipe->shouldReceive('exec')->once()->andReturn([true, true]);
            $this->redis->shouldReceive('multi')->once()->andReturn($pipe);

            $this->assertTrue($this->driver->putMany(['a' => 1, 'b' => 2], 30));
            $this->assertSame(2, $this->driver->getStats()['sets']);
        }

        public function testPutManyFallsBackToSinglePutOnException(): void
        {
            $this->redis->shouldReceive('multi')
                ->once()
                ->andThrow(new RedisException('multi failed'));
            $this->redis->shouldReceive('setex')
                ->with(self::PREFIX . 'ok', 30, serialize(1))
                ->once()
                ->andReturn(true);
            $this->redis->shouldReceive('setex')
                ->with(self::PREFIX . 'bad', 30, serialize(2))
                ->once()
                ->andThrow(new RedisException('put failed'));

            $this->assertFalse($this->driver->putMany(['ok' => 1, 'bad' => 2], 30));
        }

        public function testForgetManyDeletesKeysInBatch(): void
        {
            $prefixed = [self::PREFIX . 'a', self::PREFIX . 'b'];
            $this->redis->shouldReceive('del')
                ->with($prefixed)
                ->once()
                ->andReturn(2);
            $this->redis->shouldReceive('keys')
                ->with(self::PREFIX . 'tag_index:*')
                ->twice()
                ->andReturn([]);

            $this->assertTrue($this->driver->forgetMany(['a', 'b']));
            $this->assertSame(2, $this->driver->getStats()['deletes']);

            // 部分刪除成功時回傳 false，但仍有標籤清理
            $this->redis->shouldReceive('del')
                ->with($prefixed)
                ->once()
                ->andReturn(1);
            $this->assertFalse($this->driver->forgetMany(['a', 'b']));

            // del 回傳非整數（交易情境）回傳 false
            $this->redis->shouldReceive('del')
                ->with($prefixed)
                ->once()
                ->andReturnNull();
            $this->assertFalse($this->driver->forgetMany(['a', 'b']));

            // 例外時回退到單一操作
            $this->redis->shouldReceive('del')
                ->andThrow(new RedisException('batch delete failed'));
            $this->redis->shouldReceive('del')
                ->with(self::PREFIX . 'a')
                ->once()
                ->andReturn(1);
            $this->redis->shouldReceive('del')
                ->with(self::PREFIX . 'b')
                ->once()
                ->andReturn(0);
            $this->assertFalse($this->driver->forgetMany(['a', 'b']));
        }

        public function testForgettingPatternDeletesMatchingKeys(): void
        {
            $matchingKeys = [self::PREFIX . 'post:1', self::PREFIX . 'post:2'];
            $this->redis->shouldReceive('keys')
                ->with(self::PREFIX . 'post:*')
                ->once()
                ->andReturn($matchingKeys);
            $this->redis->shouldReceive('del')
                ->with($matchingKeys)
                ->once()
                ->andReturn(2);

            $this->assertSame(2, $this->driver->forgetPattern('post:*'));

            // 無符合鍵
            $this->redis->shouldReceive('keys')
                ->with(self::PREFIX . 'user:*')
                ->once()
                ->andReturn([]);
            $this->assertSame(0, $this->driver->forgetPattern('user:*'));

            // 例外時回傳 0
            $this->redis->shouldReceive('keys')
                ->andThrow(new RedisException('pattern failed'));
            $this->assertSame(0, $this->driver->forgetPattern('post:*'));
        }

        // ===== 計數器操作 =====

        public function testIncrementAndDecrementDelegateToRedis(): void
        {
            $this->redis->shouldReceive('incrBy')
                ->with(self::PREFIX . 'counter', 5)
                ->once()
                ->andReturn(15);
            $this->redis->shouldReceive('decrBy')
                ->with(self::PREFIX . 'counter', 3)
                ->once()
                ->andReturn(12);

            $this->assertSame(15, $this->driver->increment('counter', 5));
            $this->assertSame(12, $this->driver->decrement('counter', 3));
        }

        public function testIncrementFallsBackToGetAndPutOnException(): void
        {
            $this->redis->shouldReceive('incrBy')
                ->andThrow(new RedisException('incr failed'));
            $this->redis->shouldReceive('get')
                ->with(self::PREFIX . 'counter')
                ->once()
                ->andReturn(serialize(7));
            $this->redis->shouldReceive('setex')
                ->with(self::PREFIX . 'counter', 3600, serialize(9))
                ->once()
                ->andReturn(true);

            $this->assertSame(9, $this->driver->increment('counter', 2));
        }

        public function testIncrementFallbackHandlesNonNumericValue(): void
        {
            $this->redis->shouldReceive('incrBy')
                ->andThrow(new RedisException('incr failed'));
            $this->redis->shouldReceive('get')
                ->with(self::PREFIX . 'str-key')
                ->once()
                ->andReturn(serialize('not-a-number'));
            $this->redis->shouldReceive('setex')
                ->with(self::PREFIX . 'str-key', 3600, serialize(3))
                ->once()
                ->andReturn(true);

            $this->assertSame(3, $this->driver->increment('str-key', 3));
        }

        public function testDecrementFallsBackToGetAndPutOnException(): void
        {
            $this->redis->shouldReceive('decrBy')
                ->andThrow(new RedisException('decr failed'));
            $this->redis->shouldReceive('get')
                ->with(self::PREFIX . 'counter')
                ->once()
                ->andReturn(serialize(10));
            $this->redis->shouldReceive('setex')
                ->with(self::PREFIX . 'counter', 3600, serialize(8))
                ->once()
                ->andReturn(true);

            $this->assertSame(8, $this->driver->decrement('counter', 2));

            // 非數值情況：從 -value 開始
            $this->redis->shouldReceive('get')
                ->with(self::PREFIX . 'other')
                ->once()
                ->andReturn(serialize(null));
            $this->redis->shouldReceive('setex')
                ->with(self::PREFIX . 'other', 3600, serialize(-4))
                ->once()
                ->andReturn(true);

            $this->assertSame(-4, $this->driver->decrement('other', 4));
        }

        // ===== remember 系列 =====

        public function testRememberReturnsCachedValueWithoutCallingCallback(): void
        {
            $called = false;
            $callback = static function () use (&$called): string {
                $called = true;

                return 'computed';
            };
            $this->redis->shouldReceive('get')
                ->with(self::PREFIX . 'memo')
                ->once()
                ->andReturn(serialize('cached'));

            $this->assertSame('cached', $this->driver->remember('memo', $callback, 120));
            $this->assertFalse($called);
        }

        public function testRememberComputesAndStoresOnMiss(): void
        {
            $this->redis->shouldReceive('get')
                ->with(self::PREFIX . 'memo')
                ->once()
                ->andReturn(false);
            $this->redis->shouldReceive('setex')
                ->with(self::PREFIX . 'memo', 120, serialize('computed'))
                ->once()
                ->andReturn(true);

            $this->assertSame('computed', $this->driver->remember(
                'memo',
                static fn(): string => 'computed',
                120,
            ));
        }

        public function testRememberDoesNotStoreNullResult(): void
        {
            $this->redis->shouldReceive('get')
                ->with(self::PREFIX . 'memo')
                ->once()
                ->andReturn(false);

            $this->assertNull($this->driver->remember('memo', static fn(): ?string => null, 120));
        }

        public function testRememberForeverStoresWithoutExpiry(): void
        {
            $this->redis->shouldReceive('get')
                ->with(self::PREFIX . 'permanent')
                ->once()
                ->andReturn(false);
            $this->redis->shouldReceive('set')
                ->with(self::PREFIX . 'permanent', serialize('value'))
                ->once()
                ->andReturn(true);

            $this->assertSame('value', $this->driver->rememberForever(
                'permanent',
                static fn(): string => 'value',
            ));
        }

        // ===== 統計與狀態資訊 =====

        public function testGetStatsMergesRedisInfo(): void
        {
            $this->redis->shouldReceive('info')
                ->with('memory')
                ->once()
                ->andReturn(['used_memory' => 1024, 'used_memory_peak' => 2048]);
            $this->redis->shouldReceive('info')
                ->with('clients')
                ->once()
                ->andReturn(['connected_clients' => 3]);
            $this->redis->shouldReceive('ping')
                ->once()
                ->andReturn('+PONG');

            $stats = $this->driver->getStats();
            $this->assertSame(1024, $stats['redis_memory_used']);
            $this->assertSame(2048, $stats['redis_memory_peak']);
            $this->assertSame(3, $stats['redis_connected_clients']);
            $this->assertSame(self::PREFIX, $stats['prefix']);
            $this->assertTrue($stats['connection_status']);
            $this->assertSame(0.0, $stats['hit_rate']);
        }

        public function testGetStatsIgnoresInfoFailure(): void
        {
            $this->redis->shouldReceive('info')
                ->andThrow(new RedisException('info failed'));
            $this->redis->shouldReceive('ping')
                ->once()
                ->andReturn(false);

            $stats = $this->driver->getStats();
            $this->assertArrayNotHasKey('redis_memory_used', $stats);
            $this->assertFalse($stats['connection_status']);
        }

        public function testGetConnectionReturnsUnderlyingClient(): void
        {
            $this->assertSame($this->redis, $this->driver->getConnection());
        }

        public function testIsAvailableReflectsPingResult(): void
        {
            $this->redis->shouldReceive('ping')->once()->andReturn('+PONG');
            $this->assertTrue($this->driver->isAvailable());

            $this->redis->shouldReceive('ping')->once()->andReturn(false);
            $this->assertFalse($this->driver->isAvailable());

            $this->redis->shouldReceive('ping')->once()->andThrow(new RedisException('down'));
            $this->assertFalse($this->driver->isAvailable());
        }

        public function testCleanupAlwaysReturnsZero(): void
        {
            $this->assertSame(0, $this->driver->cleanup());
        }

        public function testPrefixAccessors(): void
        {
            $this->assertSame(self::PREFIX, $this->driver->getPrefix());
            $this->driver->setPrefix('other:');
            $this->assertSame('other:', $this->driver->getPrefix());
        }

        public function testResetStatsClearsCounters(): void
        {
            $this->redis->shouldReceive('get')->andReturn(false);
            $this->driver->get('x');
            $this->assertSame(1, $this->driver->getStats()['misses']);

            $this->driver->resetStats();
            $stats = $this->driver->getStats();
            $this->assertSame(0, $stats['hits']);
            $this->assertSame(0, $stats['misses']);
            $this->assertSame(0, $stats['sets']);
            $this->assertSame(0, $stats['deletes']);
            $this->assertSame(0, $stats['clears']);
        }

        // ===== 標籤化快取 =====

        public function testTagsCreatesClonedInstanceWithTags(): void
        {
            $tagged = $this->driver->tags(['posts', 'users']);
            $this->assertNotSame($this->driver, $tagged);
            $this->assertSame(['posts', 'users'], $tagged->getTags());
            // 原實例不受影響
            $this->assertSame([], $this->driver->getTags());

            $single = $this->driver->tags('single');
            $this->assertSame(['single'], $single->getTags());
        }

        public function testAddTagsAccumulatesUniqueTags(): void
        {
            $tagged = $this->driver->addTags(['a', 'b']);
            $this->assertSame($this->driver, $tagged);
            $this->driver->addTags(['b', 'c']);
            // array_unique 會保留原始鍵，因此以 array_values 正規化後比較
            $this->assertSame(['a', 'b', 'c'], array_values($this->driver->getTags()));

            $this->driver->addTags('d');
            $this->assertSame(['a', 'b', 'c', 'd'], array_values($this->driver->getTags()));
        }

        public function testPutRegistersTaggedKeys(): void
        {
            $tagged = $this->driver->tags(['posts']);
            $tagIndexKey = self::PREFIX . 'tag_index:posts';
            $this->redis->shouldReceive('setex')
                ->with(self::PREFIX . 'p1', 60, serialize('v'))
                ->once()
                ->andReturn(true);
            $this->redis->shouldReceive('sAdd')
                ->with($tagIndexKey, self::PREFIX . 'p1')
                ->once()
                ->andReturn(1);

            $this->assertTrue($tagged->put('p1', 'v', 60));
        }

        public function testPutWithTagsSetsTagsThenPuts(): void
        {
            $tagIndexKey = self::PREFIX . 'tag_index:news';
            $this->redis->shouldReceive('setex')
                ->with(self::PREFIX . 'n1', 3600, serialize('v'))
                ->once()
                ->andReturn(true);
            $this->redis->shouldReceive('sAdd')
                ->with($tagIndexKey, self::PREFIX . 'n1')
                ->once()
                ->andReturn(1);

            $this->assertTrue($this->driver->putWithTags('n1', 'v', ['news']));
            $this->assertSame(['news'], $this->driver->getTags());
        }

        public function testPutManyRegistersTaggedKeys(): void
        {
            $tagged = $this->driver->tags(['bulk']);
            $this->assertInstanceOf(RedisCacheDriver::class, $tagged);
            $tagIndexKey = self::PREFIX . 'tag_index:bulk';
            /** @var MockInterface&Redis $pipe */
            $pipe = Mockery::mock(Redis::class);
            $pipe->shouldReceive('setex')->times(2)->andReturnSelf();
            $pipe->shouldReceive('exec')->once()->andReturn([true, true]);
            $this->redis->shouldReceive('multi')->once()->andReturn($pipe);
            $this->redis->shouldReceive('sAdd')
                ->with($tagIndexKey, self::PREFIX . 'a')
                ->once()
                ->andReturn(1);
            $this->redis->shouldReceive('sAdd')
                ->with($tagIndexKey, self::PREFIX . 'b')
                ->once()
                ->andReturn(1);

            $this->assertTrue($tagged->putMany(['a' => 1, 'b' => 2], 30));
        }

        public function testFlushByTagsRemovesIndexedKeys(): void
        {
            $tagIndexKey = self::PREFIX . 'tag_index:old';
            $members = [self::PREFIX . 'k1', self::PREFIX . 'k2'];
            $this->redis->shouldReceive('sMembers')
                ->with($tagIndexKey)
                ->once()
                ->andReturn($members);
            $this->redis->shouldReceive('del')
                ->with($members)
                ->once()
                ->andReturn(2);
            $this->redis->shouldReceive('del')
                ->with($tagIndexKey)
                ->once()
                ->andReturn(1);

            $this->assertSame(2, $this->driver->flushByTags('old'));

            // 不存在的標籤
            $emptyTagIndexKey = self::PREFIX . 'tag_index:none';
            $this->redis->shouldReceive('sMembers')
                ->with($emptyTagIndexKey)
                ->once()
                ->andReturn([]);
            $this->assertSame(0, $this->driver->flushByTags(['none']));

            // 例外時回傳 0
            $this->redis->shouldReceive('sMembers')
                ->andThrow(new RedisException('smembers failed'));
            $this->assertSame(0, $this->driver->flushByTags('boom'));
        }

        public function testGetKeysByTagFiltersExistingKeys(): void
        {
            $tagIndexKey = self::PREFIX . 'tag_index:live';
            $members = [self::PREFIX . 'alive', self::PREFIX . 'gone', 'unprefixed'];
            $this->redis->shouldReceive('sMembers')
                ->with($tagIndexKey)
                ->once()
                ->andReturn($members);
            $this->redis->shouldReceive('exists')
                ->with(self::PREFIX . 'alive')
                ->once()
                ->andReturn(1);
            $this->redis->shouldReceive('exists')
                ->with(self::PREFIX . 'gone')
                ->once()
                ->andReturn(0);

            $this->assertSame(['alive'], $this->driver->getKeysByTag('live'));

            // 例外時回傳空陣列
            $this->redis->shouldReceive('sMembers')
                ->andThrow(new RedisException('failed'));
            $this->assertSame([], $this->driver->getKeysByTag('live'));
        }

        public function testGetKeysByTagsIntersectsTagSets(): void
        {
            $this->assertSame([], $this->driver->getKeysByTags([]));

            $tagA = self::PREFIX . 'tag_index:a';
            $tagB = self::PREFIX . 'tag_index:b';
            $this->redis->shouldReceive('sInterStore')
                ->with(Mockery::pattern('/^temp_intersection_/'), $tagA, $tagB)
                ->once()
                ->andReturn(2);
            $this->redis->shouldReceive('sMembers')
                ->with(Mockery::type('string'))
                ->once()
                ->andReturn([self::PREFIX . 'shared']);
            $this->redis->shouldReceive('del')
                ->with(Mockery::type('string'))
                ->once()
                ->andReturn(1);
            $this->redis->shouldReceive('exists')
                ->with(self::PREFIX . 'shared')
                ->once()
                ->andReturn(1);

            $this->assertSame(['shared'], $this->driver->getKeysByTags(['a', 'b']));
        }

        public function testTagExistsChecksIndexKeyPresence(): void
        {
            $tagIndexKey = self::PREFIX . 'tag_index:existing';
            $this->redis->shouldReceive('exists')
                ->with($tagIndexKey)
                ->once()
                ->andReturn(1);
            $this->assertTrue($this->driver->tagExists('existing'));

            $missingTagIndexKey = self::PREFIX . 'tag_index:missing';
            $this->redis->shouldReceive('exists')
                ->with($missingTagIndexKey)
                ->once()
                ->andReturn(0);
            $this->assertFalse($this->driver->tagExists('missing'));

            $this->redis->shouldReceive('exists')
                ->andThrow(new RedisException('failed'));
            $this->assertFalse($this->driver->tagExists('boom'));
        }

        public function testGetAllTagsExtractsTagNamesFromKeys(): void
        {
            $keys = [
                self::PREFIX . 'tag_index:alpha',
                self::PREFIX . 'tag_index:beta',
            ];
            $this->redis->shouldReceive('keys')
                ->with(self::PREFIX . 'tag_index:*')
                ->once()
                ->andReturn($keys);

            $this->assertSame(['alpha', 'beta'], $this->driver->getAllTags());

            $this->redis->shouldReceive('keys')
                ->andThrow(new RedisException('keys failed'));
            $this->assertSame([], $this->driver->getAllTags());
        }

        public function testGetTagStatisticsAggregatesPerTagInfo(): void
        {
            $tagIndexKey = self::PREFIX . 'tag_index:stat';
            $this->redis->shouldReceive('keys')
                ->with(self::PREFIX . 'tag_index:*')
                ->once()
                ->andReturn([$tagIndexKey]);
            $this->redis->shouldReceive('sMembers')
                ->with($tagIndexKey)
                ->once()
                ->andReturn([self::PREFIX . 'k1', self::PREFIX . 'k2']);
            $this->redis->shouldReceive('exists')
                ->with(self::PREFIX . 'k1')
                ->once()
                ->andReturn(1);
            $this->redis->shouldReceive('exists')
                ->with(self::PREFIX . 'k2')
                ->once()
                ->andReturn(1);

            $statistics = $this->driver->getTagStatistics();
            $this->assertSame(1, $statistics['total_tags']);
            $tags = $statistics['tags'];
            $this->assertIsArray($tags);
            $this->assertArrayHasKey('stat', $tags);
            $statInfo = $tags['stat'];
            $this->assertIsArray($statInfo);
            $this->assertSame(2, $statInfo['key_count']);
            // getKeysByTag 會移除前綴後回傳
            $sampleKeys = $statInfo['sample_keys'];
            $this->assertIsArray($sampleKeys);
            $this->assertSame(['k1', 'k2'], array_slice($sampleKeys, 0, 2));
        }

        public function testAddTagsToKeyAddsToIndexes(): void
        {
            $tagIndexKey = self::PREFIX . 'tag_index:extra';
            $this->redis->shouldReceive('sAdd')
                ->with($tagIndexKey, self::PREFIX . 'some-key')
                ->once()
                ->andReturn(1);

            $this->assertTrue($this->driver->addTagsToKey('some-key', 'extra'));
        }

        public function testGetTaggedKeysCollectsMembersAcrossCurrentTags(): void
        {
            $this->driver->addTags(['x', 'y']);
            $this->redis->shouldReceive('sMembers')
                ->with(self::PREFIX . 'tag_index:x')
                ->once()
                ->andReturn([self::PREFIX . 'k1', self::PREFIX . 'shared']);
            $this->redis->shouldReceive('sMembers')
                ->with(self::PREFIX . 'tag_index:y')
                ->once()
                ->andReturn([self::PREFIX . 'shared']);

            $this->assertSame(
                [self::PREFIX . 'k1', self::PREFIX . 'shared'],
                $this->driver->getTaggedKeys(),
            );
        }

        public function testGetTagsByKeyReadsKeyTagSet(): void
        {
            $this->redis->shouldReceive('sMembers')
                ->with(self::PREFIX . 'key:tags')
                ->once()
                ->andReturn(['t1', 't2']);
            $this->assertSame(['t1', 't2'], $this->driver->getTagsByKey('key'));

            // 例外時回傳空陣列
            $this->redis->shouldReceive('sMembers')
                ->andThrow(new RedisException('failed'));
            $this->assertSame([], $this->driver->getTagsByKey('key'));
        }

        public function testHasTagChecksKeyMembership(): void
        {
            $this->redis->shouldReceive('sMembers')
                ->with(self::PREFIX . 'key:tags')
                ->once()
                ->andReturn(['owner']);
            $this->assertTrue($this->driver->hasTag('key', 'owner'));

            $this->redis->shouldReceive('sMembers')
                ->with(self::PREFIX . 'key:tags')
                ->once()
                ->andReturn([]);
            $this->assertFalse($this->driver->hasTag('key', 'owner'));
        }

        public function testRemoveTagsFromKeyUpdatesBothSets(): void
        {
            $keyTagSet = self::PREFIX . 'key:tags';
            $tagIndexKey = self::PREFIX . 'tag_index:t1';
            $this->redis->shouldReceive('sRem')->with($keyTagSet, 't1')->once()->andReturn(1);
            $this->redis->shouldReceive('sRem')->with($tagIndexKey, self::PREFIX . 'key')->once()->andReturn(1);

            $this->assertTrue($this->driver->removeTagsFromKey('key', 't1'));

            // 例外時回傳 false
            $this->redis->shouldReceive('sRem')->andThrow(new RedisException('failed'));
            $this->assertFalse($this->driver->removeTagsFromKey('key', ['t1']));
        }

        public function testCleanupUnusedTagsRemovesEmptyTagSets(): void
        {
            $emptyTagKey = self::PREFIX . 'tag_index:empty';
            $nonEmptyTagKey = self::PREFIX . 'tag_index:kept';
            $this->redis->shouldReceive('keys')
                ->with(self::PREFIX . 'tag_index:*')
                ->once()
                ->andReturn([$emptyTagKey, $nonEmptyTagKey]);
            $this->redis->shouldReceive('sMembers')
                ->with($emptyTagKey)
                ->once()
                ->andReturn([]);
            $this->redis->shouldReceive('del')
                ->with($emptyTagKey)
                ->once()
                ->andReturn(1);
            $this->redis->shouldReceive('sMembers')
                ->with($nonEmptyTagKey)
                ->once()
                ->andReturn([self::PREFIX . 'k']);

            $this->assertSame(1, $this->driver->cleanupUnusedTags());

            $this->redis->shouldReceive('keys')
                ->andThrow(new RedisException('failed'));
            $this->assertSame(0, $this->driver->cleanupUnusedTags());
        }
    }
}
