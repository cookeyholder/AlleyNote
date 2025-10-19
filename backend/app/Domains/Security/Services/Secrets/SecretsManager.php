<?php

declare(strict_types=1);

namespace App\Domains\Security\Services\Secrets;

use App\Domains\Security\Contracts\SecretsManagerInterface;
use App\Shared\Exceptions\ValidationException;

class SecretsManager implements SecretsManagerInterface
{
    private string $envPath;

    private array $secrets = [];

    private bool $loaded = false;

    public function __construct(string $envPath = '')
    {
        $this->envPath = $envPath ?: __DIR__ . '/../../../.env';
    }

    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        // 從環境變數載入
        $this->loadFromEnvironment();

        // 從 .env 檔案載入（如果存在）
        if (file_exists($this->envPath)) {
            $this->loadFromFile($this->envPath);
        }

        $this->loaded = true;
    }

    public function get(string $key, mixed $default = null)
    {
        $this->load();

        // 優先使用環境變數
        $envValue = getenv($key);
        if ($envValue !== false) {
            return $this->parseValue($envValue);
        }

        // 使用 $_ENV
        if (isset($_ENV[$key]) && is_string($_ENV[$key])) {
            return $this->parseValue($_ENV[$key]);
        }

        // 使用 $_SERVER
        if (isset($_SERVER[$key]) && is_string($_SERVER[$key])) {
            return $this->parseValue($_SERVER[$key]);
        }

        // 使用載入的秘密
        if (isset($this->secrets[$key]) && is_string($this->secrets[$key])) {
            return $this->parseValue($this->secrets[$key]);
        }

        return $default;
    }

    public function set(string $key, string $value): void
    {
        $this->secrets[$key] = $value;

        // 同時設定到環境變數
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function getRequired(string $key): string
    {
        $value = $this->get($key);

        if ($value === null || $value === '') {
            throw new \RuntimeException("必需的環境變數 '{$key}' 未設定");
        }

        if (!is_scalar($value)) {
            throw new \RuntimeException("環境變數 '{$key}' 不是字串");
        }

        return (string) $value;
    }

    public function validateRequiredSecrets(array $requiredKeys): void
    {
        $missing = [];

        foreach ($requiredKeys as $key) {
            if (is_string($key) && (!$this->has($key) || $this->get($key) === '')) {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            throw new \RuntimeException(
                '缺少必需的環境變數: ' . implode(', ', $missing),
            );
        }
    }

    public function isProduction(): bool
    {
        $env = $this->get('APP_ENV', 'production');
        return is_string($env) && strtolower($env) === 'production';
    }

    public function isDevelopment(): bool
    {
        $env = $this->get('APP_ENV', 'production');
        return is_string($env) && strtolower($env) === 'development';
    }

    public function getSecretsSummary(): array
    {
        $this->load();

        $summary = [];
        $sensitiveKeys = [
            'password',
            'secret',
            'key',
            'token',
            'hash',
            'salt',
            'private',
            'auth',
            'api',
            'database',
            'db',
        ];

        foreach (array_keys($this->secrets) as $key) {
            $isSensitive = false;

            foreach ($sensitiveKeys as $sensitiveKey) {
                if (stripos($key, $sensitiveKey) !== false) {
                    $isSensitive = true;
                    break;
                }
            }

            $summary[$key] = [
                'set' => $this->has($key),
                'length' => is_scalar($this->get($key)) ? strlen((string) $this->get($key)) : 0,
                'sensitive' => $isSensitive,
                'value' => $isSensitive ? '[REDACTED]' : $this->get($key),
            ];
        }

        return $summary;
    }

    public function generateSecret(int $length = 32): string
    {
        if ($length < 1) {
            $length = 32;
        }
        return bin2hex(random_bytes($length));
    }

    public function validateEnvFile(string $filePath = ''): array
    {
        $filePath = $filePath ?: $this->envPath;
        $issues = [];

        if (!file_exists($filePath)) {
            $issues[] = '.env 檔案不存在';

            return $issues;
        }

        // 檢查檔案權限
        $perms = fileperms($filePath) & 0o777;
        if ($perms !== 0o600 && $perms !== 0o644) {
            $issues[] = sprintf('.env 檔案權限不安全 (%o)，建議設為 600', $perms);
        }

        // 檢查檔案內容
        $content = file_get_contents($filePath);
        if ($content === false) {
            $issues[] = '無法讀取 .env 檔案';
            return $issues;
        }
        $lines = explode("\n", $content);

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);

            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                $issues[] = sprintf('第 %d 行格式錯誤', $lineNumber + 1);
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // 檢查 key 格式
            if (!preg_match('/^[A-Z_][A-Z0-9_]*$/', $key)) {
                $issues[] = sprintf('第 %d 行：環境變數名稱格式不正確 (%s)', $lineNumber + 1, $key);
            }

            // 檢查敏感資料是否有適當的複雜度
            if ($this->isSensitiveKey($key)) {
                if (strlen($value) < 16) {
                    $issues[] = sprintf('第 %d 行：敏感環境變數 %s 值過短', $lineNumber + 1, $key);
                }

                if (preg_match('/^(password|123|test|demo|example)$/i', $value)) {
                    $issues[] = sprintf('第 %d 行：敏感環境變數 %s 使用不安全的預設值', $lineNumber + 1, $key);
                }
            }
        }

        return $issues;
    }

    private function loadFromEnvironment(): void
    {
        // 載入所有環境變數
        foreach ($_ENV as $key => $value) {
            $this->secrets[$key] = $value;
        }

        foreach ($_SERVER as $key => $value) {
            if (is_string($value) && !isset($this->secrets[$key])) {
                $this->secrets[$key] = $value;
            }
        }
    }

    private function loadFromFile(string $filePath): void
    {
        if (!is_readable($filePath)) {
            throw new \RuntimeException("無法讀取環境設定檔案: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("無法讀取檔案內容: {$filePath}");
        }
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // 移除引號
                if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
                    || (str_starts_with($value, "'") && str_ends_with($value, "'"))
                ) {
                    $value = substr($value, 1, -1);
                }

                // 只有在環境變數中不存在時才設定
                if (!isset($_ENV[$key]) && !isset($_SERVER[$key])) {
                    $this->secrets[$key] = $value;
                }
            }
        }
    }

    private function parseValue(string $value): mixed
    {
        // 處理布林值
        if (in_array(strtolower($value), ['true', 'false'])) {
            return strtolower($value) === 'true';
        }

        // 處理 null 值
        if (strtolower($value) === 'null') {
            return null;
        }

        // 處理數字
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        $sensitivePatterns = [
            'password',
            'passwd',
            'secret',
            'key',
            'token',
            'auth',
            'private',
            'salt',
            'hash',
            'signature',
            'api_key',
        ];

        $key = strtolower($key);

        foreach ($sensitivePatterns as $pattern) {
            if (str_contains($key, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
