<?php

declare(strict_types=1);

namespace {
    if (!class_exists('RedisException')) {
        class RedisException extends Exception {}
    }
    if (!class_exists('Redis')) {
        class Redis {}
    }
}

namespace Tests\Unit\Infrastructure\Routing {
    use App\Infrastructure\Routing\Cache\FileRouteCache;
    use App\Infrastructure\Routing\Cache\MemoryRouteCache;
    use App\Infrastructure\Routing\Cache\RedisRouteCache;
    use App\Infrastructure\Routing\Cache\RouteCacheFactory;
    use App\Infrastructure\Routing\Core\Route;
    use App\Infrastructure\Routing\Core\RouteCollection;
    use InvalidArgumentException;
    use Mockery;
    use Redis;
    use RedisException;
    use Tests\Support\UnitTestCase;

    /**
     * 路由快取單元測試 (MemoryRouteCache, FileRouteCache, RedisRouteCache, RouteCacheFactory).
     */
    class RouteCacheTest extends UnitTestCase
    {
        private string $tempCacheDir;

        protected function setUp(): void
        {
            parent::setUp();
            $this->tempCacheDir = sys_get_temp_dir() . '/route_cache_test_' . uniqid();
            mkdir($this->tempCacheDir, 0o777, true);
        }

        protected function tearDown(): void
        {
            $files = glob($this->tempCacheDir . '/*');
            if ($files) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }
            if (is_dir($this->tempCacheDir)) {
                rmdir($this->tempCacheDir);
            }
            parent::tearDown();
        }

        /**
         * 測試 MemoryRouteCache 功能.
         */
        public function testMemoryRouteCache(): void
        {
            $cache = new MemoryRouteCache();
            $this->assertEquals('memory://routes', $cache->getCachePath());
            $this->assertEquals(3600, $cache->getTtl());

            $cache->setTtl(1800);
            $this->assertEquals(1800, $cache->getTtl());

            $this->assertFalse($cache->isValid());
            $this->assertNull($cache->load());

            $routes = new RouteCollection();
            $routes->add(new Route(['GET'], '/test', 'TestController@index'));

            $this->assertTrue($cache->store($routes));
            $this->assertTrue($cache->isValid());

            $loaded = $cache->load();
            $this->assertNotNull($loaded);
            $this->assertEquals(1, $loaded->count());

            $stats = $cache->getStats();
            $this->assertEquals(1, $stats['hits']);
            $this->assertEquals(1, $stats['misses']);
            $this->assertGreaterThan(0, $stats['size']);

            $this->assertNotEmpty($cache->getCache());
            $this->assertNotEmpty($cache->getTimestamps());
            $this->assertFalse($cache->isItemExpired('routes'));
            $this->assertTrue($cache->isItemExpired('nonexistent'));

            // 模擬時間過期
            $cache->setTtl(10);
            $reflection = new \ReflectionClass($cache);
            $tsProp = $reflection->getProperty('timestamps');
            $tsProp->setValue($cache, ['routes' => time() - 100]);

            $this->assertTrue($cache->isItemExpired('routes'));
            $this->assertFalse($cache->isValid());
            $this->assertNull($cache->load());

            // 測試 cleanupExpired
            $cache->store($routes);
            $cache->setTtl(10);
            $tsProp->setValue($cache, ['routes' => time() - 100]);
            $cleaned = $cache->cleanupExpired();
            $this->assertGreaterThanOrEqual(1, $cleaned);

            // 測試 load 載入非 RouteCollection 物件時處理
            $cache->store($routes);
            $cache->setTtl(3600);
            $cacheProp = $reflection->getProperty('cache');
            $cacheProp->setValue($cache, ['routes' => 'not_a_route_collection']);
            $this->assertNull($cache->load());

            // 測試 clear
            $this->assertTrue($cache->clear());
            $this->assertFalse($cache->isValid());
        }

