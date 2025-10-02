<?php

declare(strict_types=1);

namespace App\Shared\Config;

use Exception;
use InvalidArgumentException;

/**
 * 環境配置管理器.
 *
 * 負責載入和管理不同環境的配置檔案，支持多環境配置切換
 */
final class EnvironmentConfig
{
    private string $environment;

    private string $configPath;

    private array $config = [];

    private bool $loaded = false;

    private const VALID_ENVIRONMENTS = ['development', 'testing', 'production'];

    private const REQUIRED_KEYS = [
        'APP_NAME',
        'APP_ENV',
        'DB_CONNECTION',
    ];

    public function __construct(?string $environment = null, ?string $configPath = null)
    {
        $this->environment = $environment ?: $this->detectEnvironment();
        $this->configPath = $configPath ?: $this->getDefaultConfigPath();

        if (!in_array($this->environment, self::VALID_ENVIRONMENTS, true)) {
            throw new InvalidArgumentException(
                "無效的環境: {$this->environment}。支援的環境: " . implode(', ', self::VALID_ENVIRONMENTS),
            );
        }
    }

    /**
     * 載入環境配置.
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $envFile = $this->getEnvironmentFile();

        if (!file_exists($envFile)) {
            throw new Exception("環境配置檔案不存在: {$envFile}");
        }

        $this->loadFromFile($envFile);
        $this->validateRequired();
        $this->loaded = true;
    }

    /**
     * 取得配置值.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->load();

        // 優先從環境變數取得（但忽略空值）
        $envValue = getenv($key);
        if ($envValue !== false && $envValue !== '') {
            return $this->parseValue($envValue);
        }

        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            $envVar = $_ENV[$key];
            if (is_string($envVar)) {
                return $this->parseValue($envVar);
            }
        }

        if (isset($this->config[$key])) {
            $configValue = $this->config[$key];
            if (is_string($configValue)) {
                return $this->parseValue($configValue);
            }

            return $configValue;
        }

        return $default;
    }

    /**
     * 設定配置值.
     */
    public function set(string $key, string $value): void
    {
        $this->config[$key] = $value;
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }

