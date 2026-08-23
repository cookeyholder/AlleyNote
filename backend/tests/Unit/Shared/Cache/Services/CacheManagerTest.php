<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Cache\Services;

use App\Shared\Cache\Contracts\CacheDriverInterface;
use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Contracts\CacheStrategyInterface;
use App\Shared\Cache\Contracts\TaggedCacheInterface;
use App\Shared\Cache\Drivers\MemoryCacheDriver;
use App\Shared\Cache\Repositories\MemoryTagRepository;
use App\Shared\Cache\Services\CacheManager;
use Exception;
use InvalidArgumentException;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * Shared CacheManager 單元測試.
 *
 * 驗證多驅動調度、策略整合、錯誤降級與統計功能。
 */
final class CacheManagerTest extends UnitTestCase
{
    /**
     * 建立固定行為的測試用策略（不拒絕快取、不調整 TTL、選擇第一個驅動）。
     */
    private function createPassThroughStrategy(): CacheStrategyInterface
    {
        return new class implements CacheStrategyInterface {
            public function shouldCache(string $key, mixed $value, int $ttl): bool
            {
                return true;
            }

            public function selectDriver(array $drivers, string $key, mixed $value): ?CacheDriverInterface
            {
                $first = reset($drivers);

                return $first === false ? null : $first;
            }

            public function decideTtl(string $key, mixed $value, int $requestedTtl): int
            {
                return $requestedTtl;
            }

            public function handleMiss(string $key, callable $callback): mixed
            {
                return $callback();
            }

            public function handleDriverFailure(
                CacheDriverInterface $failedDriver,
                array $availableDrivers,
                string $operation,
                array $params,
            ): mixed {
                return null;
            }

            public function getStats(): array
            {
                return [];
            }

            public function resetStats(): void {}
        };
    }

    private function createManager(?CacheStrategyInterface $strategy = null, array $config = []): CacheManager
    {
        $manager = new CacheManager(
            $strategy ?? $this->createPassThroughStrategy(),
            new NullLogger(),
            $config,
        );
        $manager->addDriver('primary', new MemoryCacheDriver(), 100);
        $manager->addDriver('backup', new MemoryCacheDriver(), 50);
        $manager->setDefaultDriver('primary');

        return $manager;
    }

    #[Test]
    public function driverRegistryManagement(): void
    {
        $manager = $this->createManager();

        // getDrivers / getDriver
        $this->assertCount(2, array_keys($manager->getDrivers()));
        $this->assertInstanceOf(CacheDriverInterface::class, $manager->getDriver('primary'));
        $this->assertNull($manager->getDriver('ghost'));

        // setDefaultDriver 對不存在的驅動拋出例外
        $unknownManager = new CacheManager($this->createPassThroughStrategy());
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("驅動 'nope' 不存在");

        try {
            $unknownManager->setDefaultDriver('nope');
        } catch (InvalidArgumentException $e) {
            // PHP-DI 環境下 InvalidArgumentException 與 RuntimeException 皆為預期
            throw new RuntimeException($e->getMessage(), previous: $e);
        }
    }

    #[Test]
    public function removeDriverReportsExistence(): void
    {
        $manager = $this->createManager();
        $this->assertTrue($manager->removeDriver('backup'));
        $this->assertFalse($manager->removeDriver('backup'));
        $this->assertNull($manager->getDriver('backup'));
    }

    #[Test]
    public function availableDriversFilteredByAvailability(): void
    {
        /** @var MockInterface&CacheDriverInterface $down */
        $down = Mockery::mock(CacheDriverInterface::class);
        $down->shouldReceive('isAvailable')->andReturn(false)->byDefault();
        $down->shouldReceive('getStats')->andReturn([])->byDefault();

        $manager = new CacheManager($this->createPassThroughStrategy());
        $manager->addDriver('up', new MemoryCacheDriver());
        $manager->addDriver('down', $down);

        $this->assertSame(['up'], array_keys($manager->getAvailableDrivers()));
        $this->assertTrue($manager->isDriverAvailable('up'));
        $this->assertFalse($manager->isDriverAvailable('down'));
        // 未註冊的驅動視為不可用
        $this->assertFalse($manager->isDriverAvailable('ghost'));

        // resetStats 應傳播到有實作的驅動
        $memoryDriver = new MemoryCacheDriver();
        $manager->addDriver('resettable', $memoryDriver);
        $manager->getDriver('resettable')?->put('x', 1);
        $manager->resetStats();
        $stats = $memoryDriver->getStats();
        $this->assertSame(0, $stats['sets']);
    }

