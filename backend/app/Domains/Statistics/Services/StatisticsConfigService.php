<?php

declare(strict_types=1);

// phpcs:disable Generic.Files.LineLength.TooLong
/* @phpstan-ignore-next-line */

namespace App\Domains\Statistics\Services;

use RuntimeException;

/**
 * 統計配置服務.
 *
 * 統一管理統計功能的所有配置參數，提供型別安全的配置存取
 */
final class StatisticsConfigService
{
    /** @var array<string, mixed> */
    private array $config;

    private string $environment;

    /**
     * @param array<string, mixed>|null $config
     */
    public function __construct(?array $config = null, ?string $environment = null)
    {
        $this->config = $config ?? $this->loadConfig();
        $this->environment = $environment ?? $this->detectEnvironment();
    }

    /**
     * 取得快取 TTL 設定.
     */
    public function getCacheTtl(string $type = 'medium'): int
    {
        $envConfig = $this->getEnvironmentConfig('cache.ttl.' . $type);
        if ($envConfig !== null && is_int($envConfig)) {
            return $envConfig;
        }

        $defaultConfig = $this->config['cache']['ttl'] ?? [];
        if (is_array($defaultConfig) && isset($defaultConfig[$type])) {
            return (int) $defaultConfig[$type];
        }

        return 3600;
    }

    /**
     * 取得特定統計類型的 TTL.
     */
    public function getStatisticsTypeTtl(string $statisticsType): int
    {
        $cacheConfig = $this->config['cache'] ?? [];
        if (!is_array($cacheConfig)) {
            return $this->getCacheTtl('medium');
        }

        $typesConfig = $cacheConfig['types'] ?? [];
        if (is_array($typesConfig) && isset($typesConfig[$statisticsType])) {
            return (int) $typesConfig[$statisticsType];
        }

        return $this->getCacheTtl('medium');
    }

    /**
     * 取得計算排程設定.
     */
    public function getCalculationSchedule(string $frequency): string
    {
        $calcConfig = $this->config['calculation'] ?? [];
        if (!is_array($calcConfig)) {
            return '0 * * * *';
        }

        $scheduleConfig = $calcConfig['schedule'] ?? [];
        if (is_array($scheduleConfig) && isset($scheduleConfig[$frequency])) {
            return (string) $scheduleConfig[$frequency];
        }

        return '0 * * * *';
    }

    /**
     * 取得計算任務配置.
     *
     * @return array<string, mixed>
     */
    public function getCalculationTaskConfig(): array
    {
        $calcConfig = $this->config['calculation'] ?? [];
        if (!is_array($calcConfig)) {
            return [];
        }

        $tasksConfig = $calcConfig['tasks'] ?? [];

        return is_array($tasksConfig) ? $tasksConfig : [];
    }

    /**
     * 取得並行處理配置.
     *
     * @return array<string, mixed>
     */
    public function getParallelConfig(): array
    {
        $calcConfig = $this->config['calculation'] ?? [];
        if (!is_array($calcConfig)) {
            return [
                'enabled' => false,
                'max_workers' => 1,
                'lock_timeout' => 1800,
            ];
        }

        $parallelConfig = $calcConfig['parallel'] ?? [];

        return is_array($parallelConfig) ? $parallelConfig : [
            'enabled' => false,
            'max_workers' => 1,
            'lock_timeout' => 1800,
        ];
    }

    /**
     * 取得資料保存期限.
     */
    public function getRetentionDays(string $snapshotType): int
    {
        $retentionConfig = $this->config['retention'] ?? [];
        if (!is_array($retentionConfig)) {
            return 90;
        }

        $snapshotsConfig = $retentionConfig['snapshots'] ?? [];
        if (is_array($snapshotsConfig) && isset($snapshotsConfig[$snapshotType])) {
            return (int) $snapshotsConfig[$snapshotType];
        }

        return 90;
    }

