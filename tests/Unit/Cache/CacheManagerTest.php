<?php

declare(strict_types=1);

namespace Tests\Unit\Cache;

use App\Infrastructure\Cache\CacheKeys;
use App\Infrastructure\Cache\CacheManager;
use PHPUnit\Framework\TestCase;

class CacheManagerTest extends TestCase
{
    private CacheManager $cacheManager;

    protected function setUp(): void
    {
        $this->cacheManager = new CacheManager(3600);
    }

    public function testSetAndGet(): void
    {
        $key = 'test:key';
        $value = 'test value';

        $result = $this->cacheManager->set($key, $value);
        $this->assertTrue($result);

        $retrieved = $this->cacheManager->get($key);
        $this->assertEquals($value, $retrieved);
    }

    public function testGetWithDefault(): void
    {
        $key = 'non:existent:key';
        $default = 'default value';

        $result = $this->cacheManager->get($key, $default);
        $this->assertEquals($default, $result);
    }

    public function testHas(): void
    {
        $key = 'test:exists';
        $value = 'some value';

        $this->assertFalse($this->cacheManager->has($key));

        $this->cacheManager->set($key, $value);
        $this->assertTrue($this->cacheManager->has($key));
    }

    public function testDelete(): void
    {
        $key = 'test:delete';
        $value = 'value to delete';

        $this->cacheManager->set($key, $value);
        $this->assertTrue($this->cacheManager->has($key));

        $result = $this->cacheManager->delete($key);
        $this->assertTrue($result);
        $this->assertFalse($this->cacheManager->has($key));
    }

    public function testClear(): void
    {
        $this->cacheManager->set('key1', 'value1');
        $this->cacheManager->set('key2', 'value2');

        $this->assertTrue($this->cacheManager->has('key1'));
        $this->assertTrue($this->cacheManager->has('key2'));

        $result = $this->cacheManager->clear();
        $this->assertTrue($result);

        $this->assertFalse($this->cacheManager->has('key1'));
        $this->assertFalse($this->cacheManager->has('key2'));
    }

    public function testRemember(): void
    {
        $key = 'test:remember';
        $expectedValue = 'computed value';

        $callbackExecuted = false;
        $callback = function () use ($expectedValue, &$callbackExecuted) {
            $callbackExecuted = true;

            return $expectedValue;
        };

        // 第一次呼叫應該執行 callback
        $result1 = $this->cacheManager->remember($key, $callback);
        $this->assertEquals($expectedValue, $result1);
        $this->assertTrue($callbackExecuted);

        // 重置 flag
        $callbackExecuted = false;

        // 第二次呼叫應該從快取取得，不執行 callback
        $result2 = $this->cacheManager->remember($key, $callback);
        $this->assertEquals($expectedValue, $result2);
        $this->assertFalse($callbackExecuted);
    }

    public function testRememberForever(): void
    {
        $key = 'test:forever';
        $expectedValue = 'forever value';

        $callback = function () use ($expectedValue) {
            return $expectedValue;
        };

        $result = $this->cacheManager->rememberForever($key, $callback);
        $this->assertEquals($expectedValue, $result);

        // 驗證值被快取且永不過期
        $this->assertTrue($this->cacheManager->has($key));
        $this->assertEquals($expectedValue, $this->cacheManager->get($key));
    }

    public function testTtlExpiration(): void
    {
        $key = 'test:ttl';
        $value = 'expires soon';
        $shortTtl = 1; // 1 秒

        $this->cacheManager->set($key, $value, $shortTtl);
        $this->assertTrue($this->cacheManager->has($key));

        // 等待過期
        sleep(2);

        $this->assertFalse($this->cacheManager->has($key));
        $this->assertNull($this->cacheManager->get($key));
    }

    public function testMany(): void
    {
        $keys = ['key1', 'key2', 'key3'];

        $this->cacheManager->set('key1', 'value1');
        $this->cacheManager->set('key2', 'value2');
        // key3 不設定，應該返回 null

        $result = $this->cacheManager->many($keys);

        $expected = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => null,
        ];

