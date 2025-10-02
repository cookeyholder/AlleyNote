<?php

declare(strict_types=1);

namespace AlleyNote\Scripts;

/**
 * 腳本統一載入器
 * 提供所有腳本共用的初始化邏輯和依賴注入
 */
final class ScriptBootstrap
{
    private static ?self $instance = null;
    private bool $initialized = false;
    private array $config = [];

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 初始化腳本環境
     */
    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        // 載入 autoload
        $autoloadPath = $this->findAutoloadPath();
        if (!file_exists($autoloadPath)) {
            throw new \RuntimeException("找不到 autoload 檔案: {$autoloadPath}");
        }
        require_once $autoloadPath;

        // 載入環境設定
        $this->loadEnvironment();

        // 設定錯誤處理
        $this->setupErrorHandling();

        // 設定時區
        date_default_timezone_set('Asia/Taipei');

        $this->initialized = true;
    }

    /**
     * 取得腳本配置
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * 設定腳本配置
     */
    public function setConfig(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }

    /**
     * 輸出格式化訊息
     */
    public function output(string $message, string $type = 'info'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $prefix = match($type) {
            'success' => '✅',
            'error' => '❌',
            'warning' => '⚠️',
            'info' => 'ℹ️',
            default => 'ℹ️'
        };

        echo "[{$timestamp}] {$prefix} {$message}\n";
    }

    /**
     * 尋找 autoload 路徑
     */
    private function findAutoloadPath(): string
    {
        $possiblePaths = [
            __DIR__ . '/../vendor/autoload.php',
            __DIR__ . '/../../vendor/autoload.php',
            __DIR__ . '/../../../vendor/autoload.php',
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        throw new \RuntimeException('找不到 vendor/autoload.php 檔案');
    }

    /**
     * 載入環境設定
     */
    private function loadEnvironment(): void
    {
        $envPath = dirname(__DIR__) . '/.env';
        if (file_exists($envPath) && class_exists('\Dotenv\Dotenv')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
            $dotenv->load();
        }
    }

    /**
     * 設定錯誤處理
     */
    private function setupErrorHandling(): void
    {
        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            $this->output("錯誤: {$message} 在 {$file}:{$line}", 'error');
            return true;
        });

        set_exception_handler(function (\Throwable $exception): void {
            $this->output(
                "未捕獲的例外: {$exception->getMessage()} 在 {$exception->getFile()}:{$exception->getLine()}",
                'error'
            );
            exit(1);
        });
    }

    /**
     * 防止複製
     */
    private function __clone()
    {
    }

    /**
     * 防止反序列化
     */
    public function __wakeup(): void
    {
        throw new \Exception('Cannot unserialize singleton');
    }
}

/**
 * 便利函式：快速初始化腳本環境
 */
function bootstrap(): ScriptBootstrap
{
    $bootstrap = ScriptBootstrap::getInstance();
    $bootstrap->initialize();
    return $bootstrap;
}

/**
 * 便利函式：輸出訊息
 */
function script_output(string $message, string $type = 'info'): void
{
    ScriptBootstrap::getInstance()->output($message, $type);
}

/**
 * 便利函式：取得配置
 */
function script_config(string $key, mixed $default = null): mixed
{
    return ScriptBootstrap::getInstance()->getConfig($key, $default);
}