    /**
     * 取得 API 限制配置.
     *
     * @return array<string, mixed>
     */
    public function getApiLimits(): array
    {
        $perfConfig = $this->config['performance'] ?? [];
        if (!is_array($perfConfig)) {
            return [
                'max_date_range' => 90,
                'max_results' => 1000,
                'default_limit' => 20,
            ];
        }

        $apiLimitsConfig = $perfConfig['api_limits'] ?? [];

        return is_array($apiLimitsConfig) ? $apiLimitsConfig : [
            'max_date_range' => 90,
            'max_results' => 1000,
            'default_limit' => 20,
        ];
    }

    /**
     * 取得瀏覽追蹤限流配置.
     *
     * @return array<string, mixed>
     */
    public function getViewTrackingRateLimit(string $userType): array
    {
        $perfConfig = $this->config['performance'] ?? [];
        if (!is_array($perfConfig)) {
            return ['requests' => 120, 'window' => 60];
        }

        $viewTrackingConfig = $perfConfig['view_tracking'] ?? [];
        if (!is_array($viewTrackingConfig)) {
            return ['requests' => 120, 'window' => 60];
        }

        $rateLimitsConfig = $viewTrackingConfig['rate_limits'] ?? [];
        if (is_array($rateLimitsConfig) && isset($rateLimitsConfig[$userType])) {
            $userLimit = $rateLimitsConfig[$userType];

            return is_array($userLimit) ? $userLimit : ['requests' => 120, 'window' => 60];
        }

        return ['requests' => 120, 'window' => 60];
    }

    /**
     * 取得回應超時限制.
     */
    public function getResponseTimeout(): int
    {
        $perfConfig = $this->config['performance'] ?? [];
        if (!is_array($perfConfig)) {
            return 100;
        }

        $viewTrackingConfig = $perfConfig['view_tracking'] ?? [];
        if (is_array($viewTrackingConfig) && isset($viewTrackingConfig['response_timeout'])) {
            return (int) $viewTrackingConfig['response_timeout'];
        }

        return 100;
    }

    /**
     * 取得健康檢查配置.
     *
     * @return array<string, mixed>
     */
    public function getHealthCheckConfig(): array
    {
        $monitoringConfig = $this->config['monitoring'] ?? [];
        if (!is_array($monitoringConfig)) {
            return [
                'enabled' => true,
                'cache_check_timeout' => 5,
                'database_check_timeout' => 10,
                'warning_thresholds' => [
                    'cache_hit_rate' => 80,
                    'response_time' => 2000,
                    'error_rate' => 5,
                ],
            ];
        }

        $healthCheckConfig = $monitoringConfig['health_check'] ?? [];

        return is_array($healthCheckConfig) ? $healthCheckConfig : [
            'enabled' => true,
            'cache_check_timeout' => 5,
            'database_check_timeout' => 10,
            'warning_thresholds' => [
                'cache_hit_rate' => 80,
                'response_time' => 2000,
                'error_rate' => 5,
            ],
        ];
    }

    /**
     * 取得快取預熱配置.
     *
     * @return array<string, mixed>
     */
    public function getCacheWarmupConfig(): array
    {
        $cacheConfig = $this->config['cache'] ?? [];
        if (!is_array($cacheConfig)) {
            return [
                'enabled' => true,
                'ttl' => 7200,
                'batch_size' => 100,
            ];
        }

        $warmupConfig = $cacheConfig['warmup'] ?? [];

        return is_array($warmupConfig) ? $warmupConfig : [
            'enabled' => true,
            'ttl' => 7200,
            'batch_size' => 100,
        ];
    }

    /**
     * 取得支援的快取標籤.
     *
     * @return array<string>
     */
    public function getSupportedCacheTags(): array
    {
        $cacheConfig = $this->config['cache'] ?? [];
        if (!is_array($cacheConfig)) {
            return [
                'statistics',
                'overview',
                'posts',
                'users',
                'popular',
                'trends',
                'sources',
                'prewarmed',
            ];
        }

        $tagsConfig = $cacheConfig['supported_tags'] ?? [];
        if (is_array($tagsConfig)) {
            return array_map('strval', $tagsConfig);
        }

        return [
            'statistics',
            'overview',
            'posts',
            'users',
            'popular',
            'trends',
            'sources',
            'prewarmed',
        ];
    }

