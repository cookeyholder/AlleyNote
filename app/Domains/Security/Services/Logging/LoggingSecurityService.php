<?php

declare(strict_types=1);

namespace App\Domains\Security\Services\Logging;

use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * 安全日誌記錄服務.
 *
 * 提供安全的日誌記錄功能，包含資料淨化和權限控制
 */
class LoggingSecurityService implements LoggingSecurityServiceInterface
{
    private Logger $logger;

    private Logger $securityLogger;

    private Logger $auditLogger;

    /**
     * 請求資料白名單 - 只記錄這些安全的欄位.
     */
    private const REQUEST_WHITELIST = [
        'method',
        'uri',
        'status_code',
        'response_time',
        'user_id',
        'session_id',
        'timestamp',
        'client_ip',
        'user_agent_hash', // 只記錄雜湊值，不記錄完整 User-Agent
    ];

    /**
     * 敏感資料清單 - 這些欄位需要被遮罩或移除.
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'csrf_token',
        'api_key',
        'secret',
        'token',
        'cookie',
        'authorization',
        'x-api-key',
        'authentication',
    ];

    public function __construct()
    {
        $this->initializeLoggers();
    }

    /**
     * 初始化日誌記錄器.
     */
    private function initializeLoggers(): void
    {
        $logsDir = storage_path('logs');

        // 確保日誌目錄存在且權限正確
        $this->ensureLogDirectory($logsDir);

        // 主要應用日誌
        $this->logger = new Logger('app');
        $appHandler = new RotatingFileHandler(
            $logsDir . '/app.log',
            0, // 保留所有檔案
            Logger::DEBUG
        );
        $appHandler->setFormatter(new JsonFormatter());
        $this->logger->pushHandler($appHandler);

        // 安全事件日誌
        $this->securityLogger = new Logger('security');
        $securityHandler = new RotatingFileHandler(
            $logsDir . '/security.log',
            30, // 保留30天
            Logger::INFO
        );
        $securityHandler->setFormatter(new JsonFormatter());
        $this->securityLogger->pushHandler($securityHandler);

        // 審計日誌（不輪轉，永久保存）
        $this->auditLogger = new Logger('audit');
        $auditHandler = new StreamHandler(
            $logsDir . '/audit.log',
            Logger::INFO
        );
        $auditHandler->setFormatter(new JsonFormatter());
        $this->auditLogger->pushHandler($auditHandler);

        // 設定所有日誌檔案權限
        $this->setLogFilePermissions($logsDir);
    }

