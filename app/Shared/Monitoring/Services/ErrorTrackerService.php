<?php

declare(strict_types=1);

namespace App\Shared\Monitoring\Services;

use App\Shared\Monitoring\Contracts\ErrorTrackerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * 錯誤追蹤服務實作。
 *
 * 提供全面的錯誤追蹤和分析功能，包含錯誤分類、趨勢分析和警告機制
 */
class ErrorTrackerService implements ErrorTrackerInterface
{
    /** @var array<array> 錯誤記錄暫存 */
    private array $errorRecords = [];

    /** @var array<callable> 錯誤過濾器 */
    private array $errorFilters = [];

    /** @var array<callable> 通知處理器 */
    private array $notificationHandlers = [];

    /** @var int 記錄保留數量限制 */
    private int $maxRecords = 1000;

    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    /**
     * 記錄一個錯誤。
     */
    public function recordError(Throwable $error, array $context = []): string
    {
        return $this->recordErrorWithLevel('error', $error->getMessage(), array_merge($context, [
            'exception_class' => get_class($error),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'stack_trace' => $error->getTraceAsString(),
            'previous' => $error->getPrevious() ? get_class($error->getPrevious()) . ': ' . $error->getPrevious()->getMessage() : null,
        ]), $error);
    }

    /**
     * 記錄一個警告。
     */
    public function recordWarning(string $message, array $context = []): string
    {
        return $this->recordErrorWithLevel('warning', $message, $context);
    }

    /**
     * 記錄一個訊息。
     */
    public function recordInfo(string $message, array $context = []): string
    {
        return $this->recordErrorWithLevel('info', $message, $context);
    }

    /**
     * 記錄關鍵錯誤（需要立即注意）。
     */
    public function recordCriticalError(Throwable $error, array $context = []): string
    {
        $errorId = $this->recordErrorWithLevel('critical', $error->getMessage(), array_merge($context, [
            'exception_class' => get_class($error),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'stack_trace' => $error->getTraceAsString(),
            'previous' => $error->getPrevious() ? get_class($error->getPrevious()) . ': ' . $error->getPrevious()->getMessage() : null,
        ]), $error);

        // 觸發所有通知處理器
        $this->triggerNotifications('critical', $error->getMessage(), $context, $error);

        return $errorId;
    }

    /**
     * 取得錯誤統計資料。
     */
    public function getErrorStats(int $hours = 24): array
    {
        $cutoffTime = microtime(true) - ($hours * 3600);
        $recentErrors = array_filter(
            $this->errorRecords,
            fn($record) => $record['timestamp'] > $cutoffTime
        );

        $stats = [
            'total_errors' => count($recentErrors),
            'time_period_hours' => $hours,
            'error_rate_per_hour' => $hours > 0 ? count($recentErrors) / $hours : 0,
            'levels' => [],
            'error_types' => [],
            'top_error_files' => [],
            'error_trend' => [],
        ];

        // 按等級分組
        foreach ($recentErrors as $record) {
            $level = $record['level'];
            if (!isset($stats['levels'][$level])) {
                $stats['levels'][$level] = 0;
            }
            $stats['levels'][$level]++;
        }

        // 按錯誤類型分組
        foreach ($recentErrors as $record) {
            if (!is_array($record) || !is_array($record['context'] ?? null)) {
                continue;
            }
            /** @var array<string, mixed> $context */
            $context = $record['context'];
            if (isset($context['exception_class']) && is_string($context['exception_class'])) {
                $type = $context['exception_class'];
                if (!isset($stats['error_types'][$type])) {
                    $stats['error_types'][$type] = 0;
                }
                $stats['error_types'][$type]++;
            }
        }

        // 按檔案位置分組
        foreach ($recentErrors as $record) {
            if (!is_array($record) || !is_array($record['context'] ?? null)) {
                continue;
            }
            /** @var array<string, mixed> $context */
            $context = $record['context'];
            if (isset($context['file']) && is_string($context['file'])) {
                $file = basename($context['file']);
                if (!isset($stats['top_error_files'][$file])) {
                    $stats['top_error_files'][$file] = 0;
                }
                $stats['top_error_files'][$file]++;
            }
        }

        // 計算趨勢（每小時錯誤數）
        $stats['error_trend'] = $this->calculateErrorTrend($recentErrors, $hours);

        // 排序統計資料
        arsort($stats['levels']);
        arsort($stats['error_types']);
        arsort($stats['top_error_files']);

        return $stats;
    }