        $this->assertEquals($expected, $result);
    }

    public function testPutMany(): void
    {
        $values = [
            'bulk1' => 'value1',
            'bulk2' => 'value2',
            'bulk3' => 'value3',
        ];

        $result = $this->cacheManager->putMany($values);
        $this->assertTrue($result);

        foreach ($values as $key => $expectedValue) {
            $this->assertTrue($this->cacheManager->has($key));
            $this->assertEquals($expectedValue, $this->cacheManager->get($key));
        }
    }

    public function testDeletePattern(): void
    {
        // 設定一些測試快取
        $this->cacheManager->set('alleynote:post:1', 'post1');
        $this->cacheManager->set('alleynote:post:2', 'post2');
        $this->cacheManager->set('alleynote:user:1', 'user1');
        $this->cacheManager->set('other:post:1', 'other');

        // 刪除 alleynote:post:* 模式的快取
        $deleted = $this->cacheManager->deletePattern('alleynote:post:*');
        $this->assertEquals(2, $deleted);

        // 驗證 post 相關快取被刪除
        $this->assertFalse($this->cacheManager->has('alleynote:post:1'));
        $this->assertFalse($this->cacheManager->has('alleynote:post:2'));

        // 驗證其他快取仍存在
        $this->assertTrue($this->cacheManager->has('alleynote:user:1'));
        $this->assertTrue($this->cacheManager->has('other:post:1'));
    }

    public function testIncrement(): void
    {
        $key = 'test:counter';

        // 從 0 開始遞增
        $result1 = $this->cacheManager->increment($key);
        $this->assertEquals(1, $result1);

        // 繼續遞增
        $result2 = $this->cacheManager->increment($key, 5);
        $this->assertEquals(6, $result2);

        // 驗證快取中的值
        $this->assertEquals(6, $this->cacheManager->get($key));
    }

    public function testDecrement(): void
    {
        $key = 'test:countdown';

        // 先設定初始值
        $this->cacheManager->set($key, 10);

        $result1 = $this->cacheManager->decrement($key);
        $this->assertEquals(9, $result1);

        $result2 = $this->cacheManager->decrement($key, 3);
        $this->assertEquals(6, $result2);

        $this->assertEquals(6, $this->cacheManager->get($key));
    }

    public function testGetStats(): void
    {
        // 設定一些快取項目
        $this->cacheManager->set('key1', 'value1', 3600);
        $this->cacheManager->set('key2', 'value2', 1); // 很快過期

        sleep(2); // 等待 key2 過期

        $stats = $this->cacheManager->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_keys', $stats);
        $this->assertArrayHasKey('active_keys', $stats);
        $this->assertArrayHasKey('expired_keys', $stats);
        $this->assertArrayHasKey('memory_usage', $stats);

        $this->assertIsInt((is_array($stats) && isset((is_array($stats) ? $stats['total_keys'] : (is_object($stats) ? $stats->total_keys : null)))) ? (is_array($stats) ? $stats['total_keys'] : (is_object($stats) ? $stats->total_keys : null)) : null);
        $this->assertIsInt((is_array($stats) && isset((is_array($stats) ? $stats['active_keys'] : (is_object($stats) ? $stats->active_keys : null)))) ? (is_array($stats) ? $stats['active_keys'] : (is_object($stats) ? $stats->active_keys : null)) : null);
        $this->assertIsInt((is_array($stats) && isset((is_array($stats) ? $stats['expired_keys'] : (is_object($stats) ? $stats->expired_keys : null)))) ? (is_array($stats) ? $stats['expired_keys'] : (is_object($stats) ? $stats->expired_keys : null)) : null);
        $this->assertIsString((is_array($stats) && isset((is_array($stats) ? $stats['memory_usage'] : (is_object($stats) ? $stats->memory_usage : null)))) ? (is_array($stats) ? $stats['memory_usage'] : (is_object($stats) ? $stats->memory_usage : null)) : null);
    }

    public function testCleanup(): void
    {
        // 設定一些快取項目，部分很快過期
        $this->cacheManager->set('key1', 'value1', 3600);
        $this->cacheManager->set('key2', 'value2', 1);
        $this->cacheManager->set('key3', 'value3', 1);

        sleep(2); // 等待過期

        $cleaned = $this->cacheManager->cleanup();
        $this->assertEquals(2, $cleaned);

        // 驗證過期的被清理，有效的仍存在
        $this->assertTrue($this->cacheManager->has('key1'));
        $this->assertFalse($this->cacheManager->has('key2'));
        $this->assertFalse($this->cacheManager->has('key3'));
    }

    public function testIsValidKey(): void
    {
        $validKey = CacheKeys::post(123);
        $invalidKey = 'random:key';

        $this->assertTrue($this->cacheManager->isValidKey($validKey));
        $this->assertFalse($this->cacheManager->isValidKey($invalidKey));
    }

    public function testComplexDataTypes(): void
    {
        $key = 'test:complex';

        // 測試陣列
        $arrayData = ['name' => 'test', 'numbers' => [1, 2, 3]];
        $this->cacheManager->set($key, $arrayData);
        $this->assertEquals($arrayData, $this->cacheManager->get($key));

        // 測試物件
        $objectData = (object) ['property' => 'value'];
        $this->cacheManager->set($key, $objectData);
        $this->assertEquals($objectData, $this->cacheManager->get($key));

        // 測試 null
        $this->cacheManager->set($key, null);
        $this->assertNull($this->cacheManager->get($key));

        // 測試布林值
        $this->cacheManager->set($key, true);
        $this->assertTrue($this->cacheManager->get($key));

        $this->cacheManager->set($key, false);
        $this->assertFalse($this->cacheManager->get($key));
    }

    public function testDefaultTtl(): void
    {
        $customTtl = 1800;
        $manager = new CacheManager($customTtl);

        $key = 'test:default:ttl';
        $value = 'test value';

        $manager->set($key, $value); // 使用預設 TTL

        $this->assertTrue($manager->has($key));
        $this->assertEquals($value, $manager->get($key));
    }
}