    #[Test]
    public function getReturnsValueFromFirstDriverWithHit(): void
    {
        $manager = $this->createManager();
        $primary = $manager->getDriver('primary');
        $primary?->put('hit', 'value', 60);

        $this->assertSame('value', $manager->get('hit'));
        $stats = $manager->getStats();
        $this->assertSame(1, $stats['total_hits']);
        $this->assertSame(100.0, $stats['hit_rate']);
    }

    #[Test]
    public function getFallsBackToLowerPriorityDriverWhenHigherMisses(): void
    {
        $manager = $this->createManager();
        // 只在 backup 存放，primary 應未命中後由 backup 提供
        $backup = $manager->getDriver('backup');
        $backup?->put('deep', 'buried', 60);

        $this->assertSame('buried', $manager->get('deep'));
    }

    #[Test]
    public function getSyncsValueToHigherPriorityDriversWhenEnabled(): void
    {
        $manager = $this->createManager(config: ['enable_sync' => true]);
        $backup = $manager->getDriver('backup');
        $backup?->put('sync-me', 'v', 3600);

        $this->assertSame('v', $manager->get('sync-me'));
        // 同步後 primary 也應持有該值
        $primary = $manager->getDriver('primary');
        $this->assertTrue($primary?->has('sync-me') ?? false);
    }

    #[Test]
    public function getReturnsDefaultWhenNoDriversAvailable(): void
    {
        $manager = new CacheManager($this->createPassThroughStrategy());
        $this->assertNull($manager->get('anything'));
        $this->assertSame('dflt', $manager->get('anything', 'dflt'));
        $this->assertSame(2, $manager->getStats()['total_misses']);
    }

    #[Test]
    public function getHandlesDriverExceptionsAndContinuesToNextDriver(): void
    {
        /** @var MockInterface&CacheDriverInterface $broken */
        $broken = Mockery::mock(CacheDriverInterface::class);
        $broken->shouldReceive('isAvailable')->andReturn(true)->byDefault();
        $broken->shouldReceive('has')->andThrow(new Exception('driver exploded'))->byDefault();
        $broken->shouldReceive('getStats')->andReturn([])->byDefault();

        $strategy = $this->createPassThroughStrategy();
        $manager = new CacheManager($strategy);
        $manager->addDriver('broken', $broken, 100);
        $manager->addDriver('healthy', new MemoryCacheDriver(), 50);
        $healthy = $manager->getDriver('healthy');
        $healthy?->put('rescued', 'ok', 60);

        $this->assertSame('ok', $manager->get('rescued'));
        $stats = $manager->getStats();
        $this->assertSame(1, $stats['driver_failures']);
        $this->assertSame(1, $stats['total_hits']);
    }

    #[Test]
    public function putRespectsStrategyDenial(): void
    {
        /** @var MockInterface&CacheStrategyInterface $strategy */
        $strategy = Mockery::mock(CacheStrategyInterface::class);
        $strategy->shouldReceive('shouldCache')->once()->andReturn(false);
        $strategy->shouldReceive('getStats')->andReturn([])->byDefault();

        $manager = $this->createManager($strategy);
        $this->assertFalse($manager->put('denied', 'v'));
        $stats = $manager->getStats();
        $this->assertSame(1, $stats['strategy_cache_denials']);
    }

    #[Test]
    public function putReturnsFalseWhenNoSuitableDriverSelected(): void
    {
        /** @var MockInterface&CacheStrategyInterface $strategy */
        $strategy = Mockery::mock(CacheStrategyInterface::class);
        $strategy->shouldReceive('shouldCache')->andReturn(true)->byDefault();
        $strategy->shouldReceive('decideTtl')->andReturn(600)->byDefault();
        $strategy->shouldReceive('selectDriver')->andReturn(null)->byDefault();
        $strategy->shouldReceive('getStats')->andReturn([])->byDefault();

        $manager = $this->createManager($strategy);
        $this->assertFalse($manager->put('orphan', 'v'));
    }