    /**
     * 取得最近的錯誤記錄。
     */
    public function getRecentErrors(int $limit = 50): array
    {
        $errors = $this->errorRecords;

        // 按時間戳排序（最新的在前）
        usort($errors, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return array_slice($errors, 0, $limit);
    }

    /**
     * 取得錯誤趨勢分析。
     */
    public function getErrorTrends(int $days = 7): array
    {
        $cutoffTime = microtime(true) - ($days * 24 * 3600);
        $recentErrors = array_filter(
            $this->errorRecords,
            fn($record) => $record['timestamp'] > $cutoffTime
        );

        $trends = [
            'daily_counts' => [],
            'level_trends' => [],
            'type_trends' => [],
            'total_errors' => count($recentErrors),
            'period_days' => $days,
            'average_errors_per_day' => count($recentErrors) / max(1, $days),
        ];

        // 按日期分組
        foreach ($recentErrors as $record) {
            if (!is_array($record)) {
                continue;
            }
            $timestampValue = $record['timestamp'] ?? 0;
            $timestamp = is_int($timestampValue) || is_numeric($timestampValue) ? (int)$timestampValue : time();
            $date = date('Y-m-d', $timestamp);
            if (!isset($trends['daily_counts'][$date])) {
                $trends['daily_counts'][$date] = 0;
            }
            $trends['daily_counts'][$date]++;
        }

        // 按等級和日期分組
        foreach ($recentErrors as $record) {
            if (!is_array($record)) {
                continue;
            }
            $timestampValue = $record['timestamp'] ?? 0;
            $timestamp = is_int($timestampValue) || is_numeric($timestampValue) ? (int)$timestampValue : time();
            $date = date('Y-m-d', $timestamp);
            $level = is_string($record['level'] ?? '') ? $record['level'] : 'unknown';

            if (!isset($trends['level_trends'][$level])) {
                $trends['level_trends'][$level] = [];
            }
            if (!isset($trends['level_trends'][$level][$date])) {
                $trends['level_trends'][$level][$date] = 0;
            }
            $trends['level_trends'][$level][$date]++;
        }

        // 按錯誤類型和日期分組
        foreach ($recentErrors as $record) {
            if (!is_array($record) || !is_array($record['context'] ?? null)) {
                continue;
            }
            /** @var array<string, mixed> $context */
            $context = $record['context'];
            if (isset($context['exception_class']) && is_string($context['exception_class'])) {
                $timestampValue = $record['timestamp'] ?? 0;
                $timestamp = is_int($timestampValue) || is_numeric($timestampValue) ? (int)$timestampValue : time();
                $date = date('Y-m-d', $timestamp);
                $type = $context['exception_class'];

                if (!isset($trends['type_trends'][$type])) {
                    $trends['type_trends'][$type] = [];
                }
                if (!isset($trends['type_trends'][$type][$date])) {
                    $trends['type_trends'][$type][$date] = 0;
                }
                $trends['type_trends'][$type][$date]++;
            }
        }

        // 填充遺漏的日期（確保所有日期都有資料）
        $trends = $this->fillMissingDates($trends, $days);

        return $trends;
    }

    /**
     * 檢查是否有關鍵錯誤。
     */
    public function hasCriticalErrors(int $minutes = 5): bool
    {
        $cutoffTime = microtime(true) - ($minutes * 60);

        foreach ($this->errorRecords as $record) {
            if ($record['timestamp'] > $cutoffTime && $record['level'] === 'critical') {
                return true;
            }
        }

        return false;
    }

    /**
     * 取得錯誤摘要報告。
     */
    public function getErrorSummary(int $hours = 24): array
    {
        $stats = $this->getErrorStats($hours);
        $recentCritical = [];
        $recentWarnings = [];

        $cutoffTime = microtime(true) - ($hours * 3600);

        foreach ($this->errorRecords as $record) {
            if ($record['timestamp'] <= $cutoffTime) {
                continue;
            }

            if ($record['level'] === 'critical') {
                $recentCritical[] = $record;
            } elseif ($record['level'] === 'warning') {
                $recentWarnings[] = $record;
            }
        }

        return [
            'summary' => [
                'total_errors' => $stats['total_errors'],
                'critical_errors' => count($recentCritical),
                'warnings' => count($recentWarnings),
                'error_rate_per_hour' => $stats['error_rate_per_hour'],
                'time_period_hours' => $hours,
            ],
            'top_issues' => [
                'error_types' => is_array($stats['error_types']) ? array_slice($stats['error_types'], 0, 5, true) : [],
                'error_files' => is_array($stats['top_error_files']) ? array_slice($stats['top_error_files'], 0, 5, true) : [],
            ],
            'recent_critical' => array_slice($recentCritical, 0, 10),
            'health_status' => $this->determineHealthStatus($stats),
        ];
    }

    /**
     * 清理舊的錯誤記錄。
     */
    public function cleanupOldErrors(int $daysToKeep = 30): int
    {
        $cutoffTime = microtime(true) - ($daysToKeep * 24 * 3600);
        $originalCount = count($this->errorRecords);

        $this->errorRecords = array_filter(
            $this->errorRecords,
            fn($record) => $record['timestamp'] > $cutoffTime
        );

        $cleanedCount = $originalCount - count($this->errorRecords);

        $this->logger->info("Error records cleanup completed", [
            'days_kept' => $daysToKeep,
            'records_cleaned' => $cleanedCount,
            'records_remaining' => count($this->errorRecords),
        ]);

        return $cleanedCount;
    }

    /**
     * 設定錯誤過濾規則。
     */
    public function setErrorFilter(callable $filter): void
    {
        $this->errorFilters[] = $filter;
    }

    /**
     * 添加錯誤通知處理器。
     */
    public function addNotificationHandler(callable $handler): void
    {
        $this->notificationHandlers[] = $handler;
    }

    // ===== 私有方法 =====

    /**
     * 記錄錯誤並分配等級。
     */
    private function recordErrorWithLevel(string $level, string $message, array $context = [], ?Throwable $exception = null): string
    {
        // 應用過濾器
        foreach ($this->errorFilters as $filter) {
            if (!$filter($level, $message, $context, $exception)) {
                // 如果過濾器返回 false，跳過記錄
                return '';
            }
        }

        $errorId = Uuid::uuid4()->toString();
        $timestamp = microtime(true); // 使用 microtime 獲得更精確的時間戳

        $record = [
            'id' => $errorId,
            'level' => $level,
            'message' => $message,
            'context' => $this->sanitizeContext($context),
            'timestamp' => $timestamp,
            'formatted_time' => date('Y-m-d H:i:s.u', (int)$timestamp), // 顯示微秒
            'request_id' => $_SERVER['HTTP_X_REQUEST_ID'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'client_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        // 添加到記錄中
        $this->errorRecords[] = $record;

        // 維護記錄數量限制
        if (count($this->errorRecords) > $this->maxRecords) {
            array_shift($this->errorRecords);
        }

        // 記錄到日誌
        match ($level) {
            'critical' => $this->logger->critical($message, $context),
            'error' => $this->logger->error($message, $context),
            'warning' => $this->logger->warning($message, $context),
            'info' => $this->logger->info($message, $context),
            default => $this->logger->debug($message, $context),
        };

        return $errorId;
    }

    /**
     * 清理上下文資料，移除敏感資訊。
     */
    private function sanitizeContext(array $context): array
    {
        $sensitiveKeys = [
            'password', 'password_confirmation', 'token', 'secret', 'key',
            'authorization', 'cookie', 'session', 'csrf_token',
        ];

        foreach ($context as $key => $value) {
            $keyLower = strtolower((string) $key);

            foreach ($sensitiveKeys as $sensitiveKey) {
                if (strpos($keyLower, $sensitiveKey) !== false) {
                    $context[$key] = '[REDACTED]';
                    break;
                }
            }
        }

        return $context;
    }

    /**
     * 觸發通知處理器。
     */
    private function triggerNotifications(string $level, string $message, array $context, ?Throwable $exception = null): void
    {
        foreach ($this->notificationHandlers as $handler) {
            try {
                $handler($level, $message, $context, $exception);
            } catch (\Exception $e) {
                $this->logger->error('Notification handler failed', [
                    'handler_error' => $e->getMessage(),
                    'original_level' => $level,
                    'original_message' => $message,
                ]);
            }
        }
    }

    /**
     * 計算錯誤趨勢（每小時）。
     */
    private function calculateErrorTrend(array $errors, int $hours): array
    {
        $trend = [];
        $currentTime = time();

        for ($i = 0; $i < $hours; $i++) {
            $hourStart = $currentTime - (($i + 1) * 3600);
            $hourEnd = $currentTime - ($i * 3600);
            $hourLabel = date('Y-m-d H:00', $hourStart);

            $hourlyCount = 0;
            foreach ($errors as $error) {
                if (is_array($error) && isset($error['timestamp']) && is_numeric($error['timestamp'])) {
                    $errorTimestamp = (int)$error['timestamp'];
                    if ($errorTimestamp >= $hourStart && $errorTimestamp < $hourEnd) {
                        $hourlyCount++;
                    }
                }
            }

            $trend[$hourLabel] = $hourlyCount;
        }

        return array_reverse($trend, true);
    }

    /**
     * 填充遺漏的日期。
     */
    private function fillMissingDates(array $trends, int $days): array
    {
        // 確保必要的陣列結構存在
        if (!isset($trends['daily_counts']) || !is_array($trends['daily_counts'])) {
            $trends['daily_counts'] = [];
        }
        if (!isset($trends['level_trends']) || !is_array($trends['level_trends'])) {
            $trends['level_trends'] = [];
        }
        if (!isset($trends['type_trends']) || !is_array($trends['type_trends'])) {
            $trends['type_trends'] = [];
        }

        $endDate = time();
        $startDate = $endDate - ($days * 24 * 3600);

        for ($timestamp = $startDate; $timestamp <= $endDate; $timestamp += 24 * 3600) {
            $date = date('Y-m-d', $timestamp);

            if (!isset($trends['daily_counts'][$date])) {
                $trends['daily_counts'][$date] = 0;
            }

            if (is_array($trends['level_trends'])) {
                foreach ($trends['level_trends'] as $level => &$levelData) {
                    if (is_array($levelData) && !isset($levelData[$date])) {
                        $levelData[$date] = 0;
                    }
                }
            }

            if (is_array($trends['type_trends'])) {
                foreach ($trends['type_trends'] as $type => &$typeData) {
                    if (is_array($typeData) && !isset($typeData[$date])) {
                        $typeData[$date] = 0;
                    }
                }
            }
        }

        // 排序日期
        if (is_array($trends['daily_counts'])) {
            ksort($trends['daily_counts']);
        }
        if (is_array($trends['level_trends'])) {
            foreach ($trends['level_trends'] as &$levelData) {
                if (is_array($levelData)) {
                    ksort($levelData);
                }
            }
        }
        if (is_array($trends['type_trends'])) {
            foreach ($trends['type_trends'] as &$typeData) {
                if (is_array($typeData)) {
                    ksort($typeData);
                }
            }
        }

        return $trends;
    }

    /**
     * 判斷健康狀態。
     */
    private function determineHealthStatus(array $stats): array
    {
        $levels = is_array($stats['levels'] ?? null) ? $stats['levels'] : [];

        $criticalValue = $levels['critical'] ?? 0;
        $errorValue = $levels['error'] ?? 0;
        $warningValue = $levels['warning'] ?? 0;
        $totalValue = $stats['total_errors'] ?? 0;

        $criticalCount = is_numeric($criticalValue) ? (int)$criticalValue : 0;
        $errorCount = is_numeric($errorValue) ? (int)$errorValue : 0;
        $warningCount = is_numeric($warningValue) ? (int)$warningValue : 0;
        $totalErrors = is_numeric($totalValue) ? (int)$totalValue : 0;

        if ($criticalCount > 0) {
            return [
                'status' => 'critical',
                'message' => "發現 {$criticalCount} 個關鍵錯誤",
                'score' => 0,
            ];
        } elseif ($errorCount > 10) {
            return [
                'status' => 'unhealthy',
                'message' => "錯誤數量過多 ({$errorCount} 個錯誤)",
                'score' => 25,
            ];
        } elseif ($totalErrors > 50) {
            return [
                'status' => 'warning',
                'message' => "總問題數量較高 ({$totalErrors} 個問題)",
                'score' => 60,
            ];
        } elseif ($warningCount > 0) {
            return [
                'status' => 'caution',
                'message' => "有少量警告 ({$warningCount} 個警告)",
                'score' => 80,
            ];
        } else {
            return [
                'status' => 'healthy',
                'message' => "系統運行正常",
                'score' => 100,
            ];
        }
    }
}