    /**
     * 檢查功能是否啟用.
     */
    public function isFeatureEnabled(string $feature): bool
    {
        $envConfig = $this->getEnvironmentConfig('features.' . $feature);
        if ($envConfig !== null && is_bool($envConfig)) {
            return $envConfig;
        }

        $featuresConfig = $this->config['features'] ?? [];
        if (is_array($featuresConfig) && isset($featuresConfig[$feature])) {
            return (bool) $featuresConfig[$feature];
        }

        return false;
    }

    /**
     * 取得日誌配置.
     *
     * @return array<string, mixed>
     */
    public function getLoggingConfig(): array
    {
        $monitoringConfig = $this->config['monitoring'] ?? [];
        if (!is_array($monitoringConfig)) {
            return [
                'level' => 'info',
                'max_files' => 30,
                'max_file_size' => '10M',
                'rotation' => 'daily',
            ];
        }

        $loggingConfig = $monitoringConfig['logging'] ?? [];

        return is_array($loggingConfig) ? $loggingConfig : [
            'level' => 'info',
            'max_files' => 30,
            'max_file_size' => '10M',
            'rotation' => 'daily',
        ];
    }

    /**
     * 取得資料庫配置.
     *
     * @return array<string, mixed>
     */
    public function getDatabaseConfig(): array
    {
        $perfConfig = $this->config['performance'] ?? [];
        if (!is_array($perfConfig)) {
            return [
                'connection_timeout' => 30,
                'query_timeout' => 60,
                'slow_query_threshold' => 2.0,
            ];
        }

        $dbConfig = $perfConfig['database'] ?? [];

        return is_array($dbConfig) ? $dbConfig : [
            'connection_timeout' => 30,
            'query_timeout' => 60,
            'slow_query_threshold' => 2.0,
        ];
    }

    /**
     * 取得清理配置.
     *
     * @return array<string, mixed>
     */
    public function getCleanupConfig(): array
    {
        $retentionConfig = $this->config['retention'] ?? [];
        if (!is_array($retentionConfig)) {
            return [
                'enabled' => true,
                'schedule' => '0 5 * * *',
                'batch_size' => 500,
            ];
        }

        $cleanupConfig = $retentionConfig['cleanup'] ?? [];

        return is_array($cleanupConfig) ? $cleanupConfig : [
            'enabled' => true,
            'schedule' => '0 5 * * *',
            'batch_size' => 500,
        ];
    }

    /**
     * 取得所有配置（用於除錯）.
     */
    public function getAllConfig(): array
    {
        return $this->config;
    }

    /**
     * 取得當前環境名稱.
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * 載入配置檔案.
     */
    private function loadConfig(): array
    {
        $configPath = __DIR__ . '/../../../../config/statistics.php';

        if (!file_exists($configPath)) {
            throw new RuntimeException('統計配置檔案不存在：' . $configPath);
        }

        $config = require $configPath;

        if (!is_array($config)) {
            throw new RuntimeException('統計配置檔案必須返回陣列');
        }

        return $config;
    }

    /**
     * 偵測當前環境.
     */
    private function detectEnvironment(): string
    {
        // 優先從環境變數取得
        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null;

        if (is_string($env) && $env !== '') {
            return $env;
        }

        // 從其他常見環境變數判斷
        if (isset($_ENV['TESTING']) || isset($_SERVER['TESTING'])) {
            return 'testing';
        }

        if (isset($_ENV['DEBUG']) || isset($_SERVER['DEBUG'])) {
            return 'development';
        }

        return 'production';
    }

    /**
     * 取得環境特定配置.
     */
    private function getEnvironmentConfig(string $key): mixed
    {
        $environmentConfig = $this->config['environment'] ?? [];
        if (!is_array($environmentConfig)) {
            return null;
        }

        $envConfig = $environmentConfig[$this->environment] ?? null;
        if (!is_array($envConfig)) {
            return null;
        }

        $keys = explode('.', $key);
        $value = $envConfig;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return null;
            }
            $value = $value[$k];
        }

        return $value;
    }
}
