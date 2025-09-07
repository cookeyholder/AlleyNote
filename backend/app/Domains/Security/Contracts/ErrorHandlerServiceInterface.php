<?php

declare(strict_types=1);

namespace App\Domains\Security\Contracts;

use Throwable;

interface ErrorHandlerServiceInterface
{
    /**
     * 處理例外並返回適當的錯誤訊息.
     * @return array<string, mixed>
     */
    public function handleException(Throwable $e, bool $isPublicError = false): array;

    /**
     * 記錄安全事件.
     * @param array<string, mixed> $context
     */
    public function logSecurityEvent(string $event, /** @var array<string, mixed> */ array $context = []): void;

    /**
     * 記錄登入嘗試.
     * @param array<string, mixed> $context
     */
    public function logAuthenticationAttempt(bool $success, string $username, /** @var array<string, mixed> */ array $context = []): void;

    /**
     * 記錄可疑活動.
     * @param array<string, mixed> $context
     */
    public function logSuspiciousActivity(string $activity, /** @var array<string, mixed> */ array $context = []): void;

    /**
     * 清理敏感資料以便安全記錄.
     * @param array<string, mixed> $data
     */
    public function sanitizeLogData(array $data): array;
}