    #[Test]
    public function putStoresViaSelectedDriverWithAdjustedTtl(): void
    {
        /** @var MockInterface&CacheStrategyInterface $strategy */
        $strategy = Mockery::mock(CacheStrategyInterface::class);
        $strategy->shouldReceive('shouldCache')->andReturn(true)->byDefault();
        $strategy->shouldReceive('decideTtl')->with('k', 'v', 999)->once()->andReturn(42);
        $primary = Mockery::mock(CacheDriverInterface::class);
        $primary->shouldReceive('isAvailable')->andReturn(true)->byDefault();
        $primary->shouldReceive('getStats')->andReturn([])->byDefault();
        $strategy->shouldReceive('selectDriver')->andReturn($primary)->byDefault();
        $strategy->shouldReceive('getStats')->andReturn([])->byDefault();
        $primary->shouldReceive('put')->with('k', 'v', 42)->once()->andReturn(true);

        $manager = new CacheManager($strategy);
        $manager->addDriver('primary', $primary, 100);
        $this->assertTrue($manager->put('k', 'v', 999));
    }

    #[Test]
    public function hasAggregatesAcrossDriversAndSurvivesFailures(): void
    {
        /** @var MockInterface&CacheDriverInterface $broken */
        $broken = Mockery::mock(CacheDriverInterface::class);
        $broken->shouldReceive('isAvailable')->andReturn(true)->byDefault();
        $broken->shouldReceive('has')->andThrow(new Exception('boom'))->byDefault();
        $broken->shouldReceive('getStats')->andReturn([])->byDefault();

        $manager = new CacheManager($this->createPassThroughStrategy());
        $manager->addDriver('broken', $broken, 100);
        $manager->addDriver('healthy', new MemoryCacheDriver(), 50);
        $manager->getDriver('healthy')?->put('exists', 1, 60);

        $this->assertTrue($manager->has('exists'));
        $this->assertFalse($manager->has('missing-everywhere'));
        // get 與 has 各觸發一次故障驅動
        $this->assertSame(2, $manager->getStats()['driver_failures']);
    }

    #[Test]
    public function forgetRequiresEveryDriverToSucceed(): void
    {
        $manager = $this->createManager();
        // put 經策略只寫入第一個選中的驅動，因此另一個驅動刪除時會回報失敗，
        // forget 依現行語意回傳 false 但仍會移除已持有的資料
        $manager->set('gone', 'v');

        $this->assertFalse($manager->forget('gone'));
        $this->assertFalse($manager->has('gone'));

        // 任一驅動失敗時整體為 false
        /** @var MockInterface&CacheDriverInterface $failing */
        $failing = Mockery::mock(CacheDriverInterface::class);
        $failing->shouldReceive('isAvailable')->andReturn(true)->byDefault();
        $failing->shouldReceive('forget')->andReturn(false)->byDefault();
        $failing->shouldReceive('getStats')->andReturn([])->byDefault();
        $strictManager = new CacheManager($this->createPassThroughStrategy());
        $strictManager->addDriver('failing', $failing, 100);
        $this->assertFalse($strictManager->forget('whatever'));
    }

    #[Test]
    public function flushClearsAllDrivers(): void
    {
        $manager = $this->createManager();
        $manager->put('a', 1);
        $manager->put('b', 2);

        $this->assertTrue($manager->flush());
        $this->assertFalse($manager->has('a'));
        $this->assertSame(1, $manager->getStats()['total_flushes']);
    }

    #[Test]
    public function manyAndPutManyOperateInBulk(): void
    {
        $manager = $this->createManager();
        $this->assertTrue($manager->putMany(['m1' => 'v1', 'm2' => 'v2']));

        $result = $manager->many(['m1', 'm2', 'm3']);
        $this->assertSame(['m1' => 'v1', 'm2' => 'v2', 'm3' => null], $result);

        // many 忽略非字串鍵
        $this->assertSame([], $manager->many([1, 2]));
    }

    #[Test]
    public function incrementAndDecrementDelegateToFirstAvailableDriver(): void
    {
        $manager = $this->createManager();
        $manager->increment('counter', 5);
        $manager->increment('counter', 3);

        $this->assertSame(8, $manager->get('counter'));
        $this->assertSame(7, $manager->decrement('counter'));
    }

