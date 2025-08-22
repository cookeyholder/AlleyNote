<?php

declare(strict_types=1);

namespace App\Services\Security\Contracts;

interface SessionSecurityServiceInterface
{
    /**
     * 初始化安全的 Session 設定
     */
    public function initializeSecureSession(): void;

    /**
     * 在使用者登入後重新產生 Session ID
     */
    public function regenerateSessionId(): void;

    /**
     * 安全地銷毀 Session
     */
    public function destroySession(): void;

    /**
     * 檢查 Session 是否有效
     */
    public function isSessionValid(): bool;

    /**
     * 更新 Session 活動時間
     */
    public function updateActivity(): void;

    /**
     * 設定使用者登入後的 Session 資料
     */
    public function setUserSession(int $userId, string $userIp, string $userAgent): void;

    /**
     * 驗證 Session 的 IP 位址是否一致
     */
    public function validateSessionIp(string $currentIp): bool;

    /**
     * 驗證 Session 的 User-Agent 是否一致
     */
    public function validateSessionUserAgent(string $currentUserAgent): bool;

    /**
     * 檢查是否需要 IP 變更驗證
     */
    public function requiresIpVerification(): bool;

    /**
     * 標記需要 IP 變更驗證
     */
    public function markIpChangeDetected(string $newIp): void;

    /**
     * 完成 IP 變更驗證
     */
    public function confirmIpChange(): void;

    /**
     * 檢查 IP 變更驗證是否過期
     */
    public function isIpVerificationExpired(): bool;

    /**
     * 全面的 Session 安全檢查
     */
    public function performSecurityCheck(string $currentIp, string $currentUserAgent): array;
}
