<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Cache\Repositories;

use App\Shared\Cache\Repositories\RedisTagRepository;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Redis;
use RedisException;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * RedisTagRepository 單元測試.
 *
 * 以 Mockery 模擬 Redis 連線，驗證標籤索引的雜湊操作、
 * 過期清理與交易回退邏輯。
 */
final class RedisTagRepositoryTest extends UnitTestCase
{
    private MockInterface&Redis $redis;

    private RedisTagRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(RedisException::class)) {
            /**
             * 提供 phpredis 例外類別的測試替身.
             */
            eval('class RedisException extends \\Exception {}');
        }
        /** @var MockInterface&Redis $redis */
        $redis = Mockery::mock(Redis::class);
        $this->redis = $redis;
        $this->repository = new RedisTagRepository($this->redis);
    }

    #[Test]
    public function setTagsWritesHashesAndIndexesInTransaction(): void
    {
        $expiry = time() + 600;
        $this->redis->shouldReceive('multi')->once()->andReturnSelf();
        // 先清除舊關聯（目前無任何標籤）
        $this->redis->shouldReceive('hGetAll')
            ->with('cache_tags:key_tags:post:1')
            ->once()
            ->andReturn([]);
        $this->redis->shouldReceive('hMSet')
            ->with('cache_tags:key_tags:post:1', Mockery::on(
                static fn(array $tagData): bool => count($tagData) === 2
                    && isset($tagData['news'], $tagData['hot'])
                    && is_int($tagData['news'])
                    && is_int($tagData['hot']),
            ))
            ->once()
            ->andReturn(true);
        $this->redis->shouldReceive('expire')->with('cache_tags:key_tags:post:1', 600)->once()->andReturn(true);
        $this->redis->shouldReceive('hSet')->twice()->andReturn(1);
        $this->redis->shouldReceive('expire')->twice()->andReturn(true);
        $this->redis->shouldReceive('exec')->once()->andReturn([true]);

        $this->assertTrue($this->repository->setTags('post:1', [' news ', 'hot', '', 'news'], 600));
    }

    #[Test]
    public function setTagsAcceptsEmptyTagListWithoutTouchingRedis(): void
    {
        $this->assertTrue($this->repository->setTags('key', []));
    }

    #[Test]
    public function setTagsDiscardsTransactionOnFailure(): void
    {
        $this->redis->shouldReceive('multi')->once()->andReturnSelf();
        $this->redis->shouldReceive('hGetAll')->andReturn([]);
        $this->redis->shouldReceive('hMSet')->andThrow(new RuntimeException('redis down'));
        $this->redis->shouldReceive('discard')->once()->andReturnTrue();

        $this->assertFalse($this->repository->setTags('key', ['tag'], 60));
    }

    #[Test]
    public function setTagsReplacesPreviousAssociations(): void
    {
        $this->redis->shouldReceive('multi')->twice()->andReturnSelf();
        // 舊標籤 old 存在，應先被清除
        $this->redis->shouldReceive('hGetAll')
            ->with('cache_tags:key_tags:post:1')
            ->once()
            ->andReturn(['old' => time() + 100]);
        $this->redis->shouldReceive('del')->with('cache_tags:key_tags:post:1')->once()->andReturn(1);
        $this->redis->shouldReceive('hDel')
            ->with('cache_tags:tag:old', 'post:1')
            ->once()
            ->andReturn(1);
        $this->redis->shouldReceive('hMSet')->andReturn(true)->byDefault();
        $this->redis->shouldReceive('expire')->andReturn(true)->byDefault();
        $this->redis->shouldReceive('hSet')->andReturn(1)->byDefault();
        $this->redis->shouldReceive('exec')->twice()->andReturn([true]);
        // 第二個交易內 hGetAll 不再被呼叫，直接寫入新標籤

        $this->assertTrue($this->repository->setTags('post:1', ['new-tag'], 300));
    }

    #[Test]
    public function getTagsFiltersExpiredEntriesAndCleansUp(): void
    {
        $validExpiry = (string) (time() + 500);
        $expiredExpiry = (string) (time() - 500);
        $this->redis->shouldReceive('hGetAll')
            ->with('cache_tags:key_tags:k1')
            ->once()
            ->andReturn([
                'fresh' => $validExpiry,
                'stale' => $expiredExpiry,
            ]);
        // 清理過期項目：開啟第二個交易刪除 stale
        $this->redis->shouldReceive('multi')->once()->andReturnSelf();
        $this->redis->shouldReceive('hGetAll')->with('cache_tags:key_tags:k1')->once()->andReturn([
            'fresh' => $validExpiry,
            'stale' => $expiredExpiry,
        ]);
        $this->redis->shouldReceive('hDel')->twice()->andReturn(1);
        $this->redis->shouldReceive('exec')->once()->andReturn([true, true]);

        $tags = $this->repository->getTags('k1');
        $this->assertSame(['fresh'], $tags);
    }

    #[Test]
    public function getTagsReturnsEmptyArrayWhenNoData(): void
    {
        $this->redis->shouldReceive('hGetAll')
            ->with('cache_tags:key_tags:none')
            ->once()
            ->andReturn([]);

        $this->assertSame([], $this->repository->getTags('none'));
    }

    #[Test]
    public function addTagsUsesMaxExistingExpiryAndDefaultsToHour(): void
    {
        // 無既有標籤：使用 time()+3600
        $this->redis->shouldReceive('hGetAll')
            ->with('cache_tags:key_tags:new-key')
            ->once()
            ->andReturn([]);
        $this->redis->shouldReceive('multi')->once()->andReturnSelf();
        $this->redis->shouldReceive('hMSet')
            ->with('cache_tags:key_tags:new-key', Mockery::on(
                static fn(array $tagData): bool => isset($tagData['t1']) && is_int($tagData['t1']),
            ))
            ->once()
            ->andReturn(true);
        $this->redis->shouldReceive('hSet')
            ->with('cache_tags:tag:t1', 'new-key', Mockery::type('int'))
            ->once()
            ->andReturn(1);
        $this->redis->shouldReceive('exec')->once()->andReturn([true]);

        $this->assertTrue($this->repository->addTags('new-key', ['t1']));

        // 有既有標籤時取最大過期時間
        $future = (string) (time() + 9999);
        $this->redis->shouldReceive('hGetAll')
            ->with('cache_tags:key_tags:existing-key')
            ->once()
            ->andReturn(['old' => $future]);
        $this->redis->shouldReceive('multi')->once()->andReturnSelf();
        $this->redis->shouldReceive('hMSet')->once()->andReturn(true);
        $this->redis->shouldReceive('hSet')->once()->andReturn(1);
        $this->redis->shouldReceive('exec')->once()->andReturn([true]);

        $this->assertTrue($this->repository->addTags('existing-key', ['t2']));
    }

    #[Test]
    public function addTagsDiscardsOnFailure(): void
    {
        $this->redis->shouldReceive('hGetAll')->andReturn([]);
        $this->redis->shouldReceive('multi')->once()->andReturnSelf();
        $this->redis->shouldReceive('hMSet')->andThrow(new RuntimeException('fail'));
        $this->redis->shouldReceive('discard')->once();

        $this->assertFalse($this->repository->addTags('key', ['t']));
    }

    #[Test]
    public function removeTagsDeletesFromBothSides(): void
    {
        $this->redis->shouldReceive('multi')->once()->andReturnSelf();
        $this->redis->shouldReceive('hDel')
            ->with('cache_tags:key_tags:k', 'a', 'b')
            ->once()
            ->andReturn(2);
        $this->redis->shouldReceive('hDel')->twice()->andReturn(1);
        $this->redis->shouldReceive('exec')->once()->andReturn([true]);

        $this->assertTrue($this->repository->removeTags('k', ['a', 'b']));
        // 空陣列直接成功
        $this->assertTrue($this->repository->removeTags('k', []));
    }

    #[Test]
    public function hasTagChecksExpiry(): void
    {
        $this->redis->shouldReceive('hGet')
            ->with('cache_tags:key_tags:k', 'live')
            ->once()
            ->andReturn((string) (time() + 100));
        $this->assertTrue($this->repository->hasTag('k', 'live'));

        $this->redis->shouldReceive('hGet')
            ->with('cache_tags:key_tags:k', 'expired')
            ->once()
            ->andReturn((string) (time() - 100));
        $this->assertFalse($this->repository->hasTag('k', 'expired'));

        $this->redis->shouldReceive('hGet')
            ->with('cache_tags:key_tags:k', 'missing')
            ->once()
            ->andReturn(false);
        $this->assertFalse($this->repository->hasTag('k', 'missing'));
    }

    #[Test]
    public function getKeysByTagFiltersExpiredKeys(): void
    {
        $this->redis->shouldReceive('hGetAll')
            ->with('cache_tags:tag:t')
            ->once()
            ->andReturn([
                'good' => (string) (time() + 100),
                'bad'  => (string) (time() - 100),
            ]);
        $this->redis->shouldReceive('multi')->once()->andReturnSelf();
        $this->redis->shouldReceive('hGetAll')->with('cache_tags:tag:t')->once()->andReturn([
            'good' => (string) (time() + 100),
            'bad'  => (string) (time() - 100),
        ]);
        $this->redis->shouldReceive('hDel')->twice()->andReturn(1);
        $this->redis->shouldReceive('exec')->once()->andReturn([true]);

        $keys = $this->repository->getKeysByTag('t');
        $this->assertSame(['good'], $keys);
    }

    #[Test]
    public function deleteByTagsReturnsUniqueDeletedKeys(): void
    {
        $this->redis->shouldReceive('hGetAll')
            ->with('cache_tags:tag:a')
            ->once()
            ->andReturn([
                'k1'     => (string) (time() + 100),
                'shared' => (string) (time() + 100),
            ]);
        $this->redis->shouldReceive('hGetAll')
            ->with('cache_tags:tag:b')
            ->once()
            ->andReturn([
                'shared' => (string) (time() + 100),
            ]);

        // deleteKeyInternal：讀取每個鍵的標籤並清除
        $this->redis->shouldReceive('multi')->times(4)->andReturnSelf();
        $this->redis->shouldReceive('hGetAll')->with('cache_tags:key_tags:k1')->times(2)->andReturn([
            'a' => (string) (time() + 100),
        ]);
        $this->redis->shouldReceive('hGetAll')->with('cache_tags:key_tags:shared')->times(2)->andReturn([
            'a' => (string) (time() + 100),
            'b' => (string) (time() + 100),
        ]);
        $this->redis->shouldReceive('del')->times(2)->andReturn(1);
        $this->redis->shouldReceive('hDel')->andReturn(1)->byDefault();
        $this->redis->shouldReceive('exec')->times(4)->andReturn([true]);

        $deleted = $this->repository->deleteByTags(['a', 'b']);
        sort($deleted);
        $this->assertSame(['k1', 'shared'], $deleted);
    }

    #[Test]
    public function getAllTagsOnlyKeepsTagsWithValidKeys(): void
    {
        $this->redis->shouldReceive('keys')
            ->with('cache_tags:tag:*')
            ->once()
            ->andReturn(['cache_tags:tag:live', 'cache_tags:tag:empty']);
        $this->redis->shouldReceive('hGetAll')
            ->with('cache_tags:tag:live')
            ->once()
            ->andReturn(['k' => (string) (time() + 100)]);
        $this->redis->shouldReceive('hGetAll')
            ->with('cache_tags:tag:empty')
            ->once()
            ->andReturn([]);

        $this->assertSame(['live'], $this->repository->getAllTags());
    }

    #[Test]
    public function cleanupUnusedTagsDeletesEmptyTags(): void
    {
        $this->redis->shouldReceive('keys')
            ->with('cache_tags:tag:*')
            ->once()
            ->andReturn(['cache_tags:tag:dying']);
        // cleanupExpiredKeysForTag 內部交易
        $this->redis->shouldReceive('multi')->twice()->andReturnSelf();
        $this->redis->shouldReceive('hGetAll')->with('cache_tags:tag:dying')->times(2)->andReturn([
            'dead' => (string) (time() - 50),
        ]);
        $this->redis->shouldReceive('exec')->twice()->andReturn([true]);
        $this->redis->shouldReceive('hDel')->andReturn(1)->byDefault();
        // 清理後無有效鍵 → 刪除標籤鍵
        $this->redis->shouldReceive('del')->with('cache_tags:tag:dying')->once()->andReturn(1);

        $this->assertSame(1, $this->repository->cleanupUnusedTags());
    }

    #[Test]
    public function getTagStatisticsCountsValidKeysPerTag(): void
    {
        $this->redis->shouldReceive('keys')
            ->with('cache_tags:tag:*')
            ->once()
            ->andReturn(['cache_tags:tag:popular']);
        $this->redis->shouldReceive('hGetAll')
            ->with('cache_tags:tag:popular')
            ->times(2)
            ->andReturn([
                'k1' => (string) (time() + 100),
                'k2' => (string) (time() + 100),
            ]);

        $statistics = $this->repository->getTagStatistics();
        $this->assertSame(['popular' => 2], $statistics);
    }

    #[Test]
    public function tagExistsRequiresIndexAndValidKeys(): void
    {
        $this->redis->shouldReceive('exists')->with('cache_tags:tag:there')->once()->andReturn(1);
        $this->redis->shouldReceive('exists')->with('cache_tags:tag:nope')->once()->andReturn(0);
        $this->redis->shouldReceive('hGetAll')->with('cache_tags:tag:there')->once()->andReturn([
            'k' => (string) (time() + 100),
        ]);

        $this->assertTrue($this->repository->tagExists('there'));
        $this->assertFalse($this->repository->tagExists('nope'));
    }

    #[Test]
    public function touchUpdatesExpiryForAllAssociatedTags(): void
    {
        $this->redis->shouldReceive('hGetAll')
            ->with('cache_tags:key_tags:k')
            ->once()
            ->andReturn(['x' => (string) (time() + 10), 'y' => (string) (time() - 10)]);
        $this->redis->shouldReceive('multi')->once()->andReturnSelf();
        $this->redis->shouldReceive('hMSet')
            ->with('cache_tags:key_tags:k', Mockery::on(
                static fn(array $tagData): bool => count($tagData) === 2
                    && isset($tagData['x'], $tagData['y'])
                    && is_int($tagData['x'])
                    && is_int($tagData['y']),
            ))
            ->once()
            ->andReturn(true);
        $this->redis->shouldReceive('expire')->with('cache_tags:key_tags:k', 120)->once()->andReturn(true);
        $this->redis->shouldReceive('hSet')->twice()->andReturn(1);
        $this->redis->shouldReceive('exec')->once()->andReturn([true]);

        $this->assertTrue($this->repository->touch('k', 120));

        // 無資料時回傳 false
        $this->redis->shouldReceive('hGetAll')
            ->with('cache_tags:key_tags:void')
            ->once()
            ->andReturn([]);
        $this->assertFalse($this->repository->touch('void', 60));
    }

    #[Test]
    public function touchDiscardsOnFailure(): void
    {
        $this->redis->shouldReceive('hGetAll')->andReturn(['x' => (string) (time() + 10)]);
        $this->redis->shouldReceive('multi')->once()->andReturnSelf();
        $this->redis->shouldReceive('hMSet')->andThrow(new RuntimeException('fail'));
        $this->redis->shouldReceive('discard')->once();

        $this->assertFalse($this->repository->touch('k', 60));
    }

    #[Test]
    public function flushRemovesAllTagRecords(): void
    {
        $keys = [
            'cache_tags:tag:x',
            'cache_tags:key_tags:y',
        ];
        $this->redis->shouldReceive('keys')->with('cache_tags:*')->once()->andReturn($keys);
        $this->redis->shouldReceive('del')->with(...$keys)->once()->andReturn(2);

        $this->assertTrue($this->repository->flush());
    }
}
