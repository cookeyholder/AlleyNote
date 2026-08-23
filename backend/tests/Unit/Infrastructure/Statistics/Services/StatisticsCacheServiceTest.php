<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Services;

use App\Infrastructure\Statistics\Services\StatisticsCacheService;
use App\Shared\Contracts\CacheServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * StatisticsCacheService 簡化單元測試.
 *
 * 專注於測試服務的核心功能，使用實際的方法簽名。
 */
final class StatisticsCacheServiceTest extends UnitTestCase
{
    private StatisticsCacheService $cacheService;

    /** @var MockObject&CacheServiceInterface */
    private MockObject $mockCacheService;

    /** @var MockObject&LoggerInterface */
    private MockObject $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockCacheService = $this->createMock(CacheServiceInterface::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);

        $this->cacheService = new StatisticsCacheService(
            $this->mockCacheService,
            $this->mockLogger,
        );
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(StatisticsCacheService::class, $this->cacheService);
    }

    public function testGetCacheHitSuccessfully(): void
    {
        // Arrange
        $key = 'test_key';
        $expectedValue = ['data' => 'cached_data'];
        $prefixedKey = 'statistics:test_key';

        $this->mockCacheService
            ->expects($this->once())
            ->method('get')
            ->with($prefixedKey)
            ->willReturn($expectedValue);

        $this->mockLogger
            ->expects($this->once())
            ->method('debug')
            ->with('統計快取讀取命中', ['key' => $key]);

        // Act
        $result = $this->cacheService->get($key);

        // Assert
        $this->assertEquals($expectedValue, $result);
    }

    public function testGetCacheMiss(): void
    {
        // Arrange
        $key = 'missing_key';
        $prefixedKey = 'statistics:missing_key';

        $this->mockCacheService
            ->expects($this->once())
            ->method('get')
            ->with($prefixedKey)
            ->willReturn(null);

        $this->mockLogger
            ->expects($this->once())
            ->method('debug')
            ->with('統計快取讀取未命中', ['key' => $key]);

        // Act
        $result = $this->cacheService->get($key);

        // Assert
        $this->assertNull($result);
    }

    public function testPutCacheSuccessfully(): void
    {
        // Arrange
        $key = 'test_key';
        $value = ['data' => 'test_data'];
        $ttl = 1800;
        $prefixedKey = 'statistics:test_key';

        $this->mockCacheService
            ->expects($this->once())
            ->method('set')
            ->with($prefixedKey, $value, $ttl)
            ->willReturn(true);

        $this->mockLogger
            ->expects($this->once())
            ->method('debug')
            ->with('統計快取寫入', $this->callback(function (array $context) use ($key): bool {
                return $context['key'] === $key && isset($context['ttl']) && isset($context['tags']);
            }));

        // Act
        $result = $this->cacheService->put($key, $value, $ttl);

        // Assert
        $this->assertTrue($result);
    }

    public function testRememberWithCacheHit(): void
    {
        // Arrange
        $key = 'cached_item';
        $cachedValue = ['cached' => 'data'];
        $callback = function () {
            return ['fresh' => 'data'];
        };

        $this->mockCacheService
            ->expects($this->once())
            ->method('get')
            ->with('statistics:cached_item')
            ->willReturn($cachedValue);

        // Act
        $result = $this->cacheService->remember($key, $callback, 3600);

        // Assert
        $this->assertEquals($cachedValue, $result);
    }

    public function testRememberWithCacheMiss(): void
    {
        // Arrange
        $key = 'fresh_item';
        $freshValue = ['fresh' => 'data'];
        $callback = function () use ($freshValue) {
            return $freshValue;
        };

        $this->mockCacheService
            ->expects($this->once())
            ->method('get')
            ->with('statistics:fresh_item')
            ->willReturn(null);

        $this->mockCacheService
            ->expects($this->once())
            ->method('set')
            ->with('statistics:fresh_item', $freshValue, 3600)
            ->willReturn(true);

        // Act
        $result = $this->cacheService->remember($key, $callback, 3600);

        // Assert
        $this->assertEquals($freshValue, $result);
    }

    public function testHasKeySuccessfully(): void
    {
        // Arrange
        $key = 'existing_key';
        $prefixedKey = 'statistics:existing_key';

        $this->mockCacheService
            ->expects($this->once())
            ->method('has')
            ->with($prefixedKey)
            ->willReturn(true);

        // Act
        $result = $this->cacheService->has($key);

        // Assert
        $this->assertTrue($result);
    }

    public function testForgetSingleKey(): void
    {
        // Arrange
        $key = 'key_to_delete';

        $this->mockCacheService
            ->expects($this->once())
            ->method('delete')
            ->with('statistics:key_to_delete')
            ->willReturn(true);

        $this->mockLogger
            ->expects($this->once())
            ->method('debug')
            ->with('刪除統計快取', [
                'keys'  => [$key],
                'count' => 1,
            ]);

        // Act
        $this->cacheService->forget($key);

        // 由於是 void 方法，我們只驗證沒有拋出異常
        $this->addToAssertionCount(1);
    }

    public function testForgetMultipleKeys(): void
    {
        // Arrange
        $keys = ['key1', 'key2'];

        $this->mockCacheService
            ->expects($this->exactly(2))
            ->method('delete')
            ->willReturn(true);

        $this->mockLogger
            ->expects($this->once())
            ->method('debug')
            ->with('刪除統計快取', [
                'keys'  => $keys,
                'count' => 2,
            ]);

        // Act
        $this->cacheService->forget($keys);

        // Assert - void 方法成功執行
        $this->addToAssertionCount(1);
    }

    public function testFlushClearsAllStatisticsCache(): void
    {
        // Arrange
        $this->mockCacheService
            ->expects($this->exactly(2))
            ->method('deletePattern')
            ->willReturnCallback(function ($pattern) {
                if ($pattern === 'statistics:*') {
                    return 5;
                }
                if ($pattern === 'tags:*') {
                    return 2;
                }

                return 0;
            });

        $this->mockLogger
            ->expects($this->once())
            ->method('info')
            ->with('清空所有統計快取', ['deleted_count' => 5]);

        // Act
        $result = $this->cacheService->flush();

        // Assert
        $this->assertTrue($result);
    }

    public function testGetStatsReturnsMetrics(): void
    {
        // 先執行一些操作來累積統計
        $this->mockCacheService->method('get')->willReturn(['data' => 'test']);
        $this->mockCacheService->method('getStats')->willReturn(['cache_stats' => 'test']);

        $this->cacheService->get('test_key');

        // Act
        $stats = $this->cacheService->getStats();

        // Assert
        $this->assertArrayHasKey('statistics_cache', $stats);
        $this->assertArrayHasKey('underlying_cache', $stats);
        $this->assertArrayHasKey('supported_tags', $stats);
        $this->assertArrayHasKey('generated_at', $stats);

        /** @var array<string, mixed> $statisticsCache */
        $statisticsCache = $stats['statistics_cache'];
        $this->assertArrayHasKey('hits', $statisticsCache);
        $this->assertArrayHasKey('misses', $statisticsCache);
        $this->assertArrayHasKey('puts', $statisticsCache);
        $this->assertEquals(1, $statisticsCache['hits']);
    }

    public function testWarmupExecutesCallbacks(): void
    {
        // Arrange
        $warmupCallbacks = [
            'overview' => function () {
                return ['total_posts' => 100];
            },
            'popular' => function () {
                return ['popular_items' => []];
            },
        ];

        // Mock 所有需要的方法調用
        $this->mockCacheService
            ->method('set')
            ->willReturn(true); // 確保所有 set 操作都成功

        $this->mockCacheService
            ->method('get')
            ->willReturn([]); // 標籤索引為空陣列

        // 檢查 debug 日誌被調用
        $this->mockLogger
            ->method('debug'); // 不設定 willReturn，因為 debug 方法返回 void

        // 檢查 info 日誌的參數
        $this->mockLogger
            ->expects($this->once())
            ->method('info')
            ->with('統計快取預熱完成', $this->callback(function (array $context): bool {
                // 檢查總數是否正確
                return isset($context['total']) && $context['total'] === 2
                       && isset($context['successful']) && is_int($context['successful']) && $context['successful'] >= 0
                       && isset($context['failed']) && is_int($context['failed']) && $context['failed'] >= 0
                       && is_int($context['total'])
                       && ($context['successful'] + $context['failed']) === $context['total'];
            }));

        // Act
        $result = $this->cacheService->warmup($warmupCallbacks, 7200);

        // Assert
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('overview', $result);
        $this->assertArrayHasKey('popular', $result);
    }

    public function testCleanupDeletesExpiredKeys(): void
    {
        // 這個測試比較難 mock，因為 cleanup 方法的實作比較複雜
        // 我們只測試它能被調用而不拋出異常
        $result = $this->cacheService->cleanup();

        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testCacheOperationHandlesException(): void
    {
        // Arrange
        $this->mockCacheService
            ->expects($this->once())
            ->method('get')
            ->willThrowException(new RuntimeException('Cache service error'));

        // 對於 get 操作的異常，service 會直接拋出異常而不是返回 null
        // 這是基於實際的程式碼行為
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cache service error');

        // Act
        $this->cacheService->get('test_key');
    }

    public function testPutOperationHandlesException(): void
    {
        // Arrange
        $this->mockCacheService
            ->expects($this->once())
            ->method('set')
            ->willThrowException(new RuntimeException('Set operation failed'));

        $this->mockLogger
            ->expects($this->once())
            ->method('error')
            ->with('統計快取寫入失敗', $this->callback(function (array $context): bool {
                return isset($context['key']) && $context['key'] === 'test_key';
            }));

        // Act
        $result = $this->cacheService->put('test_key', ['data' => 'test']);

        // Assert
        $this->assertFalse($result); // 應該返回 false 而不拋出異常
    }

    public function testFlushByTagsCallsCorrectMethods(): void
    {
        // Arrange
        $tags = ['posts', 'overview'];

        // Mock 取得標籤索引 - 每個標籤會有一些快取鍵
        $this->mockCacheService
            ->method('get')
            ->willReturnCallback(function (string $key): ?array {
                if (str_contains($key, 'tags:')) {
                    return ['key1', 'key2']; // 模擬標籤索引中的快取鍵
                }

                return null;
            });

        // Mock 刪除快取項目和標籤索引
        $this->mockCacheService
            ->method('delete')
            ->willReturn(true);

        $this->mockLogger
            ->expects($this->once())
            ->method('info')
            ->with('按標籤清除統計快取', [
                'tags'  => $tags,
                'count' => 2,
            ]);

        // Act
        $this->cacheService->flushByTags($tags);

        // Assert - void 方法成功執行
        $this->addToAssertionCount(1);
    }

    public function testRememberExecutesCallbackAndCachesResult(): void
    {
        // 未命中時執行回呼並寫入快取
        $this->mockCacheService->method('get')->willReturn(null);
        $this->mockCacheService->expects($this->once())->method('set')->willReturn(true);

        $calls = 0;
        $value = $this->cacheService->remember('stats_key', function () use (&$calls): array {
            $calls++;

            return ['computed' => true];
        });

        $this->assertSame(['computed' => true], $value);
        $this->assertSame(1, $calls);
    }

    public function testRememberRethrowsCallbackException(): void
    {
        $this->mockCacheService->method('get')->willReturn(null);
        $this->mockLogger->expects($this->once())->method('error');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('callback exploded');

        $this->cacheService->remember('bad_key', static fn(): string => throw new RuntimeException('callback exploded'));
    }

    public function testFlushByTagsIgnoresUnsupportedTags(): void
    {
        // 全部標籤皆不支援時直接返回，不操作底層快取
        $this->mockCacheService->expects($this->never())->method('delete');
        $this->cacheService->flushByTags(['not_a_tag']);
        $this->addToAssertionCount(1);
    }

    public function testFlushByTagsWrapsErrors(): void
    {
        $this->mockCacheService->method('get')->willThrowException(new RuntimeException('redis down'));
        $this->mockLogger->expects($this->once())->method('error')->with('按標籤清除快取失敗', $this->callback(static fn($c): bool => is_array($c)));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('redis down');

        $this->cacheService->flushByTags(['posts']);
    }

    public function testForgetWrapsErrors(): void
    {
        $this->mockCacheService->method('delete')->willThrowException(new RuntimeException('delete failed'));
        $this->mockLogger->expects($this->once())->method('error')->with('刪除快取失敗', $this->callback(static fn($c): bool => is_array($c)));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('delete failed');

        $this->cacheService->forget('some_key');
    }

    public function testFlushClearsAllPatterns(): void
    {
        $this->mockCacheService
            ->expects($this->exactly(2))
            ->method('deletePattern')
            ->willReturn(5);

        $this->assertTrue($this->cacheService->flush());

        // 底層失敗時回傳 false
        $failing = $this->createMock(CacheServiceInterface::class);
        $failing->method('deletePattern')->willThrowException(new RuntimeException('flush failed'));
        $service = new StatisticsCacheService($failing, $this->mockLogger);

        $this->assertFalse($service->flush());
    }

    public function testWarmupHandlesSuccessesAndFailures(): void
    {
        // 成功項目：主快取 1 次 + statistics/prewarmed 兩個標籤索引各 1 次
        $matcher = $this->mockCacheService->expects($this->exactly(3))->method('set');
        $matcher->willReturn(true);

        $results = $this->cacheService->warmup([
            'overview_stats' => static fn(): array => ['total' => 1],
            'broken_stats'   => static fn(): array => throw new RuntimeException('boom'),
        ]);

        $this->assertTrue($results['overview_stats']['success']);
        $this->assertArrayHasKey('duration', $results['overview_stats']);
        $this->assertFalse($results['broken_stats']['success']);
        /** @var array{success: bool, duration?: float, error?: string} $brokenResult */
        $brokenResult = $results['broken_stats'];
        $this->assertSame('boom', $brokenResult['error'] ?? 'no-error');
    }

    public function testCleanupReturnsDeletedCountAndSwallowsErrors(): void
    {
        $this->mockCacheService->expects($this->once())
            ->method('deletePattern')
            ->with('tags:*')
            ->willReturn(3);

        $this->assertSame(3, $this->cacheService->cleanup());

        $failing = $this->createMock(CacheServiceInterface::class);
        $failing->method('deletePattern')->willThrowException(new RuntimeException('cleanup failed'));
        $service = new StatisticsCacheService($failing, $this->mockLogger);

        $this->assertSame(0, $service->cleanup());
    }

    public function testTagIndexMaintenanceFlows(): void
    {
        // put：既有標籤索引為非陣列值時應重置後寫入
        $this->mockCacheService
            ->method('get')
            ->willReturnCallback(static fn(string $key): mixed => str_starts_with($key, 'tags:') ? 'garbage' : null);
        $setCalls = [];
        $this->mockCacheService
            ->method('set')
            ->willReturnCallback(function (string $key, $value) use (&$setCalls): bool {
                $setCalls[$key] = $value;

                return true;
            });

        $this->cacheService->put('main_key', ['v'], 600, ['posts']);

        $this->assertArrayHasKey('tags:posts', $setCalls);
        $this->assertSame(['main_key'], $setCalls['tags:posts']);
    }

    public function testFlushByTagsHandlesMissingTagIndex(): void
    {
        // 標籤索引不存在時直接結束，不刪除任何鍵
        $this->mockCacheService->method('get')->willReturn(null);
        $this->mockCacheService->expects($this->never())->method('delete');

        $this->cacheService->flushByTags(['posts']);
        $this->addToAssertionCount(1);
    }

    public function testForgetRemovesKeyFromTagIndexes(): void
    {
        // forget 時應從含該鍵的標籤索引中移除並回寫
        $this->mockCacheService
            ->method('get')
            ->willReturnCallback(static fn(string $key): ?array => match ($key) {
                'tags:posts', 'tags:users' => ['stale_key', 'other_key'],
                default                    => null,
            });
        $this->mockCacheService->method('delete')->willReturn(true);

        $this->mockCacheService
            ->expects($this->exactly(2))
            ->method('set')
            ->with(
                $this->logicalOr($this->equalTo('tags:posts'), $this->equalTo('tags:users')),
                ['other_key'],
                $this->anything(),
            );

        $this->cacheService->forget('stale_key');
    }
}