        /**
         * 測試 FileRouteCache 功能.
         */
        public function testFileRouteCache(): void
        {
            $cache = new FileRouteCache($this->tempCacheDir);
            $this->assertEquals($this->tempCacheDir, $cache->getCachePath());
            $this->assertEquals(3600, $cache->getTtl());

            $cache->setTtl(7200);
            $this->assertEquals(7200, $cache->getTtl());

            $this->assertFalse($cache->isValid());
            $this->assertNull($cache->load());

            $routes = new RouteCollection();
            $routes->add(new Route(['GET'], '/file-route', 'FileController@index'));

            $this->assertTrue($cache->store($routes));
            $this->assertTrue($cache->isValid());

            $loaded = $cache->load();
            $this->assertNotNull($loaded);
            $this->assertEquals(1, $loaded->count());

            $stats = $cache->getStats();
            $this->assertEquals(1, $stats['hits']);
            $this->assertEquals(1, $stats['misses']);
            $this->assertGreaterThan(0, $stats['size']);

            // 重新實例化以測試 loadStats
            $cache2 = new FileRouteCache($this->tempCacheDir);
            $stats2 = $cache2->getStats();
            $this->assertEquals(1, $stats2['hits']);

            // 模擬檔案過期
            $cacheFile = $this->tempCacheDir . '/routes.cache';
            touch($cacheFile, time() - 10000);
            $cache->setTtl(10);
            $this->assertFalse($cache->isValid());
            $this->assertNull($cache->load());

            // 測試損壞的檔案快取
            $cache->setTtl(3600);
            file_put_contents($cacheFile, 'corrupted_serialized_data');
            touch($cacheFile, time());
            $this->assertNull($cache->load());

            // 測試 clear
            $this->assertTrue($cache->clear());
            $this->assertFalse($cache->isValid());
        }

        /**
         * 測試 RedisRouteCache 功能.
         */
        public function testRedisRouteCache(): void
        {
            $redisMock = Mockery::mock(Redis::class);
            $cacheKey = 'route_cache:routes';
            $statsKey = 'route_cache:stats';

            $redisMock->shouldReceive('get')->with($statsKey)->andReturn(json_encode(['hits' => 5]))->byDefault();

            $cache = new RedisRouteCache($redisMock, 'routes');
            $this->assertSame($redisMock, $cache->getRedis());
            $this->assertEquals('redis://routes', $cache->getCachePath());
            $this->assertEquals(3600, $cache->getTtl());

            $cache->setTtl(1800);
            $this->assertEquals(1800, $cache->getTtl());

            // 測試 isConnected
            $redisMock->shouldReceive('ping')->once()->andReturn('+PONG');
            $this->assertTrue($cache->isConnected());

            $redisMock->shouldReceive('ping')->once()->andThrow(new RedisException('Connection lost'));
            $this->assertFalse($cache->isConnected());

            // 測試 isValid
            $redisMock->shouldReceive('exists')->with($cacheKey)->once()->andReturn(1);
            $this->assertTrue($cache->isValid());

            $redisMock->shouldReceive('exists')->with($cacheKey)->once()->andThrow(new RedisException('Error'));
            $this->assertFalse($cache->isValid());

            // 測試 store
            $routes = new RouteCollection();
            $routes->add(new Route(['GET'], '/redis', 'RedisController@index'));
            $serialized = serialize($routes);

            $redisMock->shouldReceive('setex')->with($cacheKey, 1800, $serialized)->once()->andReturn(true);
            $redisMock->shouldReceive('set')->with($statsKey, Mockery::type('string'))->andReturn(true);
            $this->assertTrue($cache->store($routes));

            // 測試 store with ttl = 0
            $cache->setTtl(0);
            $redisMock->shouldReceive('set')->with($cacheKey, $serialized)->once()->andReturn(true);
            $this->assertTrue($cache->store($routes));

            // 測試 store 拋出例外
            $redisMock->shouldReceive('set')->with($cacheKey, $serialized)->once()->andThrow(new RedisException('Store error'));
            $this->assertFalse($cache->store($routes));

            // 測試 load 成功
            $redisMock->shouldReceive('get')->with($cacheKey)->once()->andReturn($serialized);
            $loaded = $cache->load();
            $this->assertNotNull($loaded);

            // 測試 load 回傳 false (miss)
            $redisMock->shouldReceive('get')->with($cacheKey)->once()->andReturn(false);
            $this->assertNull($cache->load());

            // 測試 load 回傳無效內容
            $redisMock->shouldReceive('get')->with($cacheKey)->once()->andReturn(serialize('not_a_route_collection'));
            $this->assertNull($cache->load());

            // 測試 load 拋出例外
            $redisMock->shouldReceive('get')->with($cacheKey)->once()->andThrow(new RedisException('Get error'));
            $this->assertNull($cache->load());

            // 測試 clear
            $pipeMock = Mockery::mock(Redis::class);
            $pipeMock->shouldReceive('del')->with($cacheKey)->andReturnSelf();
            $pipeMock->shouldReceive('del')->with($statsKey)->andReturnSelf();
            $pipeMock->shouldReceive('exec')->andReturn([1, 1]);

            $redisMock->shouldReceive('multi')->once()->andReturn($pipeMock);
            $this->assertTrue($cache->clear());

            // 測試 clear 拋出例外
            $redisMock->shouldReceive('multi')->once()->andThrow(new RedisException('Multi error'));
            $this->assertFalse($cache->clear());

            $this->assertIsArray($cache->getStats());
        }

