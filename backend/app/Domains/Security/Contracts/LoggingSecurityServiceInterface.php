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
     * @param array<string, mixed> $context
     */
    public function info(string $message, /** @var array<string, mixed> */ array $context/** @var array<string, mixed> */ = []): void;

    /**
     * 記錄警告.
     * @param array<string, mixed> $context
     */
    public function warning(string $message, /** @var array<string, mixed> */ array $context/** @var array<string, mixed> */ = []): void;

    /**
     * 記錄錯誤.
     * @param array<string, mixed> $context
     */
    public function error(string $message, /** @var array<string, mixed> */ array $context/** @var array<string, mixed> */ = []): void;

    /**
     * 記錄安全事件.
     * @param array<string, mixed> $context
     */
    public function logSecurityEvent(string $event, /** @var array<string, mixed> */ array $context/** @var array<string, mixed> */ = []): void;

    /**
     * 記錄高風險安全事件.
     * @param array<string, mixed> $context
     */
    public function logCriticalSecurityEvent(string $event, /** @var array<string, mixed> */ array $context/** @var array<string, mixed> */ = []): void;

    /**
     * 記錄請求日誌（使用白名單模式）.
     * @param array<string, mixed> $requestData
     */
    public function logRequest(array $requestData): void;

    /**
     * 記錄驗證失敗事件.
     * @param array<string, mixed> $context
     */
    public function logAuthenticationFailure(string $reason, /** @var array<string, mixed> */ array $context/** @var array<string, mixed> */ = []): void;

    /**
     * 記錄授權失敗事件.
     * @param array<string, mixed> $context
     */
    public function logAuthorizationFailure(string $resource, string $action, /** @var array<string, mixed> */ array $context/** @var array<string, mixed> */ = []): void;

    /**
     * 檢查並修正日誌檔案權限.
     * @return array<string, mixed><string, mixed>
     */
    public function verifyLogFilePermissions(): array;

    /**
     * 取得日誌統計資訊.
     * @return array<string, mixed><string, mixed>
     */
    public function getLogStatistics(): array;
}