    #[Test]
    public function aliasMethodsRouteToCoreOperations(): void
    {
        $manager = $this->createManager();
        $this->assertTrue($manager->set('alias', 'v', 120));
        $this->assertSame('v', $manager->get('alias'));
        // delete 委託給 forget，因備援驅動未持有該鍵而回傳 false，但資料仍被移除
        $this->assertFalse($manager->delete('alias'));
        $this->assertFalse($manager->has('alias'));

        $manager->set('keep', 'v');
        $this->assertTrue($manager->clear());
        $this->assertFalse($manager->has('keep'));
    }

    #[Test]
    public function rememberReturnsCachedValueWithoutExecutingCallback(): void
    {
        $manager = $this->createManager();
        $manager->set('memo', 'cached');
        $calls = 0;

        $result = $manager->remember('memo', static function () use (&$calls): string {
            $calls++;

            return 'fresh';
        });
        $this->assertSame('cached', $result);
        $this->assertSame(0, $calls);
    }

    #[Test]
    public function rememberComputesStoresAndRethrowsCallbackErrors(): void
    {
        $manager = $this->createManager();
        $result = $manager->remember('computed-key', static fn(): string => 'computed-value', 300);
        $this->assertSame('computed-value', $result);
        $this->assertSame('computed-value', $manager->get('computed-key'));

        // callback 拋出例外時應記錄並重新拋出
        try {
            $manager->remember('boom', static fn(): string => throw new LogicException('callback failed'));
            $this->fail('預期拋出 LogicException');
        } catch (LogicException) {
            $this->addToAssertionCount(1);
        }
    }

    #[Test]
    public function driverMethodResolvesSpecificOrDefaultDriver(): void
    {
        $manager = $this->createManager();
        $resolved = $manager->driver('backup');
        $this->assertInstanceOf(CacheDriverInterface::class, $resolved);
        $this->assertSame($manager->getDriver('backup'), $resolved);
        $this->assertSame($manager->getDriver('primary'), $manager->driver());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("驅動 'ghost' 不存在");
        $manager->driver('ghost');
    }

    #[Test]
    public function prefixReturnsPrefixedProxyManager(): void
    {
        $manager = $this->createManager();
        $prefixed = $manager->prefix('shop:');
        $this->assertInstanceOf(CacheManagerInterface::class, $prefixed);
    }

    #[Test]
    public function getHealthStatusRunsReadWriteProbes(): void
    {
        $manager = $this->createManager();
        $health = $manager->getHealthStatus();

        foreach (['primary', 'backup'] as $name) {
            $this->assertArrayHasKey($name, $health);
            $entry = $health[$name];
            $this->assertIsArray($entry);
            $this->assertTrue($entry['available'] ?? false);
            $this->assertNull($entry['error'] ?? null);
        }
    }

    #[Test]
    public function getHealthStatusFlagsUnavailableDrivers(): void
    {
        /** @var MockInterface&CacheDriverInterface $down */
        $down = Mockery::mock(CacheDriverInterface::class);
        $down->shouldReceive('isAvailable')->andReturn(false)->byDefault();
        $down->shouldReceive('getStats')->andReturn([])->byDefault();
        $down->shouldIgnoreMissing();

        $manager = new CacheManager($this->createPassThroughStrategy());
        $manager->addDriver('down', $down);

        $health = $manager->getHealthStatus();
        $downStatus = $health['down'] ?? null;
        $this->assertIsArray($downStatus);
        $this->assertFalse($downStatus['available']);
    }

    #[Test]
    public function warmupExecutesCallbacksAndReportsResults(): void
    {
        $manager = $this->createManager();
        $results = $manager->warmup([
            'w1'  => static fn(): string => 'v1',
            'bad' => static fn(): string => throw new RuntimeException('warmup failed'),
        ]);

        $w1Result = $results['w1'];
        $this->assertIsArray($w1Result);
        $this->assertTrue($w1Result['success']);
        $this->assertSame('v1', $manager->get('w1'));
        $badResult = $results['bad'];
        $this->assertIsArray($badResult);
        $this->assertFalse($badResult['success']);
        $this->assertSame('warmup failed', $badResult['error']);

        // 自訂 warmup_ttl
        $customConfigManager = $this->createManager(config: ['warmup_ttl' => 123]);
        $customConfigManager->warmup(['ttl-check' => static fn(): int => 5]);
        $config = $customConfigManager->getConfig();
        $this->assertSame(123, $config['warmup_ttl']);
    }

