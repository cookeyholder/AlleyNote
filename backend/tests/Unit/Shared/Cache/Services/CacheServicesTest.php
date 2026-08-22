<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Cache\Services;

use App\Shared\Cache\Contracts\CacheDriverInterface;
use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Contracts\TaggedCacheInterface;
use App\Shared\Cache\Contracts\TagRepositoryInterface;
use App\Shared\Cache\Services\DefaultCacheStrategy as ServiceCacheStrategy;
use App\Shared\Cache\Services\PrefixedCacheManager;
use App\Shared\Cache\Services\TaggedCacheManager;
use App\Shared\Cache\Strategies\DefaultCacheStrategy as StrategyCacheStrategy;
use App\Shared\Monitoring\Contracts\CacheMonitorInterface;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\Support\UnitTestCase;

/**
 * Shared Cache 服務單元測試 (TaggedCacheManager, PrefixedCacheManager, DefaultCacheStrategy).
 */
class CacheServicesTest extends UnitTestCase
{
    /**
     * 測試 TaggedCacheManager.
     */
    public function testTaggedCacheManager(): void
    {
        $baseManagerMock = Mockery::mock(CacheManagerInterface::class);
        $tagRepoMock = Mockery::mock(TagRepositoryInterface::class);
        $loggerMock = Mockery::mock(LoggerInterface::class);
        $monitorMock = Mockery::mock(CacheMonitorInterface::class);

        $loggerMock->shouldReceive('debug')->byDefault();
        $loggerMock->shouldReceive('info')->byDefault();
        $loggerMock->shouldReceive('warning')->byDefault();

        $manager = new TaggedCacheManager($baseManagerMock, $tagRepoMock, $loggerMock, $monitorMock);

        // 測試 put & get & has
        $baseManagerMock->shouldReceive('set')->with('post_1', ['title' => 'First Post'], 3600)->andReturn(true);
        $tagRepoMock->shouldReceive('getTags')->with('post_1')->andReturn(['posts', 'user_5']);
        $tagRepoMock->shouldReceive('setTags')->with('post_1', Mockery::type('array'), 3600)->andReturn(true);
        $monitorMock->shouldReceive('recordOperation')->byDefault();

        $this->assertTrue($manager->put('post_1', ['title' => 'First Post'], 3600));

        $baseManagerMock->shouldReceive('has')->with('post_1')->andReturn(true);
        $this->assertTrue($manager->has('post_1'));

        $baseManagerMock->shouldReceive('get')->with('post_1', null)->andReturn(['title' => 'First Post']);
        $this->assertEquals(['title' => 'First Post'], $manager->get('post_1'));

        // 測試 remember
        $baseManagerMock->shouldReceive('get')->with('post_2', null)->andReturn(null);
        $baseManagerMock->shouldReceive('set')->with('post_2', 'Computed Value', 3600)->andReturn(true);
        $tagRepoMock->shouldReceive('getTags')->with('post_2')->andReturn([]);
        $remembered = $manager->remember('post_2', fn() => 'Computed Value', 3600);
        $this->assertEquals('Computed Value', $remembered);

        // 測試 addTags & getTags
        $manager->addTags(['trending']);
        $this->assertContains('trending', $manager->getTags());

        // 測試 flushByTags
        $tagRepoMock->shouldReceive('getKeysByTag')->with('user_5')->andReturn(['post_1']);
        $baseManagerMock->shouldReceive('delete')->with('post_1')->andReturn(true);
        $tagRepoMock->shouldReceive('deleteByTags')->with(['user_5'])->once();
        $this->assertSame(1, $manager->flushByTags(['user_5']));

        // 測試 getTaggedKeys
        $tagRepoMock->shouldReceive('getKeysByTag')->with('trending')->andReturn(['post_2']);
        $this->assertEquals(['post_2'], $manager->getTaggedKeys());

        // 測試 putWithTags
        $baseManagerMock->shouldReceive('set')->with('post_3', 'News data', 1800)->andReturn(true);
        $tagRepoMock->shouldReceive('setTags')->with('post_3', ['news'], 1800)->once()->andReturn(true);
        $this->assertTrue($manager->putWithTags('post_3', 'News data', ['news'], 1800));

        // 測試 getKeysByTag & getTagsByKey
        $tagRepoMock->shouldReceive('getKeysByTag')->with('news')->andReturn(['post_3']);
        $this->assertEquals(['post_3'], $manager->getKeysByTag('news'));

        $tagRepoMock->shouldReceive('getTags')->with('post_3')->andReturn(['news']);
        $this->assertEquals(['news'], $manager->getTagsByKey('post_3'));

        // 測試 addTagsToKey & removeTagsFromKey
        $baseManagerMock->shouldReceive('has')->with('post_3')->andReturn(true);
        $tagRepoMock->shouldReceive('addTags')->with('post_3', ['breaking'])->once()->andReturn(true);
        $this->assertTrue($manager->addTagsToKey('post_3', ['breaking']));

        $tagRepoMock->shouldReceive('removeTags')->with('post_3', ['news'])->once()->andReturn(true);
        $this->assertTrue($manager->removeTagsFromKey('post_3', ['news']));

        // 測試 hasTag & getAllTags & cleanupUnusedTags & getTagStatistics & tagExists
        $tagRepoMock->shouldReceive('hasTag')->with('post_3', 'breaking')->andReturn(true);
        $this->assertTrue($manager->hasTag('post_3', 'breaking'));

        $tagRepoMock->shouldReceive('getAllTags')->andReturn(['breaking']);
        $this->assertEquals(['breaking'], $manager->getAllTags());

        $tagRepoMock->shouldReceive('cleanupUnusedTags')->andReturn(2);
        $this->assertEquals(2, $manager->cleanupUnusedTags());

        $tagRepoMock->shouldReceive('getTagStatistics')->andReturn(['total_tags' => 1]);
        $this->assertEquals(['total_tags' => 1], $manager->getTagStatistics());

        $tagRepoMock->shouldReceive('tagExists')->with('breaking')->andReturn(true);
        $this->assertTrue($manager->tagExists('breaking'));

        // 測試 putMany & getManyByTag
        $baseManagerMock->shouldReceive('set')->with('k1', 'v1', 3600)->andReturn(true);
        $baseManagerMock->shouldReceive('set')->with('k2', 'v2', 3600)->andReturn(true);
        $tagRepoMock->shouldReceive('setTags')->with('k1', ['multi'], 3600)->andReturn(true);
        $tagRepoMock->shouldReceive('setTags')->with('k2', ['multi'], 3600)->andReturn(true);
        $this->assertEquals(['k1' => true, 'k2' => true], $manager->putMany(['k1' => 'v1', 'k2' => 'v2'], ['multi'], 3600));

        $tagRepoMock->shouldReceive('getKeysByTag')->with('multi')->andReturn(['k1', 'k2']);
        $tagRepoMock->shouldReceive('getTags')->with('k1')->andReturn(['multi']);
        $tagRepoMock->shouldReceive('getTags')->with('k2')->andReturn(['multi']);
        $baseManagerMock->shouldReceive('get')->with('k1', null)->andReturn('v1');
        $baseManagerMock->shouldReceive('get')->with('k2', null)->andReturn('v2');
        $multiValues = $manager->getManyByTag('multi');
        $this->assertEquals(['k1' => 'v1', 'k2' => 'v2'], $multiValues);

        // 測試 tags() 返回 TaggedCacheManager
        $taggedScoped = $manager->tags(['temp']);
        $this->assertInstanceOf(TaggedCacheManager::class, $taggedScoped);

        // 測試 forget & flush
        $tagRepoMock->shouldReceive('getTags')->with('k1')->andReturn(['multi']);
        $baseManagerMock->shouldReceive('delete')->with('k1')->andReturn(true);
        $tagRepoMock->shouldReceive('deleteKey')->with('k1')->once();
        $this->assertTrue($manager->forget('k1'));

        $baseManagerMock->shouldReceive('clear')->andReturn(true);
        $tagRepoMock->shouldReceive('flush')->once();
        $this->assertTrue($manager->flush());
    }

