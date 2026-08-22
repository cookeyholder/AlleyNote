<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Cache\Providers;

use App\Shared\Cache\Contracts\CacheDriverInterface;
use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Contracts\CacheStrategyInterface;
use App\Shared\Cache\Contracts\TagRepositoryInterface;
use App\Shared\Cache\Drivers\FileCacheDriver;
use App\Shared\Cache\Drivers\MemoryCacheDriver;
use App\Shared\Cache\Providers\CacheConfigBuilder;
use App\Shared\Cache\Providers\CacheServiceProvider;
use App\Shared\Cache\Repositories\MemoryTagRepository;
use App\Shared\Cache\Services\CacheGroupManager;
use App\Shared\Cache\Services\CacheManager;
use App\Shared\Cache\Services\DefaultCacheStrategy;
use App\Shared\Contracts\CacheServiceInterface;
use App\Shared\Enums\CacheType;
use DI\Container;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * CacheServiceProvider 單元測試.
 *
 * 驗證服務註冊、DI 定義解析與設定建構器的行為。
 * 容器未安裝 redis 擴充功能，相關路徑應回報錯誤或優雅降級。
 */
final class CacheServiceProviderTest extends UnitTestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/alleynote_cache_provider_' . uniqid();
    }

    protected function tearDown(): void
    {
        // 清理檔案快取產生的目錄與檔案
        foreach (glob($this->tempDir . '/*') ?: [] as $item) {
            if (is_file($item)) {
                unlink($item);
            }
        }
        if (is_dir($this->tempDir)) {
            @rmdir($this->tempDir . '/files');
            @rmdir($this->tempDir);
        }
        parent::tearDown();
    }

    private function createProvider(Container $container, array $config = []): CacheServiceProvider
    {
        // 建構子採淺層合併，自訂 drivers 時須自行補齊完整驅動設定
        /** @var mixed $rawDrivers */
        $rawDrivers = $config['drivers'] ?? [];
        $drivers = is_array($rawDrivers) ? $drivers = $rawDrivers : [];

        /** @var mixed $rawMemoryConfig */
        $rawMemoryConfig = $drivers[CacheType::MEMORY->value] ?? [];
        $memoryConfig = is_array($rawMemoryConfig) ? $rawMemoryConfig : [];
        $memoryConfig['enabled'] ??= true;
        $drivers[CacheType::MEMORY->value] = $memoryConfig;

        /** @var mixed $rawFileConfig */
        $rawFileConfig = $drivers[CacheType::FILE->value] ?? [];
        $fileConfig = is_array($rawFileConfig) ? $rawFileConfig : [];
        $fileConfig['enabled'] ??= true;
        $fileConfig['path'] ??= $this->tempDir;
        $drivers[CacheType::FILE->value] = $fileConfig;

        return new CacheServiceProvider($container, ['drivers' => $drivers] + $config);
    }

    #[Test]
    public function registerResolvesCoreCacheServices(): void
    {
        $container = new Container();
        $provider = $this->createProvider($container);
        $provider->register();

        $strategy = $container->get(CacheStrategyInterface::class);
        $this->assertInstanceOf(DefaultCacheStrategy::class, $strategy);

        $memoryDriver = $container->get('cache.driver.' . CacheType::MEMORY->value);
        $this->assertInstanceOf(MemoryCacheDriver::class, $memoryDriver);
        $memoryAlias = $container->get(MemoryCacheDriver::class);
        $this->assertInstanceOf(MemoryCacheDriver::class, $memoryAlias);

        $fileDriver = $container->get('cache.driver.' . CacheType::FILE->value);
        $this->assertInstanceOf(FileCacheDriver::class, $fileDriver);

        $manager = $container->get(CacheManagerInterface::class);
        $this->assertInstanceOf(CacheManager::class, $manager);
        $this->assertNotNull($manager->getDriver('memory'));
        $this->assertNotNull($manager->getDriver('file'));
        $this->assertSame('memory', $manager->getDefaultDriver());

        // 舊版服務介面以檔案驅動作為後端
        $legacyService = $container->get(CacheServiceInterface::class);
        $this->assertInstanceOf(FileCacheDriver::class, $legacyService);
    }

    #[Test]
    public function registerRespectsCustomConfiguration(): void
    {
        $container = new Container();
        $provider = $this->createProvider($container, [
            'default_driver' => CacheType::FILE->value,
            'drivers'        => [
                CacheType::MEMORY->value => ['enabled' => false],
                CacheType::FILE->value   => ['enabled' => true, 'priority' => 80],
            ],
        ]);
        $provider->register();

        $manager = $container->get(CacheManagerInterface::class);
        $this->assertInstanceOf(CacheManager::class, $manager);
        $this->assertNull($manager->getDriver('memory'), '停用的記憶體驅動不應註冊');
        $this->assertSame('file', $manager->getDefaultDriver());

        // 設定存取與更新
        $currentConfig = $provider->getConfig();
        /** @var mixed $fileSettings */
        $fileSettings = is_array($currentConfig['drivers'] ?? null) ? ($currentConfig['drivers']['file'] ?? null) : null;
        $this->assertIsArray($fileSettings);
        $this->assertSame($this->tempDir, $fileSettings['path']);
        $provider->updateConfig(['custom_key' => 'custom_value']);
        $this->assertSame('custom_value', $provider->getConfig()['custom_key']);
    }

    #[Test]
    public function redisDriverResolutionFailsWithoutExtension(): void
    {
        if (extension_loaded('redis')) {
            $this->markTestSkipped('容器已安裝 redis 擴充功能，無法測試缺少擴充的路徑');
        }

        $container = new Container();
        $provider = $this->createProvider($container, [
            'drivers' => [
                CacheType::REDIS->value => [
                    'enabled' => true,
                    'host'    => 'redis',
                    'port'    => 6379,
                ],
            ],
        ]);
        $provider->register();

        try {
            $container->get('cache.driver.' . CacheType::REDIS->value);
            $this->fail('缺少 redis 擴充時應拋出 RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertSame('Redis 擴充功能未安裝', $e->getMessage());
        }

        // 管理器仍可正常建立（Redis 驅動被略過）
        $manager = $container->get(CacheManagerInterface::class);
        $this->assertInstanceOf(CacheManager::class, $manager);
        $this->assertNull($manager->getDriver('redis'));

        // 標籤倉庫介面應降級為記憶體實作（透過 getDefinitions 註冊）
        $definitionsContainer = new Container(CacheServiceProvider::getDefinitions());
        $tagRepository = $definitionsContainer->get(TagRepositoryInterface::class);
        $this->assertInstanceOf(MemoryTagRepository::class, $tagRepository);
    }

    #[Test]
    public function getDefinitionsResolveStandaloneServices(): void
    {
        $definitions = CacheServiceProvider::getDefinitions();
        $this->assertArrayHasKey(CacheStrategyInterface::class, $definitions);
        $this->assertArrayHasKey(CacheManagerInterface::class, $definitions);
        $this->assertArrayHasKey(CacheGroupManager::class, $definitions);

        $container = new Container($definitions);
        // 提供檔案快取路徑設定
        $container->set('cache.path', $this->tempDir);

        $groupManager = $container->get(CacheGroupManager::class);
        $this->assertInstanceOf(CacheGroupManager::class, $groupManager);

        $memoryTagRepo = $container->get('cache.tag.repository.memory');
        $this->assertInstanceOf(MemoryTagRepository::class, $memoryTagRepo);

        $fileDriver = $container->get('cache.driver.' . CacheType::FILE->value);
        $this->assertInstanceOf(CacheDriverInterface::class, $fileDriver);

        $strategyFromDefinitions = $container->get(CacheStrategyInterface::class);
        $this->assertInstanceOf(DefaultCacheStrategy::class, $strategyFromDefinitions);
    }

    #[Test]
    public function configBuilderProducesCompleteConfiguration(): void
    {
        $builder = CacheServiceProvider::createConfigBuilder();
        $this->assertInstanceOf(CacheConfigBuilder::class, $builder);

        $builder
            ->defaultDriver(CacheType::REDIS)
            ->memoryDriver(['max_size' => 500])
            ->fileDriver('/tmp/custom-cache', ['ttl' => 7200])
            ->fileDriver(null) // 再次呼叫會以預設值整體覆寫檔案驅動設定
            ->redisDriver(['host' => 'redis-server'])
            ->strategy(['min_ttl' => 30])
            ->manager(['enable_sync' => true]);

        $builtConfig = $builder->build();
        $this->assertSame('redis', $builtConfig['default_driver']);

        /** @var array<string, array<string, mixed>> $builtDrivers */
        $builtDrivers = is_array($builtConfig['drivers'] ?? null) ? $builtConfig['drivers'] : [];
        $this->assertSame(500, $builtDrivers['memory']['max_size'] ?? null);

        /** @var array<string, mixed> $builtFileConfig */
        $builtFileConfig = is_array($builtDrivers['file'] ?? null) ? $builtDrivers['file'] : [];
        $this->assertArrayNotHasKey('path', $builtFileConfig, '無路徑呼叫不應殘留先前路徑');
        $this->assertSame(3600, $builtFileConfig['ttl'], '無路徑呼叫重設為預設 ttl');

        /** @var array<string, mixed> $builtRedisConfig */
        $builtRedisConfig = is_array($builtDrivers['redis'] ?? null) ? $builtDrivers['redis'] : [];
        $this->assertSame('redis-server', $builtRedisConfig['host']);

        /** @var array<string, mixed> $builtStrategyConfig */
        $builtStrategyConfig = is_array($builtConfig['strategy'] ?? null) ? $builtConfig['strategy'] : [];
        $this->assertSame(30, $builtStrategyConfig['min_ttl']);

        /** @var array<string, mixed> $builtManagerConfig */
        $builtManagerConfig = is_array($builtConfig['manager'] ?? null) ? $builtConfig['manager'] : [];
        $this->assertTrue((bool) ($builtManagerConfig['enable_sync'] ?? false));
    }
}
