<?php

declare(strict_types=1);

namespace App\Shared\Monitoring\Providers;

use App\Shared\Monitoring\Contracts\ErrorTrackerInterface;
use App\Shared\Monitoring\Contracts\PerformanceMonitorInterface;
use App\Shared\Monitoring\Contracts\SystemMonitorInterface;
use App\Shared\Monitoring\Services\ErrorTrackerService;
use App\Shared\Monitoring\Services\PerformanceMonitorService;
use App\Shared\Monitoring\Services\SystemMonitorService;
use DI\Container;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * 監控服務提供者。
 *
 * 負責註冊所有監控相關的服務到 DI 容器中
 */
class MonitoringServiceProvider
{
    /**
     * 取得監控服務的 DI 定義。
     */
    public static function getDefinitions(): array
    {
        return [
            // 系統監控服務
            SystemMonitorInterface::class => \DI\factory(function (ContainerInterface $c) {
                return new SystemMonitorService(
                    $c->get(LoggerInterface::class)
                );
            }),

            // 效能監控服務
            PerformanceMonitorInterface::class => \DI\factory(function (ContainerInterface $c) {
                return new PerformanceMonitorService(
                    $c->get(LoggerInterface::class)
                );
            }),

            // 錯誤追蹤服務
            ErrorTrackerInterface::class => \DI\factory(function (ContainerInterface $c) {
                return new ErrorTrackerService(
                    $c->get(LoggerInterface::class)
                );
            }),

            // 註冊具體實現類別的別名
            SystemMonitorService::class => \DI\get(SystemMonitorInterface::class),
            PerformanceMonitorService::class => \DI\get(PerformanceMonitorInterface::class),
            ErrorTrackerService::class => \DI\get(ErrorTrackerInterface::class),
        ];
    }
    /**
     * 註冊監控服務到容器（舊版方法，保留向後相容）。
     */
    public static function register(Container $container): void
    {
        // 系統監控服務
        $container->set(SystemMonitorInterface::class, function (ContainerInterface $c) {
            return new SystemMonitorService(
                $c->get(LoggerInterface::class)
            );
        });

        // 效能監控服務
        $container->set(PerformanceMonitorInterface::class, function (ContainerInterface $c) {
            return new PerformanceMonitorService(
                $c->get(LoggerInterface::class)
            );
        });

        // 錯誤追蹤服務
        $container->set(ErrorTrackerInterface::class, function (ContainerInterface $c) {
            return new ErrorTrackerService(
                $c->get(LoggerInterface::class)
            );
        });

        // 註冊具體實現類別的別名
        $container->set(SystemMonitorService::class, function (ContainerInterface $c) {
            return $c->get(SystemMonitorInterface::class);
        });

        $container->set(PerformanceMonitorService::class, function (ContainerInterface $c) {
            return $c->get(PerformanceMonitorInterface::class);
        });

        $container->set(ErrorTrackerService::class, function (ContainerInterface $c) {
            return $c->get(ErrorTrackerInterface::class);
        });
    }

    /**
     * 初始化監控服務（設置預設配置和事件監聽器）。
     */
    public static function initialize(ContainerInterface $container): void
    {
        /** @var SystemMonitorInterface $systemMonitor */
        $systemMonitor = $container->get(SystemMonitorInterface::class);

        /** @var PerformanceMonitorInterface $performanceMonitor */
        $performanceMonitor = $container->get(PerformanceMonitorInterface::class);

        /** @var ErrorTrackerInterface $errorTracker */
        $errorTracker = $container->get(ErrorTrackerInterface::class);

        // 設置預設的錯誤處理器
        set_error_handler(function (int $severity, string $message, string $file = '', int $line = 0) use ($errorTracker) {
            // 如果錯誤報告被關閉，則不處理
            if (!(error_reporting() & $severity)) {
                return false;
            }

            $context = [
                'severity' => $severity,
                'file' => $file,
                'line' => $line,
                'severity_name' => self::getErrorSeverityName($severity),
            ];

            switch ($severity) {
                case E_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                    $errorTracker->recordError(
                        new \ErrorException($message, 0, $severity, $file, $line),
                        $context
                    );
                    break;

                case E_WARNING:
                case E_CORE_WARNING:
                case E_COMPILE_WARNING:
                case E_USER_WARNING:
                    $errorTracker->recordWarning($message, $context);
                    break;

                default:
                    $errorTracker->recordInfo($message, $context);
                    break;
            }

            return true;
        });

        // 設置例外處理器
        set_exception_handler(function (\Throwable $exception) use ($errorTracker) {
            $errorTracker->recordCriticalError($exception, [
                'uncaught_exception' => true,
                'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
            ]);
        });

        // 註冊關閉處理器（檢查致命錯誤）
        register_shutdown_function(function () use ($errorTracker, $performanceMonitor) {
            $error = error_get_last();

            if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR])) {
                $errorTracker->recordCriticalError(
                    new \ErrorException(
                        $error['message'],
                        0,
                        $error['type'],
                        $error['file'],
                        $error['line']
                    ),
                    [
                        'fatal_error' => true,
                        'error_type' => self::getErrorSeverityName($error['type']),
                    ]
                );
            }

