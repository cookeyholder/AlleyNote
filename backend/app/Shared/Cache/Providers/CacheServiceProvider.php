<?php

declare(strict_types=1);

namespace App\Shared\Cache\Providers;

use App\Shared\Cache\Contracts\CacheDriverInterface;
use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Contracts\CacheStrategyInterface;
use App\Shared\Cache\Contracts\TaggedCacheInterface;
use App\Shared\Cache\Contracts\TagRepositoryInterface;
use App\Shared\Cache\Drivers\FileCacheDriver;
use App\Shared\Cache\Drivers\MemoryCacheDriver;
use App\Shared\Cache\Drivers\RedisCacheDriver;
use App\Shared\Cache\Repositories\MemoryTagRepository;
use App\Shared\Cache\Repositories\RedisTagRepository;
use App\Shared\Cache\Services\CacheGroupManager;
use App\Shared\Cache\Services\CacheManager;
use App\Shared\Cache\Services\DefaultCacheStrategy;
use App\Shared\Contracts\CacheServiceInterface;
use App\Shared\Monitoring\Contracts\CacheMonitorInterface;
use DI\Container;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Redis;
use RuntimeException;

/**
 * 快取服務提供者。
 *
 * 負責註冊快取系統的所有組件到 DI 容器中
 */
class CacheServiceProvider
{
    /** @var Container DI 容器 */
    private Container $container;

    /** @var array<string, mixed> 設定 */
    private array $config;

    public function __construct(Container $container, array $config = [])
    {
        $this->container = $container;
        $defaultConfig = $this->getDefaultConfig();
        /** @var array<string, mixed> $mergedConfig */
        $mergedConfig = array_merge($defaultConfig, $config);
        $this->config = $mergedConfig;
    }

    /**
     * 註冊快取服務。
     */
    public function register(): void
    {
        $this->registerStrategy();
        $this->registerDrivers();
        $this->registerManager();
        $this->registerLegacyService();
    }

    /**
     * 註冊快取策略。
     */
    private function registerStrategy(): void
    {
        $this->container->set(CacheStrategyInterface::class, function (Container $container) {
            $strategyConfig = $this->config['strategy'] ?? [];
            assert(is_array($strategyConfig), 'Strategy config must be an array');

            return new DefaultCacheStrategy($strategyConfig);
        });
    }

    /**
     * 註冊快取驅動。
     */
    private function registerDrivers(): void
    {
        // 記憶體快取驅動
        $this->container->set('cache.driver.memory', function (Container $container) {
            $driversConfig = $this->config['drivers'] ?? [];
            if (!is_array($driversConfig)) {
                $driversConfig = [];
            }

            /** @var array<string, mixed> $memoryConfig */
            $memoryConfig = $driversConfig['memory'] ?? [];
            $maxItems = $memoryConfig['max_size'] ?? 1000;
            if (!is_int($maxItems)) {
                $maxItems = 1000;
            }

            return new MemoryCacheDriver($maxItems);
        });

        // 檔案快取驅動
        $this->container->set('cache.driver.file', function (Container $container) {
            $driversConfig = $this->config['drivers'] ?? [];
            if (!is_array($driversConfig)) {
                $driversConfig = [];
            }

            /** @var array<string, mixed> $fileConfig */
            $fileConfig = $driversConfig['file'] ?? [];
            $path = $fileConfig['path'] ?? $this->getDefaultCachePath();
            if (!is_string($path)) {
                $path = $this->getDefaultCachePath();
            }

            return new FileCacheDriver($path);
        });

        // Redis 快取驅動
        $this->container->set('cache.driver.redis', function (Container $container) {
            if (!extension_loaded('redis')) {
                throw new RuntimeException('Redis 擴充功能未安裝');
            }

            $driversConfig = $this->config['drivers'] ?? [];
            if (!is_array($driversConfig)) {
                $driversConfig = [];
            }

            /** @var array<string, mixed> $redisConfig */
            $redisConfig = $driversConfig['redis'] ?? [];

            return new RedisCacheDriver($redisConfig);
        });

        // 註冊驅動別名
        $this->container->set(MemoryCacheDriver::class, function (Container $container) {
            return $container->get('cache.driver.memory');
        });

        $this->container->set(FileCacheDriver::class, function (Container $container) {
            return $container->get('cache.driver.file');
        });

        if (extension_loaded('redis')) {
            $this->container->set(RedisCacheDriver::class, function (Container $container) {
                return $container->get('cache.driver.redis');
            });
        }
    }

