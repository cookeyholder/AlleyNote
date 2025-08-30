<?php

declare(strict_types=1);

namespace App\Shared\Monitoring\Contracts;

use Throwable;

/**
 * 錯誤追蹤介面。
 * 
 * 提供應用程式錯誤和異常追蹤功能，支援錯誤分類、警告和通知
 */
interface ErrorTrackerInterface
{
    /**
     * 記錄一個錯誤。
     */
    public function recordError(Throwable $error, array $context = []): string;

    /**
     * 記錄一個警告。
     */
    public function recordWarning(string $message, array $context = []): string;

    /**
     * 記錄一個訊息。
     */
    public function recordInfo(string $message, array $context = []): string;

    /**
     * 記錄關鍵錯誤（需要立即注意）。
     */
    public function recordCriticalError(Throwable $error, array $context = []): string;

    /**
     * 取得錯誤統計資料。
     */
    public function getErrorStats(int $hours = 24): array;

    /**
     * 取得最近的錯誤記錄。
     */
    public function getRecentErrors(int $limit = 50): array;

    /**
     * 取得錯誤趨勢分析。
     */
    public function getErrorTrends(int $days = 7): array;

    /**
     * 檢查是否有關鍵錯誤。
     */
    public function hasCriticalErrors(int $minutes = 5): bool;

    /**
     * 取得錯誤摘要報告。
     */
    public function getErrorSummary(int $hours = 24): array;

    /**
     * 清理舊的錯誤記錄。
     */
    public function cleanupOldErrors(int $daysToKeep = 30): int;

    /**
     * 設定錯誤過濾規則。
     */
    public function setErrorFilter(callable $filter): void;

    /**
     * 添加錯誤通知處理器。
     */
    public function addNotificationHandler(callable $handler): void;
}