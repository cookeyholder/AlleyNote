<?php

declare(strict_types=1);

namespace Tests\Shared\Cache;

use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Drivers\MemoryCacheDriver;
use App\Shared\Cache\Drivers\FileCacheDriver;
use App\Shared\Cache\Services\CacheManager;
use App\Shared\Cache\Services\DefaultCacheStrategy;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class CacheSystemTest extends TestCase
{
    private CacheManager $cacheManager;
    private DefaultCacheStrategy $strategy;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/alleynote_cache_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);

        $this->strategy = new DefaultCacheStrategy();
        $this->cacheManager = new CacheManager($this->strategy, new NullLogger());

        // 新增記憶體驅動
        $memoryDriver = new MemoryCacheDriver();
        $this->cacheManager->addDriver('memory', $memoryDriver, 90);

        // 新增檔案驅動
        $fileDriver = new FileCacheDriver($this->tempDir);
        $this->cacheManager->addDriver('file', $fileDriver, 50);

        $this->cacheManager->setDefaultDriver('memory');
    }

    protected function tearDown(): void
    {
        // 清理測試檔案
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob($this->tempDir . '/*'));
            rmdir($this->tempDir);
        }
    }

    public function testCacheManagerBasicOperations(): void
    {
        // 測試基本儲存和取得
        $this->assertTrue($this->cacheManager->put('test_key', 'test_value', 3600));
        $this->assertEquals('test_value', $this->cacheManager->get('test_key'));
        $this->assertTrue($this->cacheManager->has('test_key'));

        // 測試刪除
        $this->assertTrue($this->cacheManager->forget('test_key'));
        $this->assertFalse($this->cacheManager->has('test_key'));
        $this->assertNull($this->cacheManager->get('test_key'));
    }

    public function testCacheManagerMultipleDrivers(): void
    {
        // 在記憶體驅動中儲存資料
        $this->cacheManager->put('memory_key', 'memory_value', 3600);
        
        // 確認可以取得資料
        $this->assertEquals('memory_value', $this->cacheManager->get('memory_key'));
        
        // 確認驅動存在
        $this->assertNotNull($this->cacheManager->getDriver('memory'));
        $this->assertNotNull($this->cacheManager->getDriver('file'));
        
        // 測試可用驅動
        $availableDrivers = $this->cacheManager->getAvailableDrivers();
        $this->assertCount(2, $availableDrivers);
        $this->assertArrayHasKey('memory', $availableDrivers);
        $this->assertArrayHasKey('file', $availableDrivers);
    }

    public function testCacheManagerBatchOperations(): void
    {
        $values = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        // 批次儲存
        $this->assertTrue($this->cacheManager->putMany($values, 3600));

        // 批次取得
        $result = $this->cacheManager->many(array_keys($values));
        $this->assertEquals($values, $result);

        // 清空快取
        $this->assertTrue($this->cacheManager->flush());

        // 確認已清空
        foreach (array_keys($values) as $key) {
            $this->assertFalse($this->cacheManager->has($key));
        }
    }

    public function testCacheStrategyFiltering(): void
    {
        // 新增排除模式
        $this->strategy->addExcludePattern('temp:*');

        // 符合排除模式的鍵應該被拒絕
        $this->assertFalse($this->strategy->shouldCache('temp:test', 'value', 3600));

        // 不符合排除模式的鍵應該被接受
        $this->assertTrue($this->strategy->shouldCache('normal:test', 'value', 3600));
    }

    public function testCacheStrategyTtlAdjustment(): void
    {
        // 測試 TTL 調整
        $adjustedTtl = $this->strategy->decideTtl('test:key', 'small_value', 1800);
        $this->assertGreaterThan(0, $adjustedTtl);

        // 測試大資料的 TTL 調整
        $largeData = str_repeat('x', 20000);
        $adjustedTtl = $this->strategy->decideTtl('test:key', $largeData, 3600);
        $this->assertGreaterThan(0, $adjustedTtl);
        $this->assertLessThanOrEqual(3600, $adjustedTtl);
    }

    public function testCacheManagerStats(): void
    {
        // 執行一些操作
        $this->cacheManager->put('test1', 'value1', 3600);
        $this->cacheManager->get('test1');
        $this->cacheManager->get('nonexistent');
        $this->cacheManager->forget('test1');

        $stats = $this->cacheManager->getStats();

        // 檢查統計資料
        $this->assertArrayHasKey('total_gets', $stats);
        $this->assertArrayHasKey('total_hits', $stats);
        $this->assertArrayHasKey('total_misses', $stats);
        $this->assertArrayHasKey('total_puts', $stats);
        $this->assertArrayHasKey('total_deletes', $stats);
        $this->assertArrayHasKey('hit_rate', $stats);
        $this->assertArrayHasKey('drivers', $stats);

        $this->assertEquals(2, $stats['total_gets']);
        $this->assertEquals(1, $stats['total_hits']);
        $this->assertEquals(1, $stats['total_misses']);
        $this->assertEquals(1, $stats['total_puts']);
        $this->assertEquals(1, $stats['total_deletes']);
    }

    public function testCacheDriverFailover(): void
    {
        // 移除記憶體驅動來模擬失敗
        $this->cacheManager->removeDriver('memory');
        $this->cacheManager->setDefaultDriver('file');

        // 應該回退到檔案驅動
        $this->assertTrue($this->cacheManager->put('failover_test', 'value', 3600));
        $this->assertEquals('value', $this->cacheManager->get('failover_test'));
    }

    public function testMemoryDriverOperations(): void
    {
        $memoryDriver = new MemoryCacheDriver(100);

        $this->assertTrue($memoryDriver->isAvailable());
        $this->assertTrue($memoryDriver->put('test', 'value', 3600));
        $this->assertTrue($memoryDriver->has('test'));
        $this->assertEquals('value', $memoryDriver->get('test'));
        $this->assertTrue($memoryDriver->forget('test'));
        $this->assertFalse($memoryDriver->has('test'));
    }

    public function testFileDriverOperations(): void
    {
        $fileDriver = new FileCacheDriver($this->tempDir);

        $this->assertTrue($fileDriver->isAvailable());
        $this->assertTrue($fileDriver->put('test', 'value', 3600));
        $this->assertTrue($fileDriver->has('test'));
        $this->assertEquals('value', $fileDriver->get('test'));
        $this->assertTrue($fileDriver->forget('test'));
        $this->assertFalse($fileDriver->has('test'));
    }

    public function testTtlExpiration(): void
    {
        $memoryDriver = new MemoryCacheDriver();

        // 設定很短的 TTL
        $this->assertTrue($memoryDriver->put('ttl_test', 'value', 1));
        $this->assertTrue($memoryDriver->has('ttl_test'));

        // 等待過期
        sleep(2);

        // 確認已過期
        $this->assertFalse($memoryDriver->has('ttl_test'));
        $this->assertNull($memoryDriver->get('ttl_test'));
    }

    public function testCacheWithComplexData(): void
    {
        $complexData = [
            'string' => 'test',
            'number' => 123,
            'array' => ['nested' => 'value'],
            'object' => (object) ['property' => 'value'],
        ];

        $this->assertTrue($this->cacheManager->put('complex', $complexData, 3600));
        $retrieved = $this->cacheManager->get('complex');

        $this->assertEquals($complexData, $retrieved);
    }
}