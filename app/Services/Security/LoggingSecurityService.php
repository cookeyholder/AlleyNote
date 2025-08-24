<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Services\Security\Contracts\LoggingSecurityServiceInterface;
use DateTime;
use Exception;

class LoggingSecurityService implements LoggingSecurityServiceInterface
{
    private string $logPath;

    private string $securityLogPath;

    public function __construct()
    {
        $this->logPath = $this->getLogPath('security.log');
        $this->securityLogPath = $this->getLogPath('security_events.log');

        // 確保日誌目錄存在
        $this->ensureLogDirectoryExists();
    }

    /**
     * 記錄一般安全事件.
     */
    public function logSecurityEvent(string $eventType, array $data = []): void
    {
        $logEntry = [
            'timestamp' => new DateTime()->format('Y-m-d H:i:s'),
            'event_type' => $eventType,
            'level' => 'info',
            'data' => $data,
            'ip' => $this->getCurrentIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ];

        $this->writeToFile($this->securityLogPath, $logEntry);
    }

    /**
     * 記錄關鍵安全事件.
     */
    public function logCriticalSecurityEvent(string $eventType, array $data = []): void
    {
        $logEntry = [
            'timestamp' => new DateTime()->format('Y-m-d H:i:s'),
            'event_type' => $eventType,
            'level' => 'critical',
            'data' => $data,
            'ip' => $this->getCurrentIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'alert' => true,
        ];

        $this->writeToFile($this->securityLogPath, $logEntry);

        // 對於關鍵事件，同時寫入一般日誌以確保記錄
        $this->error("Critical Security Event: {$eventType}", $data);
    }

    /**
     * 記錄錯誤訊息.
     */
    public function error(string $message, array $context = []): void
    {
        $this->writeLog('error', $message, $context);
    }

    /**
     * 記錄警告訊息.
     */
    public function warning(string $message, array $context = []): void
    {
        $this->writeLog('warning', $message, $context);
    }

    /**
     * 記錄資訊訊息.
     */
    public function info(string $message, array $context = []): void
    {
        $this->writeLog('info', $message, $context);
    }

    /**
     * 記錄除錯訊息.
     */
    public function debug(string $message, array $context = []): void
    {
        $this->writeLog('debug', $message, $context);
    }

    /**
     * 寫入日誌記錄.
     */
    private function writeLog(string $level, string $message, array $context = []): void
    {
        $logEntry = [
            'timestamp' => new DateTime()->format('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'ip' => $this->getCurrentIP(),
        ];

        $this->writeToFile($this->logPath, $logEntry);
    }

    /**
     * 寫入檔案.
     */
    private function writeToFile(string $filePath, array $logEntry): void
    {
        try {
            $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            file_put_contents($filePath, $logLine, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // 如果無法寫入檔案，至少記錄到系統錯誤日誌
            error_log('Failed to write security log: ' . $e->getMessage());
            error_log('Original log entry: ' . json_encode($logEntry));
        }
    }

    /**
     * 取得日誌檔案路徑.
     */
    private function getLogPath(string $filename): string
    {
        $baseLogPath = $_ENV['LOG_PATH'] ?? dirname(__DIR__, 3) . '/storage/logs';

        return rtrim($baseLogPath, '/') . '/' . $filename;
    }

    /**
     * 確保日誌目錄存在.
     */
    private function ensureLogDirectoryExists(): void
    {
        $logDir = dirname($this->logPath);
        if (!is_dir($logDir)) {
            try {
                mkdir($logDir, 0o755, true);
            } catch (Exception $e) {
                error_log('Failed to create log directory: ' . $e->getMessage());
            }
        }
    }

    /**
     * 取得當前 IP 位址
     */
    private function getCurrentIP(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (isset($_SERVER[$header]) && !empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // X-Forwarded-For 可能包含多個 IP
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
