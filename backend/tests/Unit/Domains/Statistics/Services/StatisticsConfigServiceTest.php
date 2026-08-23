<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Services;

use App\Domains\Statistics\Services\StatisticsConfigService;
use Tests\Support\UnitTestCase;

/**
 * 統計配置服務測試.
 */
final class StatisticsConfigServiceTest extends UnitTestCase
{
    private StatisticsConfigService $configService;

    protected function setUp(): void
    {
        parent::setUp();

        // 使用測試配置
        $testConfig = [
            'cache' => [
                'ttl' => [
                    'short'  => 300,
                    'medium' => 600,
                    'long'   => 1200,
                ],
                'types' => [
                    'overview' => 900,
                    'posts'    => 1800,
                ],
                'warmup' => [
                    'enabled'    => true,
                    'ttl'        => 3600,
                    'batch_size' => 50,
                ],
                'supported_tags' => ['test', 'statistics'],
            ],
            'calculation' => [
                'schedule' => [
                    'hourly' => '0 * * * *',
                    'daily'  => '0 2 * * *',
                ],
                'tasks' => [
                    'max_execution_time' => 1800,
                    'memory_limit'       => '256M',
                    'batch_size'         => 500,
                ],
                'parallel' => [
                    'enabled'      => false,
                    'max_workers'  => 2,
                    'lock_timeout' => 900,
                ],
            ],
            'performance' => [
                'api_limits' => [
                    'max_date_range' => 30,
                    'max_results'    => 100,
                    'default_limit'  => 10,
                ],
                'view_tracking' => [
                    'rate_limits' => [
                        'authenticated' => [
                            'requests' => 200,
                            'window'   => 60,
                        ],
                        'anonymous' => [
                            'requests' => 100,
                            'window'   => 60,
                        ],
                    ],
                    'response_timeout' => 50,
                ],
                'database' => [
                    'connection_timeout'   => 15,
                    'query_timeout'        => 45,
                    'slow_query_threshold' => 1.5,
                ],
            ],
            'retention' => [
                'snapshots' => [
                    'daily'  => 30,
                    'weekly' => 180,
                ],
                'cleanup' => [
                    'enabled'    => true,
                    'schedule'   => '0 3 * * *',
                    'batch_size' => 300,
                ],
            ],
            'features' => [
                'cache_enabled'          => true,
                'background_calculation' => false,
                'debug_mode'             => true,
            ],
            'monitoring' => [
                'health_check' => [
                    'enabled'             => true,
                    'cache_check_timeout' => 3,
                    'warning_thresholds'  => [
                        'cache_hit_rate' => 75,
                        'response_time'  => 1000,
                    ],
                ],
                'logging' => [
                    'level'     => 'debug',
                    'max_files' => 10,
                ],
            ],
            'environment' => [
                'testing' => [
                    'cache' => [
                        'ttl' => [
                            'short' => 60,
                        ],
                    ],
                    'features' => [
                        'cache_enabled' => false,
                    ],
                ],
            ],
        ];

        $this->configService = new StatisticsConfigService($testConfig, 'production');
    }

    public function testGetCacheTtl(): void
    {
        $this->assertSame(300, $this->configService->getCacheTtl('short'));
        $this->assertSame(600, $this->configService->getCacheTtl('medium'));
        $this->assertSame(1200, $this->configService->getCacheTtl('long'));

        // 測試預設值
        $this->assertSame(3600, $this->configService->getCacheTtl('nonexistent'));
    }

    public function testGetStatisticsTypeTtl(): void
    {
        $this->assertSame(900, $this->configService->getStatisticsTypeTtl('overview'));
        $this->assertSame(1800, $this->configService->getStatisticsTypeTtl('posts'));

        // 測試預設值（使用 medium TTL）
        $this->assertSame(600, $this->configService->getStatisticsTypeTtl('nonexistent'));
    }

    public function testGetCalculationSchedule(): void
    {
        $this->assertSame('0 * * * *', $this->configService->getCalculationSchedule('hourly'));
        $this->assertSame('0 2 * * *', $this->configService->getCalculationSchedule('daily'));

        // 測試預設值
        $this->assertSame('0 * * * *', $this->configService->getCalculationSchedule('nonexistent'));
    }

    public function testGetCalculationTaskConfig(): void
    {
        $config = $this->configService->getCalculationTaskConfig();

        $this->assertSame(1800, $config['max_execution_time']);
        $this->assertSame('256M', $config['memory_limit']);
        $this->assertSame(500, $config['batch_size']);
    }

    public function testGetParallelConfig(): void
    {
        $config = $this->configService->getParallelConfig();

        $this->assertFalse($config['enabled']);
        $this->assertSame(2, $config['max_workers']);
        $this->assertSame(900, $config['lock_timeout']);
    }

    public function testGetRetentionDays(): void
    {
        $this->assertSame(30, $this->configService->getRetentionDays('daily'));
        $this->assertSame(180, $this->configService->getRetentionDays('weekly'));

        // 測試預設值
        $this->assertSame(90, $this->configService->getRetentionDays('nonexistent'));
    }