    /**
     * 註冊快取管理器。
     */
    private function registerManager(): void
    {
        $this->container->set(CacheManagerInterface::class, function (Container $container) {
            $strategy = $container->get(CacheStrategyInterface::class);
            assert($strategy instanceof CacheStrategyInterface, 'Strategy must implement CacheStrategyInterface');

            $logger = $container->has(LoggerInterface::class)
                ? $container->get(LoggerInterface::class)
                : null;
            assert($logger instanceof LoggerInterface || $logger === null, 'Logger must implement LoggerInterface or be null');

            $managerConfig = $this->config['manager'] ?? [];
            assert(is_array($managerConfig), 'Manager config must be an array');

            $manager = new CacheManager($strategy, $logger, $managerConfig);

            // 新增驅動
            $this->addDriversToManager($manager, $container);

            // 設定預設驅動
            $defaultDriver = $this->config['default_driver'] ?? 'memory';
            assert(is_string($defaultDriver), 'Default driver must be a string');

            if ($manager->getDriver($defaultDriver)) {
                $manager->setDefaultDriver($defaultDriver);
            }

            return $manager;
        });

        // 註冊別名
        $this->container->set(CacheManager::class, function (Container $container) {
            return $container->get(CacheManagerInterface::class);
        });
    }

    /**
     * 註冊舊版快取服務（向後相容）。
     */
    private function registerLegacyService(): void
    {
        $this->container->set(CacheServiceInterface::class, function (Container $container) {
            // 使用檔案快取驅動作為舊版服務的後端
            return $container->get('cache.driver.file');
        });
    }