        /**
         * 測試 RouteCacheFactory.
         */
        public function testRouteCacheFactory(): void
        {
            $factory = new RouteCacheFactory();

            $this->assertEquals(['file', 'memory'], RouteCacheFactory::getSupportedDrivers());
            $this->assertTrue(RouteCacheFactory::isDriverSupported('file'));
            $this->assertTrue(RouteCacheFactory::isDriverSupported('memory'));
            $this->assertFalse(RouteCacheFactory::isDriverSupported('invalid'));
            $this->assertEquals(FileRouteCache::class, RouteCacheFactory::getDriverClass('file'));
            $this->assertNull(RouteCacheFactory::getDriverClass('unknown'));

            // 建立預設實例
            $defaultCache = $factory->createDefault();
            $this->assertInstanceOf(MemoryRouteCache::class, $defaultCache);

            // 建立記憶體快取
            $memCache = $factory->create(['driver' => 'memory', 'ttl' => 1200]);
            $this->assertInstanceOf(MemoryRouteCache::class, $memCache);
            $this->assertEquals(1200, $memCache->getTtl());

            // 建立檔案快取
            $fileCache = $factory->create(['driver' => 'file', 'path' => $this->tempCacheDir, 'ttl' => 2400]);
            $this->assertInstanceOf(FileRouteCache::class, $fileCache);
            $this->assertEquals(2400, $fileCache->getTtl());

            // 不支援的驅動拋出例外
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Unsupported cache driver: unsupported');
            $factory->create(['driver' => 'unsupported']);
        }

        /**
         * 測試 RouteCacheFactory::validateConfig.
         */
        public function testRouteCacheFactoryValidateConfig(): void
        {
            $factory = new RouteCacheFactory();

            // 正確配置
            $errors = $factory->validateConfig([
                'driver' => 'file',
                'path'   => $this->tempCacheDir . '/cache',
                'ttl'    => 3600,
            ]);
            $this->assertEmpty($errors);

            // 缺少 driver
            $errors = $factory->validateConfig([]);
            $this->assertContains('Cache driver is required', $errors);

            // 不支援的 driver
            $errors = $factory->validateConfig(['driver' => 'not_found']);
            $this->assertNotEmpty($errors);

            // 無效的 ttl
            $errors = $factory->validateConfig(['driver' => 'memory', 'ttl' => -5]);
            $this->assertContains('Cache TTL must be a non-negative integer', $errors);

            // 無效的 file path (空字串)
            $errors = $factory->validateConfig(['driver' => 'file', 'path' => '']);
            $this->assertContains('Cache path must be a non-empty string', $errors);

            // 不可寫入目錄
            $errors = $factory->validateConfig(['driver' => 'file', 'path' => '/nonexistent_system_dir_12345/sub/cache']);
            $this->assertNotEmpty($errors);
        }
    }
}