            // 記錄請求結束資訊
            $performanceMonitor->recordMetric('request_completed', 1, [
                'memory_peak_usage' => memory_get_peak_usage(true),
                'memory_usage' => memory_get_usage(true),
                'execution_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)),
            ]);
        });

        // 設置預設的錯誤過濾器
        $errorTracker->setErrorFilter(function (string $level, string $message, array $context, ?\Throwable $exception) {
            // 過濾掉某些不重要的錯誤
            $ignoredMessages = [
                'Undefined variable',
                'Undefined index',
                'Trying to get property',
            ];

            foreach ($ignoredMessages as $ignoredMessage) {
                if (strpos($message, $ignoredMessage) !== false && $level === 'info') {
                    return false; // 不記錄這些資訊級別的錯誤
                }
            }

            return true;
        });

        // 設置預設的通知處理器（用於關鍵錯誤）
        $errorTracker->addNotificationHandler(function (string $level, string $message, array $context, ?\Throwable $exception) {
            if ($level === 'critical') {
                // 這裡可以整合電子郵件、Slack、Discord 等通知系統
                error_log("CRITICAL ERROR: {$message}");

                // 如果是在開發環境，可以顯示詳細資訊
                if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
                    if ($exception) {
                        error_log("Exception details: " . $exception->__toString());
                    }
                    error_log("Context: " . json_encode($context, JSON_PRETTY_PRINT));
                }
            }
        });
    }

    /**
     * 設置效能基準測試。
     */
    public static function setupPerformanceBenchmarks(ContainerInterface $container): void
    {
        /** @var PerformanceMonitorInterface $performanceMonitor */
        $performanceMonitor = $container->get(PerformanceMonitorInterface::class);

        // 設置預設的效能閾值
        $performanceMonitor->setSlowQueryThreshold(1.0); // 1 秒
        $performanceMonitor->setSlowOperationThreshold(2.0); // 2 秒

        // 記錄應用程式啟動指標
        $performanceMonitor->recordMetric('app_startup', microtime(true), [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        ]);
    }

    /**
     * 設置系統健康檢查排程。
     */
    public static function setupHealthCheckSchedule(ContainerInterface $container): void
    {
        /** @var SystemMonitorInterface $systemMonitor */
        $systemMonitor = $container->get(SystemMonitorInterface::class);

        // 註冊定期健康檢查（如果有排程系統的話）
        // 這裡只是示例，實際實作取決於使用的排程系統

        // 檢查系統健康狀態
        $healthStatus = $systemMonitor->getSystemHealth();

        if ($healthStatus['status'] !== 'healthy') {
            /** @var ErrorTrackerInterface $errorTracker */
            $errorTracker = $container->get(ErrorTrackerInterface::class);

            $errorTracker->recordWarning('System health check failed', [
                'health_status' => $healthStatus,
                'check_time' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * 取得錯誤嚴重程度名稱。
     */
    private static function getErrorSeverityName(int $severity): string
    {
        return match ($severity) {
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
            default => 'UNKNOWN',
        };
    }
}
