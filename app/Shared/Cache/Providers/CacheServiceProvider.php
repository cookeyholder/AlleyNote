<?php

declare(strict_types=1);

namespace App\Shared\Cache\Providers;

use App\Shared\Cache\Contracts\CacheDriverInterface;
use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Contracts\CacheStrategyInterface;
use App\Shared\Cache\Drivers\FileCacheDriver;
use App\Shared\Cache\Drivers\MemoryCacheDriver;
use App\Shared\Cache\Drivers\RedisCacheDriver;
use App\Shared\Cache\Services\CacheManager;
use App\Shared\Cache\Services\DefaultCacheStrategy;
use App\Shared\Contracts\CacheServiceInterface;
use DI\Container;
use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;

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
        $this->config = array_merge($this->getDefaultConfig(), $config);
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
            return new DefaultCacheStrategy($this->config['strategy'] ?? []);
        });
    }

    /**
     * 註冊快取驅動。
     */
    private function registerDrivers(): void
    {
        // 記憶體快取驅動
        $this->container->set('cache.driver.memory', function (Container $container) {
            return new MemoryCacheDriver($this->config['drivers']['memory'] ?? []);
        });

        // 檔案快取驅動
        $this->container->set('cache.driver.file', function (Container $container) {
            $config = $this->config['drivers']['file'] ?? [];
            $config['path'] = $config['path'] ?? $this->getDefaultCachePath();
            return new FileCacheDriver($config);
        });

        // Redis 快取驅動
        $this->container->set('cache.driver.redis', function (Container $container) {
            if (!extension_loaded('redis')) {
                throw new \RuntimeException('Redis 擴充功能未安裝');
            }

            $config = $this->config['drivers']['redis'] ?? [];
            return new RedisCacheDriver($config);
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
            $logger = $container->has(LoggerInterface::class)
                ? $container->get(LoggerInterface::class)
                : null;

            $manager = new CacheManager($strategy, $logger, $this->config['manager'] ?? []);

            // 新增驅動
            $this->addDriversToManager($manager, $container);

            // 設定預設驅動
            $defaultDriver = $this->config['default_driver'] ?? 'memory';
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

        // 記憶體驅動
        if (isset($drivers['memory']) && ($drivers['memory']['enabled'] ?? true)) {
            $driver = $container->get('cache.driver.memory');
            $priority = $drivers['memory']['priority'] ?? 90;
            $manager->addDriver('memory', $driver, $priority);
        }

        // 檔案驅動
        if (isset($drivers['file']) && ($drivers['file']['enabled'] ?? true)) {
            $driver = $container->get('cache.driver.file');
            $priority = $drivers['file']['priority'] ?? 50;
            $manager->addDriver('file', $driver, $priority);
        }

        // Redis 驅動
        if (extension_loaded('redis') && isset($drivers['redis']) && ($drivers['redis']['enabled'] ?? false)) {
            try {
                $driver = $container->get('cache.driver.redis');
                $priority = $drivers['redis']['priority'] ?? 70;
                $manager->addDriver('redis', $driver, $priority);
            } catch (\Exception $e) {
                // Redis 連線失敗，記錄但不中斷啟動
                if ($container->has(LoggerInterface::class)) {
                    $logger = $container->get(LoggerInterface::class);
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
            CacheStrategyInterface::class => \DI\factory(function (\Psr\Container\ContainerInterface $c) {
                $config = $c->has('cache.strategy') ? $c->get('cache.strategy') : [];
                return new DefaultCacheStrategy($config);
            }),

            'cache.driver.memory' => \DI\factory(function (\Psr\Container\ContainerInterface $c) {
                $config = $c->has('cache.drivers.memory') ? $c->get('cache.drivers.memory') : [];
                return new MemoryCacheDriver($config);
            }),

            'cache.driver.file' => \DI\factory(function (\Psr\Container\ContainerInterface $c) {
                $config = $c->has('cache.drivers.file') ? $c->get('cache.drivers.file') : [];
                $config['path'] = $config['path'] ?? $c->get('cache.path');
                return new FileCacheDriver($config);
            }),

            'cache.driver.redis' => \DI\factory(function (\Psr\Container\ContainerInterface $c) {
                if (!extension_loaded('redis')) {
                    throw new \RuntimeException('Redis 擴充功能未安裝');
                }
                $config = $c->has('cache.drivers.redis') ? $c->get('cache.drivers.redis') : [];
                return new RedisCacheDriver($config);
            }),

            MemoryCacheDriver::class => \DI\get('cache.driver.memory'),
            FileCacheDriver::class => \DI\get('cache.driver.file'),
            RedisCacheDriver::class => \DI\get('cache.driver.redis'),

            CacheManagerInterface::class => \DI\factory(function (\Psr\Container\ContainerInterface $c) {
                $strategy = $c->get(CacheStrategyInterface::class);
                $logger = $c->has(LoggerInterface::class) ? $c->get(LoggerInterface::class) : null;
                $config = $c->has('cache.manager') ? $c->get('cache.manager') : [];

                $manager = new CacheManager($strategy, $logger, $config);

                // 新增記憶體驅動
                $memoryDriver = $c->get('cache.driver.memory');
                $memoryPriority = $c->has('cache.drivers.memory.priority') ? $c->get('cache.drivers.memory.priority') : 90;
                $manager->addDriver('memory', $memoryDriver, $memoryPriority);

                // 新增檔案驅動
                $fileDriver = $c->get('cache.driver.file');
                $filePriority = $c->has('cache.drivers.file.priority') ? $c->get('cache.drivers.file.priority') : 50;
                $manager->addDriver('file', $fileDriver, $filePriority);

                // 新增 Redis 驅動（如果可用）
                if (extension_loaded('redis')) {
                    try {
                        $redisDriver = $c->get('cache.driver.redis');
                        $redisPriority = $c->has('cache.drivers.redis.priority') ? $c->get('cache.drivers.redis.priority') : 70;
                        $manager->addDriver('redis', $redisDriver, $redisPriority);
                    } catch (\Exception $e) {
                        // Redis 不可用，忽略
                        if ($logger) {
                            $logger->warning('Redis 快取驅動不可用', ['error' => $e->getMessage()]);
                        }
                    }
                }

                // 設定預設驅動
                $defaultDriver = $c->has('cache.default_driver') ? $c->get('cache.default_driver') : 'memory';
                if ($manager->getDriver($defaultDriver)) {
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
        $this->config = array_merge($this->config, $config);
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
    public function fileDriver(string $path = null, array $config = []): self
    {
        $defaultConfig = [
            'enabled' => true,
            'priority' => 50,
            'ttl' => 3600,
        ];

        if ($path !== null) {
            $defaultConfig['path'] = $path;
        }

        $this->config['drivers']['file'] = array_merge($defaultConfig, $config);
        return $this;
    }

    /**
     * 設定 Redis 驅動。
     */
    public function redisDriver(array $config = []): self
    {
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
