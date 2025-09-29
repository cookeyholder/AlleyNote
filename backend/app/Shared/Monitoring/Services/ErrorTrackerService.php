<?php

declare(strict_types=1);

namespace App\Shared\Monitoring\Services;

use App\Shared\Enums\LogLevel;
use App\Shared\Monitoring\Contracts\ErrorTrackerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Throwable;

final class ErrorTrackerService implements ErrorTrackerInterface
{
    /** @var array<int, array{timestamp: float, level: string, message: string, context: array}> */
    private array $errorRecords = [];

    /** @var array<callable> */
    private array $errorFilters = [];

    /** @var array<callable> */
    private array $notificationHandlers = [];

    private int $maxRecords = 1000;

    public function __construct(private LoggerInterface $logger) {}

    public function recordError(Throwable $error, array $context = []): string
    {
        return $this->recordErrorWithLevel(LogLevel::ERROR->value, $error->getMessage(), array_merge($context, [
            'exception_class' => get_class($error),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
        ]), $error);
    }

    public function recordWarning(string $message, array $context = []): string
    {
        return $this->recordErrorWithLevel(LogLevel::WARNING->value, $message, $context);
    }

    public function recordInfo(string $message, array $context = []): string
    {
        return $this->recordErrorWithLevel(LogLevel::INFO->value, $message, $context);
    }

    public function recordCriticalError(Throwable $error, array $context = []): string
    {
        $id = $this->recordErrorWithLevel(LogLevel::CRITICAL->value, $error->getMessage(), $context, $error);
        $this->triggerNotifications(LogLevel::CRITICAL->value, $error->getMessage(), $context, $error);
        return $id;
    }

    public function getErrorStats(int $hours = 24): array
    {
        $cutoff = microtime(true) - ($hours * 3600);
        $recent = array_filter($this->errorRecords, fn($r) => ($r['timestamp']) > $cutoff);
        $levels = [];
        foreach ($recent as $r) {
            $lvl = $r['level'] ?? 'unknown';
            $levels[$lvl] = ($levels[$lvl] ?? 0) + 1;
        }
        return ['total_errors' => count($recent), 'levels' => $levels];
    }

    public function getRecentErrors(int $limit = 50): array
    {
        $errors = $this->errorRecords;
        usort($errors, fn($a, $b) => ($b['timestamp']) <=> ($a['timestamp']));
        return array_slice($errors, 0, $limit);
    }

    public function getErrorTrends(int $days = 7): array
    {
        return ['daily_counts' => []];
    }

    public function hasCriticalErrors(int $minutes = 5): bool
    {
        $cutoff = microtime(true) - ($minutes * 60);
        foreach ($this->errorRecords as $r) {
            if (($r['timestamp']) > $cutoff && ($r['level'] ?? '') === LogLevel::CRITICAL->value) return true;
        }
        return false;
    }

    public function getErrorSummary(int $hours = 24): array
    {
        $stats = $this->getErrorStats($hours);
        return ['summary' => $stats];
    }

    public function cleanupOldErrors(int $daysToKeep = 30): int
    {
        $cutoff = microtime(true) - ($daysToKeep * 24 * 3600);
        $original = count($this->errorRecords);
        $this->errorRecords = array_values(array_filter($this->errorRecords, fn($r) => ($r['timestamp']) > $cutoff));
        return $original - count($this->errorRecords);
    }

    public function setErrorFilter(callable $filter): void
    {
        $this->errorFilters[] = $filter;
    }

    public function addNotificationHandler(callable $handler): void
    {
        $this->notificationHandlers[] = $handler;
    }

    private function recordErrorWithLevel(string $level, string $message, array $context = [], ?Throwable $exception = null): string
    {
        foreach ($this->errorFilters as $filter) {
            try {
                if (!$filter($level, $message, $context, $exception)) return '';
            } catch (Exception $e) {
                $this->logger->error('Error filter threw exception', ['e' => $e->getMessage()]);
            }
        }

        $id = substr(bin2hex(random_bytes(8)), 0, 16);
        $this->errorRecords[] = ['id' => $id, 'timestamp' => microtime(true), 'level' => $level, 'message' => $message, 'context' => $this->sanitizeContext($context)];

        foreach ($this->notificationHandlers as $h) {
            try {
                $h($level, $message, $context, $exception);
            } catch (Exception $e) {
                $this->logger->error('Notification handler failed', ['e' => $e->getMessage()]);
            }
        }

        match ($level) {
            LogLevel::ERROR->value => $this->logger->error($message, $context),
            LogLevel::WARNING->value => $this->logger->warning($message, $context),
            LogLevel::INFO->value => $this->logger->info($message, $context),
            LogLevel::CRITICAL->value => $this->logger->critical($message, $context),
            default => $this->logger->debug($message, $context),
        };

        if (count($this->errorRecords) > $this->maxRecords) array_shift($this->errorRecords);

        return $id;
    }

    private function sanitizeContext(array $context): array
    {
        $sensitive = ['password', 'token', 'secret', 'authorization', 'cookie', 'session', 'csrf_token'];
        foreach ($context as $k => $v) {
            foreach ($sensitive as $s) {
                if (stripos((string) $k, $s) !== false) {
                    $context[$k] = '[REDACTED]';
                    break;
                }
            }
        }
        return $context;
    }

    private function triggerNotifications(string $level, string $message, array $context = [], ?Throwable $exception = null): void
    {
        foreach ($this->notificationHandlers as $h) {
            try {
                $h($level, $message, $context, $exception);
            } catch (Exception $e) {
                $this->logger->error('Notification handler failed', ['e' => $e->getMessage()]);
            }
        }
    }
}
