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

    /** @var array<string, mixed> */
    private array $config = [];

    private bool $loaded = false;

    private const VALID_ENVIRONMENTS = ['development', 'testing', 'production'];

    private const REQUIRED_KEYS = [
        'APP_NAME',
        'APP_ENV',
        'DB_CONNECTION',
        'JWT_PRIVATE_KEY',
        'JWT_PUBLIC_KEY',
    ];

    public function __construct(?string $environment = null, ?string $configPath = null)
    {
        $this->environment = $environment ?? $this->detectEnvironment();
        $this->configPath = $configPath ?? $this->getDefaultConfigPath();

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
     * 取得配置值
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
     * 設定配置值
     */
    public function set(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }

    /**
     * 檢查配置鍵是否存在.
     */
    public function has(string $key): bool
    {
        $this->load();

        return isset($this->config[$key])
            || getenv($key) !== false
            || isset($_ENV[$key]);
    }

    /**
     * 取得所有配置.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $this->load();

        return $this->config;
    }

    /**
     * 取得當前環境.
     */
    public function getEnvironment(): string
    {
        return $this->environment;
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
     * 檢查是否為生產環境.
     */
    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }

    /**
     * 重新載入配置.
     */
    public function reload(): void
    {
        $this->loaded = false;
        $this->config = [];
        $this->load();
    }

    /**
     * 從檔案載入配置.
     */
    private function loadFromFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception("無法讀取配置檔案: {$filePath}");
        }

        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            // 跳過空行和註解
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            $this->parseLine($line);
        }
    }

    /**
     * 解析配置行.
     */
    private function parseLine(string $line): void
    {
        if (!str_contains($line, '=')) {
            return;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // 移除引號
        if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        $this->config[$key] = $value;
    }

    /**
     * 解析配置值
     */
    private function parseValue(string $value): mixed
    {
        // 布林值
        if (in_array(strtolower($value), ['true', '1', 'on', 'yes'], true)) {
            return true;
        }

        if (in_array(strtolower($value), ['false', '0', 'off', 'no'], true)) {
            return false;
        }

        // null 值
        if (strtolower($value) === 'null') {
            return null;
        }

        // 數字
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        // JSON 陣列或物件
        if ((str_starts_with($value, '[') && str_ends_with($value, ']'))
            || (str_starts_with($value, '{') && str_ends_with($value, '}'))) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }

    /**
     * 偵測當前環境.
     */
    private function detectEnvironment(): string
    {
        // 優先從環境變數取得
        $envVar = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? null);
        if (is_string($envVar) && in_array($envVar, self::VALID_ENVIRONMENTS, true)) {
            return $envVar;
        }

        // 從 SERVER 變數偵測
        if (isset($_SERVER['APP_ENV']) && is_string($_SERVER['APP_ENV'])) {
            $serverEnv = $_SERVER['APP_ENV'];
            if (in_array($serverEnv, self::VALID_ENVIRONMENTS, true)) {
                return $serverEnv;
            }
        }

        // 根據主機名稱或其他指標判斷
        if (function_exists('gethostname')) {
            $hostname = gethostname();
            if (is_string($hostname)) {
                if (str_contains($hostname, 'local') || str_contains($hostname, 'dev')) {
                    return 'development';
                }

                if (str_contains($hostname, 'test')) {
                    return 'testing';
                }
            }
        }

        // 預設為開發環境
        return 'development';
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
        $envFile = $this->configPath . '/.env';

        // 嘗試環境特定的檔案
        $envSpecificFile = $this->configPath . "/.env.{$this->environment}";
        if (file_exists($envSpecificFile)) {
            return $envSpecificFile;
        }

        return $envFile;
    }

    /**
     * 驗證配置完整性.
     *
     * @return array<string> 驗證錯誤清單
     */
    public function validate(): array
    {
        $errors = [];

        try {
            $this->load();
            $this->validateRequired();
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }

        return $errors;
    }

    /**
     * 驗證必要的配置鍵.
     */
    private function validateRequired(): void
    {
        $missing = [];

        foreach (self::REQUIRED_KEYS as $key) {
            if (!$this->has($key)) {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            throw new Exception(
                '缺少必要的配置鍵: ' . implode(', ', $missing),
            );
        }
    }
}
