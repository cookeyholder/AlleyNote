<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Cache\Drivers;

use App\Shared\Cache\Contracts\CacheDriverInterface;
use App\Shared\Cache\Drivers\LayeredCacheDriver;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * LayeredCacheDriver 單元測試.
 *
 * 以 Mockery 模擬多層擬多層驅動，驗證層級查找、資料晉升與同步邏輯。
 */
final class LayeredCacheDriverTest extends UnitTestCase
{
    /**
     * 建立可用的驅動模擬物件。
     */
    private function mockDriver(bool $available = true): MockInterface&CacheDriverInterface
    {
        /** @var MockInterface&CacheDriverInterface $driver */
        $driver = Mockery::mock(CacheDriverInterface::class);
        $driver->shouldReceive('isAvailable')->andReturn($available)->byDefault();
        // getStats 彙整時預設回傳空統計
        $driver->shouldReceive('getStats')->andReturn([])->byDefault();

        return $driver;
    }

    #[Test]
    public function constructorRejectsEmptyLayers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('至少需要一個快取層級');
        new LayeredCacheDriver([]);
    }

    #[Test]
    public function getFindsValueInLowerLayerAndPromotesToUpperLayers(): void
    {
        $fast = $this->mockDriver();
        $slow = $this->mockDriver();

        $fast->shouldReceive('get')->with('key', null)->once()->andReturn(null);
        $slow->shouldReceive('get')->with('key', null)->once()->andReturn('value');
        // 晉升：寫回第一層（使用預設 TTL）
        $fast->shouldReceive('put')->with('key', 'value')->once()->andReturn(true);

        $driver = new LayeredCacheDriver([$fast, $slow]);
        $this->assertSame('value', $driver->get('key'));

        $stats = $driver->getStats();
        $this->assertSame(1, $stats['hits']);
        $this->assertSame(1, $stats['layer_promotions']);
        $this->assertSame(100.0, $stats['hit_rate']);
    }

    #[Test]
    public function getReturnsDefaultAndCountsMissWhenNothingFound(): void
    {
        $first = $this->mockDriver();
        $second = $this->mockDriver(false); // 不可用，應被跳過

        $first->shouldReceive('get')->with('key', 'fallback')->once()->andReturn('fallback');

        $driver = new LayeredCacheDriver([$first, $second]);
        $this->assertSame('fallback', $driver->get('key', 'fallback'));

        $stats = $driver->getStats();
        $this->assertSame(1, $stats['misses']);
        $this->assertSame(0, $stats['hits']);
    }

    #[Test]
    public function putWritesToAllAvailableLayers(): void
    {
        $first = $this->mockDriver();
        $second = $this->mockDriver(false);
        $third = $this->mockDriver();

        $first->shouldReceive('put')->with('k', 'v', 120)->once()->andReturn(true);
        $third->shouldReceive('put')->with('k', 'v', 120)->once()->andReturn(true);

        $driver = new LayeredCacheDriver([$first, $second, $third]);
        $this->assertTrue($driver->put('k', 'v', 120));
        $this->assertSame(1, $driver->getStats()['sets']);
    }

    #[Test]
    public function putReturnsFalseWhenAnyLayerFails(): void
    {
        $first = $this->mockDriver();
        $second = $this->mockDriver();

        $first->shouldReceive('put')->once()->andReturn(true);
        $second->shouldReceive('put')->once()->andReturn(false);

        $driver = new LayeredCacheDriver([$first, $second]);
        $this->assertFalse($driver->put('k', 'v'));
        $this->assertSame(0, $driver->getStats()['sets']);
    }

    #[Test]
    public function hasChecksAcrossLayersSkippingUnavailableOnes(): void
    {
        $first = $this->mockDriver();
        $second = $this->mockDriver(false);
        $third = $this->mockDriver();

        $first->shouldReceive('has')->with('k')->once()->andReturn(false);
        $third->shouldReceive('has')->with('k')->once()->andReturn(true);

        $driver = new LayeredCacheDriver([$first, $second, $third]);
        $this->assertTrue($driver->has('k'));

        $absentFirst = $this->mockDriver();
        $absentFirst->shouldReceive('has')->with('none')->once()->andReturn(false);
        $driverWithoutKey = new LayeredCacheDriver([$absentFirst]);
        $this->assertFalse($driverWithoutKey->has('none'));
    }

    #[Test]
    public function forgetRequiresAllAvailableLayersToSucceed(): void
    {
        $first = $this->mockDriver();
        $second = $this->mockDriver(false);
        $third = $this->mockDriver();

        $first->shouldReceive('forget')->with('k')->once()->andReturn(true);
        $third->shouldReceive('forget')->with('k')->once()->andReturn(true);

        $driver = new LayeredCacheDriver([$first, $second, $third]);
        $this->assertTrue($driver->forget('k'));
        $this->assertSame(1, $driver->getStats()['deletes']);

        // 任一層失敗時整體為 false
        $failing = $this->mockDriver();
        $failing->shouldReceive('forget')->once()->andReturn(true);
        $rejecting = $this->mockDriver();
        $rejecting->shouldReceive('forget')->once()->andReturn(false);
        $strictDriver = new LayeredCacheDriver([$failing, $rejecting]);
        $this->assertFalse($strictDriver->forget('k'));
        $this->assertSame(0, $strictDriver->getStats()['deletes']);
    }

    #[Test]
    public function flushClearsAllAvailableLayers(): void
    {
        $first = $this->mockDriver();
        $second = $this->mockDriver(false);
        $third = $this->mockDriver();

        $first->shouldReceive('flush')->once()->andReturn(true);
        $third->shouldReceive('flush')->once()->andReturn(true);

        $driver = new LayeredCacheDriver([$first, $second, $third]);
        $this->assertTrue($driver->flush());
        $this->assertSame(1, $driver->getStats()['clears']);

        $failing = $this->mockDriver();
        $failing->shouldReceive('flush')->once()->andReturn(false);
        $strictDriver = new LayeredCacheDriver([$failing]);
        $this->assertFalse($strictDriver->flush());
    }

    #[Test]
    public function manyCollectsValuesAcrossLayersWithPromotion(): void
    {
        $fast = $this->mockDriver();
        $slow = $this->mockDriver();

        $fast->shouldReceive('many')->with(['a', 'b', 'c'])->once()->andReturn([
            'a' => null,
            'b' => null,
            'c' => null,
        ]);
        // 第二層仍會收到完整的待查清單（移除動作在結果處理後才生效）
        $slow->shouldReceive('many')->with(['a', 'b', 'c'])->once()->andReturn([
            'a' => 'va',
            'b' => null,
        ]);
        // a 應被晉升到第一層
        $fast->shouldReceive('put')->with('a', 'va')->once()->andReturn(true);

        $driver = new LayeredCacheDriver([$fast, $slow]);
        $result = $driver->many(['a', 'b', 'c']);

        $this->assertSame('va', $result['a']);
        $this->assertNull($result['b']);
        $this->assertNull($result['c']);
        $this->assertSame(1, $driver->getStats()['layer_promotions']);
    }

    #[Test]
    public function putManyWritesToAllAvailableLayers(): void
    {
        $first = $this->mockDriver();
        $second = $this->mockDriver();

        $values = ['x' => 1, 'y' => 2];
        $first->shouldReceive('putMany')->with($values, 60)->once()->andReturn(true);
        $second->shouldReceive('putMany')->with($values, 60)->once()->andReturn(false);

        $driver = new LayeredCacheDriver([$first, $second]);
        $this->assertFalse($driver->putMany($values, 60));
        $this->assertSame(0, $driver->getStats()['sets']);

        $okFirst = $this->mockDriver();
        $okSecond = $this->mockDriver();
        $okFirst->shouldReceive('putMany')->with($values, 60)->once()->andReturn(true);
        $okSecond->shouldReceive('putMany')->with($values, 60)->once()->andReturn(true);
        $successDriver = new LayeredCacheDriver([$okFirst, $okSecond]);
        $this->assertTrue($successDriver->putMany($values, 60));
        $this->assertSame(2, $successDriver->getStats()['sets']);
    }

    #[Test]
    public function forgetManyAggregatesResultsAcrossLayers(): void
    {
        $first = $this->mockDriver();
        $second = $this->mockDriver();

        $keys = ['k1', 'k2'];
        $first->shouldReceive('forgetMany')->with($keys)->once()->andReturn(true);
        $second->shouldReceive('forgetMany')->with($keys)->once()->andReturn(true);

        $driver = new LayeredCacheDriver([$first, $second]);
        $this->assertTrue($driver->forgetMany($keys));
        $this->assertSame(2, $driver->getStats()['deletes']);
    }

    #[Test]
    public function forgettingPatternSumsDeletedCountAcrossLayers(): void
    {
        $first = $this->mockDriver();
        $second = $this->mockDriver(false);
        $third = $this->mockDriver();

        $first->shouldReceive('forgetPattern')->with('post:*')->once()->andReturn(3);
        $third->shouldReceive('forgetPattern')->with('post:*')->once()->andReturn(2);

        $driver = new LayeredCacheDriver([$first, $second, $third]);
        $this->assertSame(5, $driver->forgetPattern('post:*'));
        $this->assertSame(5, $driver->getStats()['deletes']);
    }

    #[Test]
    public function incrementRunsOnFirstAvailableLayerAndSyncsOthers(): void
    {
        $first = $this->mockDriver();
        $second = $this->mockDriver();
        $unavailable = $this->mockDriver(false);

        $first->shouldReceive('increment')->with('counter', 2)->once()->andReturn(7);
        $second->shouldReceive('put')->with('counter', 7)->once()->andReturn(true);

        $driver = new LayeredCacheDriver([$unavailable, $first, $second]);
        $this->assertSame(7, $driver->increment('counter', 2));

        $first->shouldReceive('decrement')->with('counter', 3)->once()->andReturn(4);
        $second->shouldReceive('put')->with('counter', 4)->once()->andReturn(true);

        $this->assertSame(4, $driver->decrement('counter', 3));
    }

    #[Test]
    public function incrementThrowsExceptionWhenNoLayersAvailable(): void
    {
        $down = $this->mockDriver(false);
        $driver = new LayeredCacheDriver([$down]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('沒有可用的快取層級');
        $driver->increment('counter');
    }

    #[Test]
    public function decrementThrowsExceptionWhenNoLayersAvailable(): void
    {
        $down = $this->mockDriver(false);
        $driver = new LayeredCacheDriver([$down]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('沒有可用的快取層級');
        $driver->decrement('counter');
    }

    #[Test]
    public function rememberExecutesCallbackOnlyOnMiss(): void
    {
        $layer = $this->mockDriver();
        $calls = 0;

        $layer->shouldReceive('get')->with('memo', null)->twice()->andReturn(null, 'computed');
        $layer->shouldReceive('put')->with('memo', 'computed', 120)->once()->andReturn(true);

        $driver = new LayeredCacheDriver([$layer]);
        $callback = static function () use (&$calls): string {
            $calls++;

            return 'computed';
        };

        $this->assertSame('computed', $driver->remember('memo', $callback, 120));
        $this->assertSame('computed', $driver->remember('memo', $callback, 120));
        $this->assertSame(1, $calls);
    }

    #[Test]
    public function rememberDoesNotStoreNullCallbackResult(): void
    {
        $layer = $this->mockDriver();
        $layer->shouldReceive('get')->with('null-memo', null)->once()->andReturn(null);

        $driver = new LayeredCacheDriver([$layer]);
        $this->assertNull($driver->remember('null-memo', static fn(): ?string => null, 120));
    }

    #[Test]
    public function rememberForeverStoresPermanentEntry(): void
    {
        $layer = $this->mockDriver();
        $layer->shouldReceive('get')->with('forever-key', null)->once()->andReturn(null);
        $layer->shouldReceive('put')->with('forever-key', 'kept', 0)->once()->andReturn(true);

        $driver = new LayeredCacheDriver([$layer]);
        $this->assertSame('kept', $driver->rememberForever(
            'forever-key',
            static fn(): string => 'kept',
        ));
    }

    #[Test]
    public function getStatsAggregatesLayerInformation(): void
    {
        $layer = $this->mockDriver();
        $layer->shouldReceive('getStats')->once()->andReturn(['hits' => 9]);

        $driver = new LayeredCacheDriver([$layer]);
        $stats = $driver->getStats();

        $this->assertSame(1, $stats['total_layers']);
        $layersInfo = $stats['layers'];
        $this->assertIsArray($layersInfo);
        $layerZero = $layersInfo['layer_0'] ?? null;
        $this->assertIsArray($layerZero);
        // 模擬物件的類別名稱帶有 Mockery 前綴，僅驗證結尾為介面名稱
        $driverName = $layerZero['driver'];
        $this->assertIsString($driverName);
        $this->assertStringEndsWith('CacheDriverInterface', $driverName);
        $this->assertTrue($layerZero['available']);
        /** @var mixed $nestedStats */
        $nestedStats = $layerZero['stats'];
        $this->assertIsArray($nestedStats);
        $this->assertSame(9, $nestedStats['hits']);
    }

    #[Test]
    public function getConnectionReturnsLayerInstances(): void
    {
        $first = $this->mockDriver();
        $second = $this->mockDriver();
        $driver = new LayeredCacheDriver([$first, $second]);

        $connection = $driver->getConnection();
        $this->assertIsArray($connection);
        $this->assertSame([$first, $second], array_values($connection));
        $this->assertSame([$first, $second], $driver->getLayers());
    }

    #[Test]
    public function isAvailableReflectsAnyLayerAvailability(): void
    {
        $down = $this->mockDriver(false);
        $driverAllDown = new LayeredCacheDriver([$down]);
        $this->assertFalse($driverAllDown->isAvailable());

        $up = $this->mockDriver();
        $driverWithUpLayer = new LayeredCacheDriver([$down, $up]);
        $this->assertTrue($driverWithUpLayer->isAvailable());
    }

    #[Test]
    public function cleanupSumsCleanedItemsFromAvailableLayers(): void
    {
        $first = $this->mockDriver();
        $second = $this->mockDriver(false);
        $third = $this->mockDriver();

        $first->shouldReceive('cleanup')->once()->andReturn(3);
        $third->shouldReceive('cleanup')->once()->andReturn(4);

        $driver = new LayeredCacheDriver([$first, $second, $third]);
        $this->assertSame(7, $driver->cleanup());
    }

    #[Test]
    public function addLayerAppendsAndRemoveLayerDeletes(): void
    {
        $first = $this->mockDriver();
        $second = $this->mockDriver();
        $driver = new LayeredCacheDriver([$first]);

        $driver->addLayer($second);
        $this->assertSame([$first, $second], $driver->getLayers());

        $this->assertTrue($driver->removeLayer($second));
        $this->assertSame([$first], $driver->getLayers());

        // 移除不存在的層級回傳 false
        $unknown = $this->mockDriver();
        $this->assertFalse($driver->removeLayer($unknown));
    }

    #[Test]
    public function resetStatsClearsCountersAndPropagatesToLayers(): void
    {
        $layer = $this->mockDriver();
        $layer->shouldReceive('get')->with('k', null)->once()->andReturn(null);
        $layer->shouldReceive('resetStats')->once()->andReturnNull();

        $driver = new LayeredCacheDriver([$layer]);
        $driver->get('k'); // 產生 miss 計數
        $this->assertSame(1, $driver->getStats()['misses']);

        $driver->resetStats();
        $stats = $driver->getStats();
        $this->assertSame(0, $stats['misses']);
        $this->assertSame(0, $stats['hits']);
        $this->assertSame(0, $stats['layer_promotions']);
    }
}
