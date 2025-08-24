<?php

declare(strict_types=1);

namespace App\Domains\Security\Contracts;

/**
 * 安全日誌記錄服務介面.
 */
interface LoggingSecurityServiceInterface
{
    /**
     * 記錄一般資訊.
     */
    public function info(string $message, array $context = []): void;

    /**
     * 記錄警告.
     */
    public function warning(string $message, array $context = []): void;

    /**
     * 記錄錯誤.
     */
    public function error(string $message, array $context = []): void;

    /**
     * 記錄安全事件.
     */
    public function logSecurityEvent(string $event, array $context = []): void;

    /**
     * 記錄高風險安全事件.
     */
    public function logCriticalSecurityEvent(string $event, array $context = []): void;

    /**
     * 記錄請求日誌（使用白名單模式）.
     */
    public function logRequest(array $requestData): void;

    /**
     * 記錄驗證失敗事件.
     */
    public function logAuthenticationFailure(string $reason, array $context = []): void;

    /**
     * 記錄授權失敗事件.
     */
    public function logAuthorizationFailure(string $resource, string $action, array $context = []): void;

    /**
     * 檢查並修正日誌檔案權限.
     */
    public function verifyLogFilePermissions(): array;

    /**
     * 取得日誌統計資訊.
     */
    public function getLogStatistics(): array;
}