    public function testGetApiLimits(): void
    {
        $limits = $this->configService->getApiLimits();

        $this->assertSame(30, $limits['max_date_range']);
        $this->assertSame(100, $limits['max_results']);
        $this->assertSame(10, $limits['default_limit']);
    }

    public function testGetViewTrackingRateLimit(): void
    {
        $authLimits = $this->configService->getViewTrackingRateLimit('authenticated');
        $this->assertSame(200, $authLimits['requests']);
        $this->assertSame(60, $authLimits['window']);

        $anonLimits = $this->configService->getViewTrackingRateLimit('anonymous');
        $this->assertSame(100, $anonLimits['requests']);
        $this->assertSame(60, $anonLimits['window']);

        // 測試預設值
        $defaultLimits = $this->configService->getViewTrackingRateLimit('nonexistent');
        $this->assertSame(120, $defaultLimits['requests']);
    }

    public function testGetResponseTimeout(): void
    {
        $this->assertSame(50, $this->configService->getResponseTimeout());
    }

    public function testGetHealthCheckConfig(): void
    {
        $config = $this->configService->getHealthCheckConfig();

        $this->assertTrue($config['enabled']);
        $this->assertSame(3, $config['cache_check_timeout']);

        /** @var array<string, mixed> $warningThresholds */
        $warningThresholds = $config['warning_thresholds'] ?? [];
        $this->assertSame(75, $warningThresholds['cache_hit_rate'] ?? 0);
        $this->assertSame(1000, $warningThresholds['response_time'] ?? 0);
    }

    public function testGetCacheWarmupConfig(): void
    {
        $config = $this->configService->getCacheWarmupConfig();

        $this->assertTrue($config['enabled']);
        $this->assertSame(3600, $config['ttl']);
        $this->assertSame(50, $config['batch_size']);
    }

    public function testGetSupportedCacheTags(): void
    {
        $tags = $this->configService->getSupportedCacheTags();

        $this->assertContains('test', $tags);
        $this->assertContains('statistics', $tags);
    }

    public function testIsFeatureEnabled(): void
    {
        $this->assertTrue($this->configService->isFeatureEnabled('cache_enabled'));
        $this->assertFalse($this->configService->isFeatureEnabled('background_calculation'));
        $this->assertTrue($this->configService->isFeatureEnabled('debug_mode'));

        // 測試不存在的功能
        $this->assertFalse($this->configService->isFeatureEnabled('nonexistent'));
    }

    public function testGetLoggingConfig(): void
    {
        $config = $this->configService->getLoggingConfig();

        $this->assertSame('debug', $config['level']);
        $this->assertSame(10, $config['max_files']);
    }

    public function testGetDatabaseConfig(): void
    {
        $config = $this->configService->getDatabaseConfig();

        $this->assertSame(15, $config['connection_timeout']);
        $this->assertSame(45, $config['query_timeout']);
        $this->assertSame(1.5, $config['slow_query_threshold']);
    }

    public function testGetCleanupConfig(): void
    {
        $config = $this->configService->getCleanupConfig();

        $this->assertTrue($config['enabled']);
        $this->assertSame('0 3 * * *', $config['schedule']);
        $this->assertSame(300, $config['batch_size']);
    }

    public function testGetEnvironment(): void
    {
        $this->assertSame('production', $this->configService->getEnvironment());
    }

    public function testEnvironmentOverrides(): void
    {
        // 使用測試環境
        /** @var array<string, mixed> $allConfig */
        $allConfig = $this->configService->getAllConfig();
        $configService = new StatisticsConfigService(
            $allConfig,
            'testing',
        );

        // 環境特定的快取 TTL
        $this->assertSame(60, $configService->getCacheTtl('short'));

        // 環境特定的功能開關
        $this->assertFalse($configService->isFeatureEnabled('cache_enabled'));

        // 未覆蓋的設定應使用預設值
        $this->assertSame(600, $configService->getCacheTtl('medium'));
    }

    public function testGetAllConfig(): void
    {
        $config = $this->configService->getAllConfig();

        $this->assertArrayHasKey('cache', $config);
        $this->assertArrayHasKey('calculation', $config);
        $this->assertArrayHasKey('performance', $config);
    }

    public function testDefaultConstructorLoadsFromFileAndDetectsEnvironment(): void
    {
        $_ENV['APP_ENV'] = 'testing';
        $service = new StatisticsConfigService();
        $this->assertSame('testing', $service->getEnvironment());
        $this->assertNotEmpty($service->getAllConfig());

        unset($_ENV['APP_ENV']);
        $_SERVER['APP_ENV'] = 'staging';
        $service2 = new StatisticsConfigService();
        $this->assertSame('staging', $service2->getEnvironment());
        unset($_SERVER['APP_ENV']);

        $_ENV['DEBUG'] = 'true';
        $service3 = new StatisticsConfigService();
        $this->assertSame('development', $service3->getEnvironment());
        unset($_ENV['DEBUG']);
    }

