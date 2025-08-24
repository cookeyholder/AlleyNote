<?php

declare(strict_types=1);

namespace App\Domains\Security\Contracts;

interface ErrorHandlerServiceInterface
{
    /**
     * 處理例外並返回適當的錯誤訊息.
     */
    public function handleException(\Throwable $e, bool $isPublicError = false): array;

    /**
     * 記錄安全事件.
     */
    public function logSecurityEvent(string $event, array $context = []): void;

    /**
     * 記錄登入嘗試.
     */
    public function logAuthenticationAttempt(bool $success, string $username, array $context = []): void;

    /**
     * 記錄可疑活動.
     */
    public function logSuspiciousActivity(string $activity, array $context = []): void;

    /**
     * 清理敏感資料以便安全記錄.
     */
    public function sanitizeLogData(array $data): array;
}