    /**
     * 取得目前環境.
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * 檢查是否為生產環境.
     */
    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }

    /**
     * 檢查是否為開發環境.
     */
    public function isDevelopment(): bool
    {
        return $this->environment === 'development';
    }

    /**
     * 檢查是否為測試環境.
     */
    public function isTesting(): bool
    {
        return $this->environment === 'testing';
    }

    /**
     * 取得所有已載入的配置.
     */
    public function all(): array
    {
        $this->load();

        return $this->config;
    }

    /**
     * 驗證環境配置的完整性.
     */
    public function validate(): array
    {
        $this->load();
        $errors = [];

        // 檢查必要的配置項目
        foreach (self::REQUIRED_KEYS as $key) {
            if (empty($this->getRaw($key))) {
                $errors[] = "必要配置項目遺失: {$key}";
            }
        }

        // 環境特定的驗證
        $errors = array_merge($errors, $this->validateEnvironmentSpecific());

        return $errors;
    }

    /**
     * 自動偵測當前環境.
     */
    private function detectEnvironment(): string
    {
        // 從環境變數檢測
        $env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');

        // 確保返回值是字串
        if (!is_string($env)) {
            $env = 'development';
        }

        // 檢測是否在測試模式
        if (defined('PHPUNIT_RUNNING') || (function_exists('running_tests') && running_tests())) {
            return 'testing';
        }

        return $env;
    }

    /**
     * 取得預設配置路徑.
     */
    private function getDefaultConfigPath(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * 取得環境檔案路徑.
     */
    private function getEnvironmentFile(): string
    {
        return $this->configPath . "/.env.{$this->environment}";
    }

    /**
     * 從檔案載入配置.
     */
    private function loadFromFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception("無法讀取環境配置檔案: {$filePath}");
        }

        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            // 跳過註解和空行
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // 解析 KEY=VALUE 格式
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // 移除引號
                $value = trim($value, '"\'');

                // 只將值存入配置檔案，不覆寫現有的環境變數
                $this->config[$key] = $value;

                // 只在環境變數不存在時才設定
                if (getenv($key) === false && !isset($_ENV[$key])) {
                    $_ENV[$key] = $value;
                    putenv("{$key}={$value}");
                }
            }
        }
    }

    /**
     * 解析配置值.
     */
    private function parseValue(string $value): mixed
    {
        // 解析布林值
        $lower = strtolower($value);
        if ($lower === 'true' || $lower === '1' || $lower === 'on' || $lower === 'yes') {
            return true;
        }
        if ($lower === 'false' || $lower === '0' || $lower === 'off' || $lower === 'no' || $lower === '') {
            return false;
        }

        // 解析 null 值
        if ($lower === 'null') {
            return null;
        }

        // 解析數字
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }

    /**
     * 驗證必要配置項目.
     */
    private function validateRequired(): void
    {
        $missing = [];

        foreach (self::REQUIRED_KEYS as $key) {
            // 檢查環境變數和配置檔案（按實際獲取順序）
            $hasValue = false;

            // 先檢查環境變數
            $envValue = getenv($key);
            if ($envValue !== false && $envValue !== '') {
                $hasValue = true;
            }

            if (!$hasValue && isset($_ENV[$key]) && $_ENV[$key] !== '') {
                $hasValue = true;
            }

            // 最後檢查配置檔案
            if (!$hasValue && isset($this->config[$key]) && $this->config[$key] !== '') {
                $hasValue = true;
            }

            // 針對 JWT 金鑰，額外檢查路徑變數
            if (in_array($key, ['JWT_PRIVATE_KEY', 'JWT_PUBLIC_KEY'])) {
                $pathKey = $key . '_PATH';
                $pathValue = getenv($pathKey);
                if ($pathValue !== false && $pathValue !== '') {
                    $hasValue = true;
                }
                if (!$hasValue && isset($_ENV[$pathKey]) && $_ENV[$pathKey] !== '') {
                    $hasValue = true;
                }
            }

            if (!$hasValue) {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            throw new Exception(
                '環境配置缺少必要項目: ' . implode(', ', $missing),
            );
        }
    }

    /**
     * 取得原始配置值（不觸發自動載入）.
     */
    private function getRaw(string $key): mixed
    {
        // 優先從環境變數取得（但忽略空值）
        $envValue = getenv($key);
        if ($envValue !== false && $envValue !== '') {
            return $this->parseValue($envValue);
        }

        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            $envVar = $_ENV[$key];
            if (is_string($envVar)) {
                return $this->parseValue($envVar);
            }

            return $envVar;
        }

        // 從配置檔案中取得
        return $this->config[$key] ?? null;
    }

    /**
     * 環境特定的驗證.
     */
    private function validateEnvironmentSpecific(): array
    {
        $errors = [];

        $environmentErrors = match ($this->environment) {
            'production' => $this->validateProductionConfig(),
            'testing' => $this->validateTestingConfig(),
            'development' => $this->validateDevelopmentConfig(),
            default => [],
        };

        return array_merge($errors, $environmentErrors);
    }

    /**
     * 驗證生產環境配置.
     */
    private function validateProductionConfig(): array
    {
        $errors = [];

        // 生產環境必須關閉偵錯
        $appDebug = $this->get('APP_DEBUG', false);
        if ($appDebug === true || $appDebug === 'true' || $appDebug === '1') {
            $errors[] = '生產環境必須關閉 APP_DEBUG';
        }

        // 生產環境必須使用 HTTPS
        $forceHttps = $this->get('FORCE_HTTPS', false);
        if ($forceHttps === false || $forceHttps === 'false' || $forceHttps === '0' || $forceHttps === '') {
            $errors[] = '生產環境建議啟用 FORCE_HTTPS';
        }

        // 檢查敏感資訊是否為預設值
        $sensitiveKeys = [
            'APP_KEY' => 'base64:CHANGE-THIS-TO-REAL-PRODUCTION-KEY',
            'ADMIN_PASSWORD' => 'CHANGE-THIS-TO-STRONG-PASSWORD',
            'JWT_PRIVATE_KEY' => 'REPLACE-WITH-ACTUAL-PRIVATE-KEY',
        ];

        foreach ($sensitiveKeys as $key => $defaultValue) {
            $value = $this->get($key, '');
            if (is_string($value) && str_contains($value, $defaultValue)) {
                $errors[] = "生產環境必須修改 {$key} 的預設值";
            }
        }

        return $errors;
    }

    /**
     * 驗證測試環境配置.
     */
    private function validateTestingConfig(): array
    {
        $errors = [];

        // 測試環境應該使用記憶體資料庫
        $dbDatabase = $this->get('DB_DATABASE', '');
        if (is_string($dbDatabase) && $dbDatabase !== ':memory:' && !str_contains($dbDatabase, 'test')) {
            $errors[] = '測試環境建議使用記憶體資料庫或測試專用資料庫';
        }

        return $errors;
    }

    /**
     * 驗證開發環境配置.
     */
    private function validateDevelopmentConfig(): array
    {
        $errors = [];

        // 開發環境應該啟用偵錯
        $appDebug = $this->get('APP_DEBUG', false);
        if ($appDebug === false || $appDebug === 'false' || $appDebug === '0' || $appDebug === '') {
            $errors[] = '開發環境建議啟用 APP_DEBUG';
        }

        return $errors;
    }
}