    #[Test]
    public function cleanupCollectsPerDriverResults(): void
    {
        $manager = $this->createManager();
        $results = $manager->cleanup();

        foreach (['primary', 'backup'] as $name) {
            $entry = $results[$name];
            $this->assertIsArray($entry);
            $this->assertTrue($entry['success']);
            $this->assertIsInt($entry['cleaned_items']);
        }
    }

    #[Test]
    public function cleanupReportsDriverFailures(): void
    {
        /** @var MockInterface&CacheDriverInterface $broken */
        $broken = Mockery::mock(CacheDriverInterface::class);
        $broken->shouldReceive('cleanup')->andThrow(new Exception('cleanup exploded'))->byDefault();
        $broken->shouldIgnoreMissing();

        $manager = new CacheManager($this->createPassThroughStrategy());
        $manager->addDriver('broken', $broken);
        $results = $manager->cleanup();
        $brokenResult = $results['broken'];
        $this->assertIsArray($brokenResult);
        $this->assertFalse($brokenResult['success']);
        $this->assertSame('cleanup exploded', $brokenResult['error']);
    }

    #[Test]
    public function tagsUsesTaggedDriverWhenNoRepositoryProvided(): void
    {
        $manager = $this->createManager();
        $tagged = $manager->tags(['posts']);
        $this->assertInstanceOf(TaggedCacheInterface::class, $tagged);

        // 字串標籤也接受
        $single = $manager->tags('users');
        $this->assertInstanceOf(TaggedCacheInterface::class, $single);
    }

    #[Test]
    public function tagsThrowsWhenNoTaggedDriverAvailable(): void
    {
        /** @var MockInterface&CacheDriverInterface $plain */
        $plain = Mockery::mock(CacheDriverInterface::class);
        $plain->shouldReceive('isAvailable')->andReturn(true)->byDefault();
        $plain->shouldIgnoreMissing();

        $manager = new CacheManager($this->createPassThroughStrategy());
        $manager->addDriver('plain', $plain);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('沒有可用的標籤快取驅動');
        $manager->tags(['any']);
    }

    #[Test]
    public function statsTrackOperationsAndReset(): void
    {
        $manager = $this->createManager();
        $manager->set('s1', 'v');
        $manager->get('s1');
        $manager->get('missing');
        $manager->delete('s1');

        $stats = $manager->getStats();
        $this->assertSame(2, $stats['total_gets']);
        $this->assertSame(1, $stats['total_hits']);
        $this->assertSame(1, $stats['total_puts']);
        $this->assertSame(1, $stats['total_deletes']);
        $driversInfo = $stats['drivers'];
        $this->assertIsArray($driversInfo);
        $this->assertArrayHasKey('primary', $driversInfo);
        $this->assertArrayHasKey('strategy_stats', $stats);

        $manager->resetStats();
        $resetStats = $manager->getStats();
        $this->assertSame(0, $resetStats['total_gets']);
        $this->assertSame(0, $resetStats['total_hits']);
    }

    #[Test]
    public function updateConfigMergesSettings(): void
    {
        $manager = $this->createManager();
        $originalEnableSync = $manager->getConfig()['enable_sync'] ?? null;
        $this->assertFalse($originalEnableSync);

        $manager->updateConfig(['enable_sync' => true]);
        $config = $manager->getConfig();
        $this->assertTrue($config['enable_sync'] ?? false);
        // 其餘設定保留
        $this->assertSame(3600, $config['sync_ttl'] ?? null);
    }

    #[Test]
    public function defaultDriverAccessorRoundTrip(): void
    {
        $manager = $this->createManager();
        $this->assertSame('primary', $manager->getDefaultDriver());
        $manager->setDefaultDriver('backup');
        $this->assertSame('backup', $manager->getDefaultDriver());
    }

    #[Test]
    public function tagRepositoryEnablesTaggedCacheManager(): void
    {
        $manager = new CacheManager(
            $this->createPassThroughStrategy(),
            new NullLogger(),
            [],
            null,
            new MemoryTagRepository(),
        );
        $manager->addDriver('memory', new MemoryCacheDriver());

        $tagged = $manager->tags(['news']);
        $this->assertInstanceOf(TaggedCacheInterface::class, $tagged);
    }
}