    /**
     * 測試 PrefixedCacheManager.
     */
    public function testPrefixedCacheManager(): void
    {
        $baseManagerMock = Mockery::mock(CacheManagerInterface::class);
        $driverMock = Mockery::mock(CacheDriverInterface::class);

        $manager = new PrefixedCacheManager($baseManagerMock, 'prefix:');
        $this->assertEquals('prefix:', $manager->getPrefix());

        // 測試 get, set, has, delete
        $baseManagerMock->shouldReceive('get')->with('prefix:key1', null)->andReturn('val1');
        $this->assertEquals('val1', $manager->get('key1'));

        $baseManagerMock->shouldReceive('set')->with('prefix:key2', 'val2', 3600)->andReturn(true);
        $this->assertTrue($manager->set('key2', 'val2', 3600));

        $baseManagerMock->shouldReceive('has')->with('prefix:key1')->andReturn(true);
        $this->assertTrue($manager->has('key1'));

        $baseManagerMock->shouldReceive('delete')->with('prefix:key1')->andReturn(true);
        $this->assertTrue($manager->delete('key1'));

        // 測試 clear, remember, tags, prefix
        $baseManagerMock->shouldReceive('clear')->andReturn(true);
        $this->assertTrue($manager->clear());

        $baseManagerMock->shouldReceive('remember')->with('prefix:k3', Mockery::type('callable'), 1800)->andReturn('computed');
        $this->assertEquals('computed', $manager->remember('k3', fn() => 'computed', 1800));

        $nestedPrefixed = $manager->prefix('sub:');
        assert($nestedPrefixed instanceof PrefixedCacheManager);
        $this->assertEquals('prefix:sub:', $nestedPrefixed->getPrefix());

        $taggedMock = Mockery::mock(TaggedCacheInterface::class);
        $baseManagerMock->shouldReceive('tags')->with(['tag1'])->andReturn($taggedMock);
        $this->assertSame($taggedMock, $manager->tags(['tag1']));

        // 測試 driver, getDriver, getDrivers, getStats, getHealthStatus, warmup, cleanup
        $baseManagerMock->shouldReceive('driver')->with('redis')->andReturn($driverMock);
        $this->assertSame($driverMock, $manager->driver('redis'));

        $baseManagerMock->shouldReceive('getDriver')->with('file')->andReturn($driverMock);
        $this->assertSame($driverMock, $manager->getDriver('file'));

        $baseManagerMock->shouldReceive('getDrivers')->andReturn(['memory' => $driverMock]);
        $this->assertArrayHasKey('memory', $manager->getDrivers());

        $baseManagerMock->shouldReceive('getStats')->andReturn(['hits' => 10]);
        $this->assertEquals(['hits' => 10], $manager->getStats());

        $baseManagerMock->shouldReceive('getHealthStatus')->andReturn(['healthy' => true]);
        $this->assertEquals(['healthy' => true], $manager->getHealthStatus());

        $baseManagerMock->shouldReceive('warmup')->with(Mockery::type('array'))->andReturn(['prefix:k' => true]);
        $this->assertEquals(['prefix:k' => true], $manager->warmup(['k' => fn() => 'val']));

        $baseManagerMock->shouldReceive('cleanup')->andReturn(['cleaned' => 3]);
        $this->assertEquals(['cleaned' => 3], $manager->cleanup());
    }