    public function testFallbackWhenConfigSectionsAreMissingOrEmpty(): void
    {
        $nonArrayConfigService = new StatisticsConfigService([
            'cache'       => false,
            'calculation' => false,
            'performance' => false,
            'retention'   => false,
            'monitoring'  => false,
            'environment' => false,
        ], 'unknown');

        $this->assertSame(3600, $nonArrayConfigService->getCacheTtl('medium'));
        $this->assertSame(3600, $nonArrayConfigService->getStatisticsTypeTtl('overview'));
        $this->assertSame('0 * * * *', $nonArrayConfigService->getCalculationSchedule('daily'));
        $this->assertSame([], $nonArrayConfigService->getCalculationTaskConfig());

        $parallel = $nonArrayConfigService->getParallelConfig();
        $this->assertFalse($parallel['enabled']);
        $this->assertSame(1, $parallel['max_workers']);

        $this->assertSame(90, $nonArrayConfigService->getRetentionDays('daily'));

        $apiLimits = $nonArrayConfigService->getApiLimits();
        $this->assertSame(90, $apiLimits['max_date_range']);

        $trackingLimit = $nonArrayConfigService->getViewTrackingRateLimit('anonymous');
        $this->assertSame(120, $trackingLimit['requests']);

        $this->assertSame(100, $nonArrayConfigService->getResponseTimeout());

        $health = $nonArrayConfigService->getHealthCheckConfig();
        $this->assertTrue($health['enabled']);

        $warmup = $nonArrayConfigService->getCacheWarmupConfig();
        $this->assertTrue($warmup['enabled']);
        $this->assertSame(7200, $warmup['ttl']);

        $tags = $nonArrayConfigService->getSupportedCacheTags();
        $this->assertContains('statistics', $tags);

        $this->assertFalse($nonArrayConfigService->isFeatureEnabled('any_feature'));

        $logging = $nonArrayConfigService->getLoggingConfig();
        $this->assertSame('info', $logging['level']);

        $db = $nonArrayConfigService->getDatabaseConfig();
        $this->assertSame(30, $db['connection_timeout']);

        $cleanup = $nonArrayConfigService->getCleanupConfig();
        $this->assertTrue($cleanup['enabled']);
    }

    public function testGetViewTrackingRateLimitWithInvalidViewTrackingConfig(): void
    {
        $service = new StatisticsConfigService([
            'performance' => ['view_tracking' => 'invalid'],
        ]);

        $this->assertSame(['requests' => 120, 'window' => 60], $service->getViewTrackingRateLimit('anonymous'));
    }

    public function testGetResponseTimeoutFallsBackToDefault(): void
    {
        $service = new StatisticsConfigService([
            'performance' => [],
        ]);

        $this->assertSame(100, $service->getResponseTimeout());
    }

    public function testGetSupportedCacheTagsFallsBackToDefaults(): void
    {
        $service = new StatisticsConfigService([
            'cache' => ['supported_tags' => 'invalid'],
        ]);

        $tags = $service->getSupportedCacheTags();
        $this->assertSame([
            'statistics',
            'overview',
            'posts',
            'users',
            'popular',
            'trends',
            'sources',
            'prewarmed',
        ], $tags);
    }

    /**
     * 檢測環境：無 APP_ENV 但有 TESTING 環境變數時回傳 testing.
     */
    public function testDetectEnvironmentFallsBackToTesting(): void
    {
        $originalEnv = $_ENV['APP_ENV'] ?? null;
        $originalServer = $_SERVER['APP_ENV'] ?? null;
        unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);
        $_ENV['TESTING'] = '1';

        try {
            $service = new StatisticsConfigService([]);
            $this->assertSame('testing', $service->getEnvironment());
        } finally {
            if ($originalEnv !== null) {
                $_ENV['APP_ENV'] = $originalEnv;
            }
            if ($originalServer !== null) {
                $_SERVER['APP_ENV'] = $originalServer;
            } else {
                unset($_ENV['TESTING']);
            }
        }
    }

    /**
     * 檢測環境：所有環境變數皆未設定時回傳 production.
     */
    public function testDetectEnvironmentFallsBackToProduction(): void
    {
        $backup = [$_ENV, $_SERVER];
        $savedEnvVars = [
            'APP_ENV' => [$_ENV['APP_ENV'] ?? null, $_SERVER['APP_ENV'] ?? null],
            'TESTING' => [$_ENV['TESTING'] ?? null, $_SERVER['TESTING'] ?? null],
            'DEBUG'   => [$_ENV['DEBUG'] ?? null, $_SERVER['DEBUG'] ?? null],
        ];
        foreach (['APP_ENV', 'TESTING', 'DEBUG'] as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
        }

        try {
            $service = new StatisticsConfigService([]);
            $this->assertSame('production', $service->getEnvironment());
        } finally {
            foreach ($savedEnvVars as $key => [$envValue, $serverValue]) {
                if ($envValue !== null) {
                    $_ENV[$key] = $envValue;
                }
                if ($serverValue !== null) {
                    $_SERVER[$key] = $serverValue;
                }
            }
            unset($backup);
        }
    }
}
