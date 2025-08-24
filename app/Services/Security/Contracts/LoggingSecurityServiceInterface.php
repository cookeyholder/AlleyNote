<?php

declare(strict_types=1);

namespace App\Services\Security\Contracts;

interface LoggingSecurityServiceInterface
{
    /**
     * 記錄一般安全事件
     */
    public function logSecurityEvent(string $eventType, array $data = []): void;

    /**
     * 記錄關鍵安全事件
     */
    public function logCriticalSecurityEvent(string $eventType, array $data = []): void;

    /**
     * 記錄錯誤訊息
     */
    public function error(string $message, array $context = []): void;

    /**
     * 記錄警告訊息
     */
    public function warning(string $message, array $context = []): void;

    /**
     * 記錄資訊訊息
     */
    public function info(string $message, array $context = []): void;

    /**
     * 記錄除錯訊息
     */
    public function debug(string $message, array $context = []): void;
}