    /**
     * 測試 DefaultCacheStrategy (含 Services 與 Strategies 兩個命名空間).
     */
    public function testDefaultCacheStrategy(): void
    {
        $strategies = [
            new ServiceCacheStrategy([
                'exclude_patterns' => ['session:*', 'temp:*', 'debug:*'],
            ]),
            new StrategyCacheStrategy([
                'exclude_patterns' => ['session:*', 'temp:*', 'debug:*'],
            ]),
        ];

        foreach ($strategies as $strategy) {
            $driverMock = Mockery::mock(CacheDriverInterface::class);
            $driverMock->shouldReceive('isAvailable')->andReturn(true)->byDefault();
            $availableDrivers = ['memory' => $driverMock, 'redis' => $driverMock, 'file' => $driverMock];

            // 測試 shouldCache 基本判斷
            $this->assertTrue($strategy->shouldCache('post:1', 'simple content', 3600));

            // 測試排除黑名單前綴
            $this->assertFalse($strategy->shouldCache('temp:session', 'data', 3600));
            $this->assertFalse($strategy->shouldCache('session:123', 'data', 3600));
            $this->assertFalse($strategy->shouldCache('debug:log', 'data', 3600));

            // 測試超出最大大小
            $strategy->setMaxValueSize(100);
            $this->assertFalse($strategy->shouldCache('key', str_repeat('A', 200), 3600));
            $strategy->setMaxValueSize(10485760);

            // 測試 TTL 限制
            $strategy->setTtlRange(10, 86400);
            $this->assertFalse($strategy->shouldCache('key', 'data', 5));
            $this->assertFalse($strategy->shouldCache('key', 'data', 100000));

            // 測試 selectDriver
            // 小資料 (< 1KB) 選擇 memory
            $selected = $strategy->selectDriver($availableDrivers, 'key', 'small string');
            $this->assertEquals($driverMock, $selected);

            // 測試驅動不可用時的降級
            $unavailableMemory = Mockery::mock(CacheDriverInterface::class);
            $unavailableMemory->shouldReceive('isAvailable')->andReturn(false);
            $fallbackDrivers = ['memory' => $unavailableMemory, 'redis' => $driverMock];
            $fallbackSelected = $strategy->selectDriver($fallbackDrivers, 'key', 'small string');
            $this->assertEquals($driverMock, $fallbackSelected);

            // 無任何可用驅動
            $this->assertNull($strategy->selectDriver([], 'key', 'small string'));

            // 測試 decideTtl
            $this->assertEquals(7200, $strategy->decideTtl('config:app', 'val', 300));
            $this->assertEquals(600, $strategy->decideTtl('session:123', 'val', 300));
            $this->assertEquals(0, $strategy->decideTtl('key', 'val', 0));

            // 指定 TTL 在範圍內 (小資料 <= 1KB 自動加倍)
            $this->assertEquals(2400, $strategy->decideTtl('key', 'val', 1200));

            // 測試 handleMiss
            $this->assertEquals('computed', $strategy->handleMiss('missed:key', fn() => 'computed'));

            // 測試 handleDriverFailure
            $driverMock->shouldReceive('get')->with('test_key', null)->andReturn('fallback_val');
            $recoveryVal = $strategy->handleDriverFailure($unavailableMemory, $fallbackDrivers, 'get', ['key' => 'test_key']);
            $this->assertEquals('fallback_val', $recoveryVal);

            // 測試排除模式增刪
            $strategy->addExcludePattern('custom:*');
            $this->assertContains('custom:*', $strategy->getExcludePatterns());
            $this->assertFalse($strategy->shouldCache('custom:key', 'data', 3600));

            $strategy->removeExcludePattern('custom:*');
            $this->assertNotContains('custom:*', $strategy->getExcludePatterns());

            // 測試統計
            $stats = $strategy->getStats();
            $this->assertIsArray($stats);
            $this->assertArrayHasKey('cache_decisions', $stats);
            $strategy->resetStats();
            $this->assertEquals(0, $strategy->getStats()['cache_decisions']);
        }
    }
}