    /**
     * 新增驅動到管理器。
     */
    private function addDriversToManager(CacheManager $manager, Container $container): void
    {
        $drivers = $this->config['drivers'] ?? [];
        assert(is_array($drivers), 'Drivers config must be an array');

        // 記憶體驅動
        if (isset($drivers['memory']) && is_array($drivers['memory']) && ($drivers['memory']['enabled'] ?? true)) {
            $driver = $container->get('cache.driver.memory');
            assert($driver instanceof CacheDriverInterface, 'Memory driver must implement CacheDriverInterface');
            $priority = $drivers['memory']['priority'] ?? 90;
            assert(is_int($priority), 'Priority must be an integer');
            $manager->addDriver('memory', $driver, $priority);
        }

        // 檔案驅動
        if (isset($drivers['file']) && is_array($drivers['file']) && ($drivers['file']['enabled'] ?? true)) {
            $driver = $container->get('cache.driver.file');
            assert($driver instanceof CacheDriverInterface, 'File driver must implement CacheDriverInterface');
            $priority = $drivers['file']['priority'] ?? 50;
            assert(is_int($priority), 'Priority must be an integer');
            $manager->addDriver('file', $driver, $priority);
        }

        // Redis 驅動
        if (extension_loaded('redis') && isset($drivers['redis']) && is_array($drivers['redis']) && ($drivers['redis']['enabled'] ?? false)) {
            try {
                $driver = $container->get('cache.driver.redis');
                assert($driver instanceof CacheDriverInterface, 'Redis driver must implement CacheDriverInterface');
                $priority = $drivers['redis']['priority'] ?? 70;
                assert(is_int($priority), 'Priority must be an integer');
                $manager->addDriver('redis', $driver, $priority);
            } catch (Exception $e) {
                // Redis 連線失敗，記錄但不中斷啟動
                if ($container->has(LoggerInterface::class)) {
                    $logger = $container->get(LoggerInterface::class);
                    assert($logger instanceof LoggerInterface, 'Logger must implement LoggerInterface');
                    $logger->warning('Redis 快取驅動啟用失敗', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * 建立設定建構器。
     */
    public static function createConfigBuilder(): CacheConfigBuilder
    {
        return new CacheConfigBuilder();
    }

    /**
     * 取得 DI 容器定義。
     */
    public static function getDefinitions(): array
    {
        return [
            CacheStrategyInterface::class => \DI\factory(function (ContainerInterface $c) {
                /** @var array<string, mixed> $config */
                $config = $c->has('cache.strategy') ? $c->get('cache.strategy') : [];

                return new DefaultCacheStrategy($config);
            }),

            'cache.driver.memory' => \DI\factory(function (ContainerInterface $c) {
                $config = $c->has('cache.drivers.memory') ? $c->get('cache.drivers.memory') : [];
                if (!is_array($config)) {
                    $config = [];
                }
                $maxItems = isset($config['max_size']) && is_int($config['max_size'])
                    ? $config['max_size']
                    : 1000;

                return new MemoryCacheDriver($maxItems);
            }),

            'cache.driver.file' => \DI\factory(function (ContainerInterface $c) {
                $config = $c->has('cache.drivers.file') ? $c->get('cache.drivers.file') : [];
                if (!is_array($config)) {
                    $config = [];
                }
                $defaultPath = $c->has('cache.path') ? $c->get('cache.path') : '/tmp/cache';
                $path = isset($config['path']) && is_string($config['path'])
                    ? $config['path']
                    : (is_string($defaultPath) ? $defaultPath : '/tmp/cache');

                return new FileCacheDriver($path);
            }),

            'cache.driver.redis' => \DI\factory(function (ContainerInterface $c) {
                if (!extension_loaded('redis')) {
                    throw new RuntimeException('Redis 擴充功能未安裝');
                }
                $config = $c->has('cache.drivers.redis') ? $c->get('cache.drivers.redis') : [];
                if (!is_array($config)) {
                    $config = [];
                }

                return new RedisCacheDriver($config);
            }),

            MemoryCacheDriver::class => \DI\get('cache.driver.memory'),
            FileCacheDriver::class => \DI\get('cache.driver.file'),
            RedisCacheDriver::class => \DI\get('cache.driver.redis'),

            // 標籤倉庫
            'cache.tag.repository.memory' => \DI\factory(function (ContainerInterface $c) {
                return new MemoryTagRepository();
            }),

            'cache.tag.repository.redis' => \DI\factory(function (ContainerInterface $c) {
                if (!extension_loaded('redis')) {
                    throw new RuntimeException('Redis 擴充功能未安裝');
                }
                $redis = null;
                if ($c->has('redis')) {
                    $redis = $c->get('redis');
                }
                if (!($redis instanceof Redis)) {
                    throw new RuntimeException('Redis 實例不可用');
                }

                return new RedisTagRepository($redis);
            }),

            TagRepositoryInterface::class => \DI\factory(function (ContainerInterface $c) {
                // 根據是否有 Redis 來選擇標籤倉庫
                if (extension_loaded('redis')) {
                    try {
                        return $c->get('cache.tag.repository.redis');
                    } catch (Exception) {
                        return $c->get('cache.tag.repository.memory');
                    }
                }

                return $c->get('cache.tag.repository.memory');
            }),

            // 快取分組管理器
            CacheGroupManager::class => \DI\factory(function (ContainerInterface $c) {
                $taggedCache = $c->get(CacheManagerInterface::class);
                // 型別檢查
                if (!is_object($taggedCache) || !method_exists($taggedCache, 'tags')) {
                    throw new RuntimeException('快取管理器不支援標籤功能');
                }
                $tagsResult = $taggedCache->tags([]);
                if (!($tagsResult instanceof TaggedCacheInterface)) {
                    throw new RuntimeException('tags() 必須回傳 TaggedCacheInterface');
                }
                $logger = $c->has(LoggerInterface::class) ? $c->get(LoggerInterface::class) : null;
                if (!($logger instanceof LoggerInterface)) {
                    $logger = new NullLogger();
                }

                return new CacheGroupManager($tagsResult, $logger);
            }),

            CacheManagerInterface::class => \DI\factory(function (ContainerInterface $c) {
                $strategy = $c->get(CacheStrategyInterface::class);
                if (!($strategy instanceof CacheStrategyInterface)) {
                    throw new RuntimeException('快取策略型別錯誤');
                }
                $logger = $c->has(LoggerInterface::class) ? $c->get(LoggerInterface::class) : null;
                if ($logger !== null && !($logger instanceof LoggerInterface)) {
                    $logger = new NullLogger();
                }
                $config = $c->has('cache.manager') ? $c->get('cache.manager') : [];
                if (!is_array($config)) {
                    $config = [];
                }
                $tagRepository = null;

                try {
                    $tagRepositoryTmp = $c->get(TagRepositoryInterface::class);
                    if ($tagRepositoryTmp instanceof TagRepositoryInterface) {
                        $tagRepository = $tagRepositoryTmp;
                    }
                } catch (Exception) {
                }
                $monitor = null;

                try {
                    $monitorTmp = $c->get(CacheMonitorInterface::class);
                    if ($monitorTmp instanceof CacheMonitorInterface) {
                        $monitor = $monitorTmp;
                    }
                } catch (Exception) {
                }
                $manager = new CacheManager($strategy, $logger, $config, $monitor, $tagRepository);
                // 新增記憶體驅動
                $memoryDriver = $c->get('cache.driver.memory');
                if ($memoryDriver instanceof CacheDriverInterface) {
                    $memoryPriority = $c->has('cache.drivers.memory.priority') ? $c->get('cache.drivers.memory.priority') : 90;
                    $manager->addDriver('memory', $memoryDriver, is_int($memoryPriority) ? $memoryPriority : 90);
                }
                // 新增檔案驅動
                $fileDriver = $c->get('cache.driver.file');
                if ($fileDriver instanceof CacheDriverInterface) {
                    $filePriority = $c->has('cache.drivers.file.priority') ? $c->get('cache.drivers.file.priority') : 50;
                    $manager->addDriver('file', $fileDriver, is_int($filePriority) ? $filePriority : 50);
                }
                // 新增 Redis 驅動（如果可用）
                if (extension_loaded('redis')) {
                    try {
                        $redisDriver = $c->get('cache.driver.redis');
                        if ($redisDriver instanceof CacheDriverInterface) {
                            $redisPriority = $c->has('cache.drivers.redis.priority') ? $c->get('cache.drivers.redis.priority') : 70;
                            $manager->addDriver('redis', $redisDriver, is_int($redisPriority) ? $redisPriority : 70);
                        }
                    } catch (Exception $e) {
                        if ($logger instanceof LoggerInterface) {
                            $logger->warning('Redis 快取驅動不可用', ['error' => $e->getMessage()]);
                        }
                    }
                }
                // 設定預設驅動
                $defaultDriver = $c->has('cache.default_driver') ? $c->get('cache.default_driver') : 'memory';
                if (is_string($defaultDriver) && $manager->getDriver($defaultDriver)) {
                    $manager->setDefaultDriver($defaultDriver);
                }

                return $manager;
            }),

            CacheManager::class => \DI\get(CacheManagerInterface::class),

            // 向後相容：舊版快取服務介面
            CacheServiceInterface::class => \DI\get('cache.driver.file'),
        ];
    }

    /**
     * 取得預設設定。
     */
    private function getDefaultConfig(): array
    {
        return [
            'default_driver' => 'memory',
            'drivers' => [
                'memory' => [
                    'enabled' => true,
                    'priority' => 90,
                    'max_size' => 1000,
                    'ttl' => 3600,
                ],
                'file' => [
                    'enabled' => true,
                    'priority' => 50,
                    'path' => $this->getDefaultCachePath(),
                    'ttl' => 3600,
                ],
                'redis' => [
                    'enabled' => false,
                    'priority' => 70,
                    'host' => '127.0.0.1',
                    'port' => 6379,
                    'database' => 0,
                    'timeout' => 2.0,
                    'prefix' => 'alleynote:cache:',
                ],
            ],
            'strategy' => [
                'min_ttl' => 60,
                'max_ttl' => 86400,
                'max_value_size' => 1024 * 1024,
                'exclude_patterns' => ['temp:*', 'debug:*'],
            ],
            'manager' => [
                'enable_sync' => false,
                'sync_ttl' => 3600,
                'max_retry_attempts' => 3,
                'retry_delay' => 100,
            ],
        ];
    }

    /**
     * 取得預設快取路徑。
     */
    private function getDefaultCachePath(): string
    {
        return dirname(__DIR__, 4) . '/storage/cache';
    }

    /**
     * 取得設定。
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * 更新設定。
     */
    public function updateConfig(array $config): void
    {
        /** @var array<string, mixed> $mergedConfig */
        $mergedConfig = array_merge($this->config, $config);
        $this->config = $mergedConfig;
    }
}

/**
 * 快取設定建構器。
 *
 * 提供流暢的介面來建構快取設定
 */
class CacheConfigBuilder
{
    /** @var array<string, mixed> 設定 */
    private array $config = [];

    /**
     * 設定預設驅動。
     */
    public function defaultDriver(string $driver): self
    {
        $this->config['default_driver'] = $driver;

        return $this;
    }

    /**
     * 設定記憶體驅動。
     */
    public function memoryDriver(array $config = []): self
    {
        if (!isset($this->config['drivers']) || !is_array($this->config['drivers'])) {
            $this->config['drivers'] = [];
        }
        $this->config['drivers']['memory'] = array_merge([
            'enabled' => true,
            'priority' => 90,
            'max_size' => 1000,
            'ttl' => 3600,
        ], $config);

        return $this;
    }

    /**
     * 設定檔案驅動。
     */
    public function fileDriver(?string $path = null, array $config = []): self
    {
        $defaultConfig = [
            'enabled' => true,
            'priority' => 50,
            'ttl' => 3600,
        ];

        if ($path !== null) {
            $defaultConfig['path'] = $path;
        }

        if (!isset($this->config['drivers']) || !is_array($this->config['drivers'])) {
            $this->config['drivers'] = [];
        }
        $this->config['drivers']['file'] = array_merge($defaultConfig, $config);

        return $this;
    }

    /**
     * 設定 Redis 驅動。
     */
    public function redisDriver(array $config = []): self
    {
        if (!isset($this->config['drivers']) || !is_array($this->config['drivers'])) {
            $this->config['drivers'] = [];
        }
        $this->config['drivers']['redis'] = array_merge([
            'enabled' => true,
            'priority' => 70,
            'host' => '127.0.0.1',
            'port' => 6379,
            'database' => 0,
            'timeout' => 2.0,
            'prefix' => 'alleynote:cache:',
        ], $config);

        return $this;
    }

    /**
     * 設定快取策略。
     */
    public function strategy(array $config): self
    {
        $this->config['strategy'] = array_merge([
            'min_ttl' => 60,
            'max_ttl' => 86400,
            'max_value_size' => 1024 * 1024,
            'exclude_patterns' => ['temp:*', 'debug:*'],
        ], $config);

        return $this;
    }

    /**
     * 設定管理器。
     */
    public function manager(array $config): self
    {
        $this->config['manager'] = array_merge([
            'enable_sync' => false,
            'sync_ttl' => 3600,
            'max_retry_attempts' => 3,
            'retry_delay' => 100,
        ], $config);

        return $this;
    }

    /**
     * 建構設定。
     */
    public function build(): array
    {
        return $this->config;
    }
}