    /**
     * 確保日誌目錄存在且權限正確.
     */
    private function ensureLogDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0750, true);
        }
        chmod($path, 0750);
    }

    /**
     * 設定日誌檔案權限為 0640.
     */
    private function setLogFilePermissions(string $logsDir): void
    {
        $logFiles = [
            'app.log',
            'security.log',
            'audit.log',
            'csp_violations.log',
        ];

        foreach ($logFiles as $file) {
            $filepath = $logsDir . '/' . $file;
            if (file_exists($filepath)) {
                chmod($filepath, 0640);
            }
        }

        // 也處理輪轉的日誌檔案
        $rotatedFiles = glob($logsDir . '/*.log-*');
        foreach ($rotatedFiles as $file) {
            chmod($file, 0640);
        }
    }

    /**
     * 記錄一般應用日誌.
     */
    public function info(string $message, array $context = []): void
    {
        $sanitizedContext = $this->sanitizeContext($context);
        $this->logger->info($message, $sanitizedContext);
    }

    public function warning(string $message, array $context = []): void
    {
        $sanitizedContext = $this->sanitizeContext($context);
        $this->logger->warning($message, $sanitizedContext);
    }

    public function error(string $message, array $context = []): void
    {
        $sanitizedContext = $this->sanitizeContext($context);
        $this->logger->error($message, $sanitizedContext);
    }

    /**
     * 記錄安全事件.
     */
    public function logSecurityEvent(string $event, array $context = []): void
    {
        $sanitizedContext = $this->sanitizeContext($context);
        $enrichedContext = $this->enrichSecurityContext($sanitizedContext);

        $this->securityLogger->warning($event, $enrichedContext);
    }

    /**
     * 記錄高風險安全事件.
     */
    public function logCriticalSecurityEvent(string $event, array $context = []): void
    {
        $sanitizedContext = $this->sanitizeContext($context);
        $enrichedContext = $this->enrichSecurityContext($sanitizedContext);

        $this->securityLogger->critical($event, $enrichedContext);

        // 同時記錄到審計日誌
        $this->auditLogger->critical($event, $enrichedContext);
    }

    /**
     * 記錄請求日誌（使用白名單模式）.
     */
    public function logRequest(array $requestData): void
    {
        $whitelistedData = $this->applyRequestWhitelist($requestData);
        $enrichedData = $this->enrichRequestContext($whitelistedData);

        $this->logger->info('HTTP Request', $enrichedData);
    }

    /**
     * 記錄驗證失敗事件.
     */
    public function logAuthenticationFailure(string $reason, array $context = []): void
    {
        $sanitizedContext = $this->sanitizeContext($context);
        $enrichedContext = $this->enrichSecurityContext($sanitizedContext);

        $this->securityLogger->warning('Authentication Failure: ' . $reason, $enrichedContext);
    }

    /**
     * 記錄授權失敗事件.
     */
    public function logAuthorizationFailure(string $resource, string $action, array $context = []): void
    {
        $sanitizedContext = $this->sanitizeContext($context);
        $enrichedContext = $this->enrichSecurityContext($sanitizedContext);

        $this->securityLogger->warning(
            "Authorization Failure: Access denied to {$resource} for action {$action}",
            $enrichedContext
        );
    }

    /**
     * 應用請求資料白名單.
     */
    private function applyRequestWhitelist(array $data): array
    {
        $filtered = [];

        foreach (self::REQUEST_WHITELIST as $allowedField) {
            if (isset($data[$allowedField])) {
                $filtered[$allowedField] = $data[$allowedField];
            }
        }

        return $filtered;
    }

    /**
     * 淨化上下文資料，移除敏感資訊.
     */
    private function sanitizeContext(array $context): array
    {
        return $this->recursiveSanitize($context);
    }

    /**
     * 遞迴淨化陣列，移除敏感資料.
     */
    private function recursiveSanitize(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $lowercaseKey = strtolower($key);

            // 檢查是否為敏感欄位
            $isSensitive = false;
            foreach (self::SENSITIVE_FIELDS as $sensitiveField) {
                if (strpos($lowercaseKey, $sensitiveField) !== false) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->recursiveSanitize($value);
            } elseif (is_string($value) && strlen($value) > 1000) {
                // 截斷過長的字串
                $sanitized[$key] = substr($value, 0, 1000) . '[TRUNCATED]';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * 豐富安全上下文資訊.
     */
    private function enrichSecurityContext(array $context): array
    {
        $context['server_time'] = date('Y-m-d H:i:s');
        $context['process_id'] = getmypid();

        if (!isset($context['session_id']) && session_status() === PHP_SESSION_ACTIVE) {
            $context['session_id'] = session_id();
        }

        if (!isset($context['user_id']) && isset($_SESSION['user_id'])) {
            $context['user_id'] = $_SESSION['user_id'];
        }

        return $context;
    }

    /**
     * 豐富請求上下文資訊.
     */
    private function enrichRequestContext(array $context): array
    {
        $context['server_time'] = date('Y-m-d H:i:s');

        // 如果有 User-Agent，轉換為雜湊值
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $context['user_agent_hash'] = hash('sha256', $_SERVER['HTTP_USER_AGENT']);
        }

        return $context;
    }

    /**
     * 檢查並修正日誌檔案權限.
     */
    public function verifyLogFilePermissions(): array
    {
        $logsDir = storage_path('logs');
        $results = [];

        if (!is_dir($logsDir)) {
            return ['error' => 'Logs directory does not exist'];
        }

        $logFiles = glob($logsDir . '/*.log*');

        foreach ($logFiles as $file) {
            $perms = fileperms($file) & 0777;
            $expected = 0640;

            $results[basename($file)] = [
                'current_permissions' => sprintf('%o', $perms),
                'expected_permissions' => sprintf('%o', $expected),
                'is_correct' => $perms === $expected,
            ];

            // 如果權限不正確，嘗試修正
            if ($perms !== $expected) {
                if (chmod($file, $expected)) {
                    $results[basename($file)]['corrected'] = true;
                } else {
                    $results[basename($file)]['correction_failed'] = true;
                }
            }
        }

        return $results;
    }

    /**
     * 取得日誌統計資訊.
     */
    public function getLogStatistics(): array
    {
        $logsDir = storage_path('logs');
        $stats = [
            'directory' => $logsDir,
            'directory_permissions' => sprintf('%o', fileperms($logsDir) & 0777),
            'files' => [],
        ];

        $logFiles = glob($logsDir . '/*.log*');

        foreach ($logFiles as $file) {
            $stats['files'][basename($file)] = [
                'size' => filesize($file),
                'permissions' => sprintf('%o', fileperms($file) & 0777),
                'last_modified' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        return $stats;
    }
}
